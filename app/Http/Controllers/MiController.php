<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
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


    protected static function getAuthenticatedUserId(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET', 'FLlJWvW0W0A5XlOBf2UBxhiRzAXpmo04YUHLHiRNxsw4hhsYKtZwLVUJDfN2ZgFt'), env('JWT_ALGORITHM', 'HS256')));
            if (!isset($decoded->sub)) { // Assuming 'sub' claim holds the user ID
                return response()->json(['message' => 'Token inválido (falta claim sub).'], 401);
            }
            $jwtUserId = $decoded->sub;
            $headerUserId = $request->header('X-USER-ID');
            if ($headerUserId !== null) { // Verifica si el header está presente
                if ((string)$headerUserId !== (string)$jwtUserId) {
                    return response()->json(['message' => 'El ID del header X-USER-ID no concuerda con el token.'], 401);
                }
            }
            return $jwtUserId;
        } catch (ExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json(['message' => 'Token inválido (firma incorrecta).'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }
    }
}
