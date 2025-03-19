<?php

namespace App\Http\Controllers;

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
}