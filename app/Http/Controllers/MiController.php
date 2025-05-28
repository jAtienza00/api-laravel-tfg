<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

class MiController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    public static function arrayToJson(Collection $coleccion)
    {
        $array = $coleccion->toArray();
        return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    public function usuarioDesdeToken(Request $request, $id)
    {
        $token = $request->bearerToken(); // Obtener el token del header Authorization

        if (!$token) {
            return false; // Denegar acceso si no hay token
        }

        try {
            // Decodificar el token con la clave de la API externa
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), env('JWT_ALGORITHM')));

            // Verificar que el usuario autenticado coincide con el canal privado
            if ($decoded->sub == $id) {
                return ['id' => $decoded->sub];
            }
        } catch (\Exception $e) {
            return false; // Token inválido
        }

        return false;
    }
}
