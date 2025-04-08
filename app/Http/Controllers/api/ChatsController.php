<?php

namespace App\Http\Controllers;

use App\Events\websocket;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWT;

class ChatsController extends Controller
{
    public function postMesaage(Request $request){
        event(new websocket($request->id, $request->message));
    }
}
