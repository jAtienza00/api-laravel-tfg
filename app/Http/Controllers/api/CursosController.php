<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MiController;
use App\Models\Curso;

use function PHPUnit\Framework\isNull;

class CursosController extends MiController
{

    protected $imagesFiles = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'ico', 'webp', 'heic', 'heif', 'avif'];


    public function index(Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }
        $cursos = Curso::get(['nombre', 'id', 'color_fondo', 'color_texto']);

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
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png',
            'color_fondo' => 'nullable|string|max:100',
            'color_texto' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Recopilar los datos validados para el curso
            $cursoData = $request->only(['nombre', 'descripcion']);

            // Añadir colores si se proporcionan, si no, la BD usará los defaults definidos en la migración
            if ($request->filled('color_fondo')) {
                $cursoData['color_fondo'] = $request->input('color_fondo');
            }
            if ($request->filled('color_texto')) {
                $cursoData['color_texto'] = $request->input('color_texto');
            }
            foreach ($request->all() as $key => $value) {
                if (is_string($value)) {
                    \Illuminate\Support\Facades\Log::debug("Campo:" . $key . ", Es UTF-8 Válido?: " . (mb_check_encoding($value, 'UTF-8') ? 'Sí' : 'No - Hex: ' . bin2hex($value)));
                }
            }
            // Manejar la subida de la imagen
            if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
                $archivo = $request->file('imagen');
                // $extension = $archivo->getClientOriginalExtension(); // Ya no se usa la extensión sola
                $cursoData['imagen'] = base64_encode(file_get_contents($archivo->getRealPath()));
                $cursoData['tipo_archivo'] = $archivo->getMimeType(); // Guardar el MIME type completo
            } else {
                $cursoData['imagen'] = null;
                $cursoData['tipo_archivo'] = null;
            }

            $cursoData['id_usuario'] = $authUserId;

            $curso = Curso::create($cursoData);

            DB::commit();
            return response()->json(['message' => 'Curso creado correctamente.', 'id' => $curso->id, 'nombre' => $curso->nombre], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al guardar el curso.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        $curso = Curso::find($id);

        if (!$curso) {
            return response()->json(['message' => 'Curso no encontrado.'], 404);
        }

        // Preparar la imagen en Base64
        $imagenBase64 = null;
        if ($curso->imagen && $curso->tipo_archivo) {
            // Asumimos que $curso->tipo_archivo es el MIME type completo
            if (str_starts_with($curso->tipo_archivo, 'image/')) {
                $imagenBase64 = 'data:' . $curso->tipo_archivo . ';base64,' . $curso->imagen;
            }
        }

        $cursoResponse = $curso->toArray();
        $cursoResponse['imagen'] = $imagenBase64;

        $contenido = $curso->contenido_cursos()->get();

        foreach ($contenido as $itemContenido) {
            if ($itemContenido->archivo && $itemContenido->tipo_archivo) {
                // $itemContenido->tipo_archivo ahora es un MIME type completo, ej: "image/jpeg", "application/pdf"
                $esImagen = str_starts_with($itemContenido->tipo_archivo, 'image/');

                if ($esImagen) {
                    // Si es imagen, la mostramos en base64
                    $imagenBase64Contenido = 'data:' . $itemContenido->tipo_archivo . ';base64,' . $itemContenido->archivo;
                    $itemContenido->archivo = $imagenBase64Contenido; // Modificar el atributo del objeto
                    $itemContenido->esImagen = true;
                    $itemContenido->nombre_archivo_original = null;
                    // Opcionalmente, también ofrecer descarga para imágenes si se desea
                    $itemContenido->url_descarga = route('contenido.descargar', ['id_curso' => $curso->id, 'id_contenido' => $itemContenido->id]);
                } else {
                    // Para otros tipos de archivo, solo generar la URL de descarga
                    $itemContenido->url_descarga = route('contenido.descargar', ['id_curso' => $curso->id, 'id_contenido' => $itemContenido->id]);
                    $itemContenido->archivo = null; // No enviar el binario en el JSON principal para no hacerlo pesado
                    $itemContenido->esImagen = false;
                }
            } else {
                $itemContenido->url_descarga = null;
                $itemContenido->nombre_archivo_original = null;
                $itemContenido->es_imagen = false; // Asegurar que el flag esté presente
            }
        }
        return response()->json(['curso' => $cursoResponse, 'contenido' => $contenido], 200);
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
            'nombre' => 'nullable|string|max:100',
            'descripcion' => 'required|string',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png',
            'color_fondo' => 'nullable|string|max:100',
            'color_texto' => 'nullable|string|max:100',
        ]);

        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                \Illuminate\Support\Facades\Log::debug("Campo:" . $key . ", Es UTF-8 Válido?: " . (mb_check_encoding($value, 'UTF-8') ? 'Sí' : 'No - Hex: ' . bin2hex($value)));
            }
        }

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $cursoData = $request->only(['descripcion']);

            // Añadir colores si se proporcionan, si no, la BD usará los defaults definidos en la migración
            $cursoData['nombre'] = $request->filled('nombre') ? $request->input('nombre') : $curso->nombre;
            $cursoData['color_fondo'] = $request->filled('color_fondo') ? $request->input('color_fondo') : $curso->color_fondo;
            $cursoData['color_texto'] = $request->filled('color_texto') ? $request->input('color_texto') : $curso->color_texto;

            // Manejar la subida de la imagen
            if ($request->hasFile('imagen') && $request->file('imagen')->isValid()) {
                $archivo = $request->file('imagen');
                // $extension = $archivo->getClientOriginalExtension();
                $cursoData['imagen'] =  base64_encode(file_get_contents($archivo->getRealPath()));
                $cursoData['tipo_archivo'] = $archivo->getMimeType(); // Guardar el MIME type completo
            } else {
                $cursoData['imagen'] = $curso->imagen;
                $cursoData['tipo_archivo'] = $curso->tipo_archivo;
            }
            $curso->update($cursoData);

            DB::commit();
            return response()->json(['message' => 'Curso actualizado correctamente.', 'id' => $curso->id, 'nombre' => $curso->nombre], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error('Error al actualizar el curso: ' . $e->getMessage());
            $errorMessage = $e->getMessage();
            // Asegurarse de que el mensaje de error sea UTF-8 válido
            if (!mb_check_encoding($errorMessage, 'UTF-8')) {
                $errorMessage = mb_convert_encoding($errorMessage, 'UTF-8', 'UTF-8');
            }
            return response()->json(['message' => 'Error al actualizar el curso.', 'error' => $errorMessage], 500);
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
            $curso->delete();
            DB::commit();
            return response()->json(['message' => 'Curso eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            //\Illuminate\Support\Facades\Log::error('Error al eliminar el curso: ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar el curso.'], 500);
        }
    }
}
