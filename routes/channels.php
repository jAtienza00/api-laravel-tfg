<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('user.{id}', function (Request $request, $id) {
    $token = $request->bearerToken(); // Obtener el token del header Authorization

    if (!$token) {
        return false; // Denegar acceso si no hay token
    }

    try {
        // Decodificar el token con la clave de la API externa
        $decoded = JWT::decode($token, new Key(env('JWT_SECRET_EXTERN'), 'HS256'));

        // Verificar que el usuario autenticado coincide con el canal privado
        if ($decoded->sub == $id) {
            return ['id' => $decoded->sub];
        }
    } catch (\Exception $e) {
        return false; // Token inválido
    }

    return false;
});