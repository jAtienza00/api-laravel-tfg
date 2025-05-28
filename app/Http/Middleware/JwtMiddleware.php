<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$token = $request->bearerToken() || ! $id = $request->header('X-User-ID')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            // Decodificar el token con la clave de la API externa
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET', 'FLlJWvW0W0A5XlOBf2UBxhiRzAXpmo04YUHLHiRNxsw4hhsYKtZwLVUJDfN2ZgFt'), env('JWT_ALGORITHM', 'HS256')));

            // Verificar que el usuario autenticado coincide con el canal privado
            if ($decoded->sub == $id) {
                return $next($request);
            }
        }catch(ExpiredException $e){
            return response()->json(['message' => 'Expired'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error'], 500);
        }
        return $next($request);
    }
}
