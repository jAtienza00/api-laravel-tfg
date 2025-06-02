<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\MiController;
use App\Models\Contenido_cursos;


class ContenidoController extends MiController
{

    protected static $extensionesPeligrosas = [
        'php',
        'php3',
        'php4',
        'php5',
        'php7',
        'phtml',
        'phar',
        'asp',
        'aspx',
        'jsp',
        'cgi',
        'pl',
        'sh',
        'bash',
        'bin',
        'exe',
        'dll',
        'com',
        'bat',
        'cmd',
        'vbs',
        'vb',
        'js',
        'jse',
        'mjs',
        'ts',
        'py',
        'rb',
        'lua',
        'wsf',
        'wsh',
        'ps1',
        'ps2',
        'ps3',
        'ps4',
        'scr',
        'jar',
        'class',
        'war',
        'swf',
        'lnk',
        'iso',
        'img',
        'hta',
        'reg',
        'sql',
        'db',
        'bak',
        'inc',
        'conf',
        'ini',
        'htaccess',
        'htpasswd'
    ];

    protected static $tiposMimePeligrosos = [
        'application/x-php',
        'application/x-sh',
        'application/x-executable',
        'application/x-msdownload',
        'application/x-bat',
        'application/x-msdos-program',
        'application/javascript',
        'application/ecmascript',
        'application/x-python',
        'application/x-ruby',
        'application/x-perl',
        'application/x-csh',
        'application/x-shellscript',
        'text/x-shellscript',
        'text/javascript',
        'text/html',
        'text/x-php',
        'text/x-python',
        'text/x-perl',
        'application/x-java-applet',
        'application/x-java-archive',
        'application/x-msinstaller',
        'application/x-msi',
        'application/x-dosexec',
        'application/x-sql',
    ];



    public function index($id, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }
        $contenido = Contenido_cursos::where('id_curso', $id)->get(['titulo', 'id']);

        return response()->json($contenido);
    }

    public function store($id_curso, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }

        // Validar tanto los datos del request como el id_curso de la ruta
        $validationData = array_merge($request->all(), ['id_curso_param' => $id_curso]);
        $validator = Validator::make($validationData, [
            'titulo' => 'required|string|max:100',
            'archivo' => 'nullable|file',
            'mensaje' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dataToCreate = [
            'id_cursos' => $id_curso,
            'titulo' => $request->input('titulo'),
            'mensaje' => $request->input('mensaje', null),
            'archivo' => null,
            'tipo_archivo' => null,
            'nombre_archivo_original' => null,
        ];

        // Manejo de archivo y validación de tipo de archivo peligroso
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            if (!$archivo->isValid()) {
                return response()->json(['message' => 'Archivo inválido o corrupto.'], 422);
            }

            $extension = strtolower($archivo->getClientOriginalExtension());
            $mime = $archivo->getMimeType();

            if (in_array($extension, self::$extensionesPeligrosas) || in_array($mime, self::$tiposMimePeligrosos)) {
                return response()->json(['message' => 'Tipo de archivo no permitido.'], 422);
            }
            $dataToCreate['archivo'] = file_get_contents($archivo->getRealPath());
            $dataToCreate['tipo_archivo'] = $mime; // Guardar el MIME type completo
            $dataToCreate['nombre_archivo_original'] = $archivo->getClientOriginalName();
        }

        DB::beginTransaction();
        try {
            $contenido = Contenido_cursos::create($dataToCreate);
            DB::commit();
            return response()->json(['message' => 'Contenido creado correctamente.', 'contenido_id' => $contenido->id], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            if (!mb_check_encoding($errorMessage, 'UTF-8')) {
                $errorMessage = mb_convert_encoding($errorMessage, 'UTF-8', 'UTF-8');
            }
            return response()->json(['message' => 'Error al crear el contenido.', 'error' => $errorMessage], 500);
        }
    }

    public function destroy($id_curso, $id_contenido, Request $request)
    {
        $authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }
        $contenido = Contenido_cursos::where('id_cursos', $id_curso)->where('id', $id_contenido)->first();
        if (!$contenido) {
            return response()->json(['message' => 'Contenido no encontrado o no pertenece al curso especificado.'], 404);
        }
        $cursoDelContenido = $contenido->cursos; // Acceder al modelo Curso relacionado

        if (!$cursoDelContenido || $authUserId != $cursoDelContenido->id_usuario) {
            return response()->json(['message' => 'No tienes permiso para eliminar este contenido.'], 403);
        }
        DB::beginTransaction();
        try {
            $contenido->delete();
            DB::commit();
            return response()->json(['message' => 'Contenido eliminado correctamente.'], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorMessage = $e->getMessage();
            if (!mb_check_encoding($errorMessage, 'UTF-8')) {
                $errorMessage = mb_convert_encoding($errorMessage, 'UTF-8', 'UTF-8');
            }
            return response()->json(['message' => 'Error al eliminar el contenido.', 'error' => $errorMessage], 500);
        }
    }

    private function getExtensionFromMimeType(string $mimeType): string
    {
        // Mapeo simple, se puede expandir o usar una librería como league/mime-type-detection
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            // Añadir más tipos comunes según sea necesario
        ];

        if (isset($mimeMap[$mimeType])) {
            return $mimeMap[$mimeType];
        }

        // Fallback: intentar obtener la parte después de '/'
        $parts = explode('/', $mimeType);
        $subType = end($parts);
        // Limpiar caracteres no alfanuméricos para una extensión válida
        $subType = preg_replace("/[^a-zA-Z0-9]/", "", $subType);
        return $subType ?: 'bin'; // 'bin' como extensión genérica si todo falla
    }

    public function descargarArchivo($id_curso, $id_contenido, Request $request)
    {
        /*$authUserId = $this->getAuthenticatedUserId($request);
        if ($authUserId instanceof \Illuminate\Http\JsonResponse) {
            return $authUserId;
        }*/

        $contenido = Contenido_cursos::where('id_cursos', $id_curso)
            ->where('id', $id_contenido)
            ->first();

        if (!$contenido || !$contenido->archivo || !$contenido->tipo_archivo) {
            return response()->json(['message' => 'Archivo no encontrado o contenido no válido.'], 404);
        }

        $nombreArchivoDescarga = $contenido->nombre_archivo_original;

        if (!$nombreArchivoDescarga) {
            $nombreOriginalBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $contenido->titulo ?: 'archivo_descargado');
            $extension = $this->getExtensionFromMimeType($contenido->tipo_archivo);
            $nombreArchivoDescarga = $nombreOriginalBase . '.' . $extension;
        }

        return response()->make($contenido->archivo, 200, [
            'Content-Type' => $contenido->tipo_archivo,
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($nombreArchivoDescarga) . '"',
        ]);
    }
}
