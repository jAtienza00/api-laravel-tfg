<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MiController;
use App\Models\Curso;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

use function PHPUnit\Framework\isNull;

class CursosController extends MiController
{
    /**
     * Helper function to get authenticated user ID from JWT token.
     * Returns user ID on success, or a JsonResponse on failure.
     */
    private function getAuthenticatedUserId(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), env('JWT_ALGORITHM', 'HS256')));
            if (!isset($decoded->sub)) { // Assuming 'sub' claim holds the user ID
                return response()->json(['message' => 'Token inválido (falta claim sub).'], 401);
            }
            if (!isNull($request->header('X-USER-ID'))) {
                if (($request->header('X-USER-ID') != $decoded->sub)) {
                    return $decoded->sub; // Return user ID
                }
                return response()->json(['message' => 'No concuerdan los ids.'], 401);
            }
            return response()->json(['message' => 'Id no proporcionado.'], 401);
        } catch (ExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json(['message' => 'Token inválido (firma incorrecta).'], 401);
        } catch (\Exception $e) {
            // Log::error('Error al decodificar JWT: ' . $e->getMessage());
            return response()->json(['message' => 'Token inválido.'], 401);
        }
    }

    public function index(Request $request)
    {
        // Optional: Secure this endpoint if needed, e.g., by checking for a valid token
        // $authUserId = $this->getAuthenticatedUserId($request);
        // if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
        //     return $authUserId;
        // }

        // Load courses, optionally with their content
        // For performance, avoid loading 'contenidoCursos' for all courses in a list
        // unless specifically requested and paginated.
        $cursos = Curso::query()
            ->when($request->query('with_contenido'), function ($query) {
                $query->with('contenidoCursos');
            })
            ->get();

        return response()->json($cursos);
    }

    public function store(Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'imagen' => 'nullable|string', // Or file validation rules if handling uploads
            'color_fondo' => 'nullable|string|max:100',
            'color_texto' => 'nullable|string|max:100',
            'contenido_cursos' => 'nullable|array',
            'contenido_cursos.*.titulo' => 'required_with:contenido_cursos|string|max:100',
            'contenido_cursos.*.mensaje' => 'required_with:contenido_cursos|string',
            'contenido_cursos.*.archivo' => 'nullable|string', // Or file validation
            'contenido_cursos.*.tipo_archivo' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $cursoData = $request->except('contenido_cursos');
            $cursoData['id_usuario'] = $authUserId;
            $curso = Curso::create($cursoData);

            if ($request->has('contenido_cursos') && is_array($request->contenido_cursos)) {
                foreach ($request->contenido_cursos as $contenidoData) {
                    $curso->contenidoCursos()->create($contenidoData);
                }
            }

            DB::commit();
            return response()->json($curso->load('contenidoCursos'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error al guardar el curso: ' . $e->getMessage());
            return response()->json(['message' => 'Error al guardar el curso.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        $curso = Curso::with('contenidoCursos')->find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado.'], 404);
        }
        $contenido = $curso->contenido_cursos();

        return response()->json([$curso, $contenido], 200);
    }

    public function update(Request $request, $id)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        $curso = Curso::find($id);
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado.'], 404);
        }

        if ($curso->id_usuario != $authUserId) {
            return response()->json(['message' => 'No autorizado para modificar este curso.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'sometimes|required|string',
            // Add other validation rules for curso fields
            'contenido_cursos' => 'nullable|array',
            // Add validation for contenido_cursos items (id, titulo, etc.)
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $curso->update($request->except(['contenido_cursos', 'id_usuario'])); // id_usuario should not be mass-assignable here

            if ($request->has('contenido_cursos')) {
                $this->syncContenidoCursos($curso, $request->input('contenido_cursos', []));
            }

            DB::commit();
            return response()->json($curso->load('contenidoCursos'));
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error al actualizar el curso: ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar el curso.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to sync contenido_cursos for a given curso during update.
     */
    private function syncContenidoCursos(Curso $curso, array $contenidosData)
    {
        $existingContenidoIds = $curso->contenidoCursos()->pluck('id')->toArray();
        $requestContenidoIds = [];

        foreach ($contenidosData as $contenidoData) {
            // Basic validation for each item
            $itemValidator = Validator::make($contenidoData, [
                'id' => 'nullable|integer|exists:contenido_cursos,id', // Ensure ID exists if provided
                'titulo' => 'required|string|max:100',
                'mensaje' => 'required|string',
                'archivo' => 'nullable|image',
                'tipo_archivo' => 'nullable|string|max:100',
            ]);
            if ($itemValidator->fails()) {
                // Handle individual item validation error, e.g., throw exception or collect errors
                throw new \Illuminate\Validation\ValidationException($itemValidator);
            }

            if (isset($contenidoData['id'])) {
                $contenido = $curso->contenidoCursos()::find($contenidoData['id']);
                if ($contenido && $contenido->id_cursos == $curso->id) { // Ensure it belongs to the course
                    $contenido->update($contenidoData);
                    $requestContenidoIds[] = $contenido->id;
                }
            } else {
                $newContenido = $curso->contenidoCursos()->create($contenidoData);
                $requestContenidoIds[] = $newContenido->id;
            }
        }

        // Delete contenido_cursos not present in the request
        $idsToDelete = array_diff($existingContenidoIds, $requestContenidoIds);
        if (!empty($idsToDelete)) {
            $curso->contenidoCursos()::whereIn('id', $idsToDelete)->where('id_cursos', $curso->id)->delete();
        }
    }

    public function destroy($id, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        $curso = Curso::find($id);
        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado.'], 404);
        }

        if ($curso->id_usuario != $authUserId) {
            return response()->json(['message' => 'No autorizado para eliminar este curso.'], 403);
        }

        DB::beginTransaction();
        try {
            // Related contenido_cursos will be deleted by model event or DB cascade if configured.
            // Otherwise, delete them manually: $curso->contenidoCursos()->delete();
            $curso->delete();
            DB::commit();
            return response()->json(['message' => 'Curso eliminado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error al eliminar el curso: ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar el curso.'], 500);
        }
    }
}
