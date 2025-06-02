<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\CriptoController;
use App\Http\Controllers\api\CursosController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::get('/cripto', [CriptoController::class, 'index']);
Route::get('/cripto/top', [CriptoController::class, 'top']);
Route::get('/buscar', [CriptoController::class, 'buscar']);
Route::get('/cripto/{id}', [CriptoController::class, 'show']);
Route::get('/cripto/{id}/{cantidad}', [CriptoController::class, 'convertir']);

Route::resource('/cursos', 'App\Http\Controllers\api\CursosController');

// Ruta para descargar archivos de contenido de un curso
Route::get('/curso/contenido/{id_curso}/{id_contenido}/descargar', [\App\Http\Controllers\api\ContenidoController::class, 'descargarArchivo'])->name('contenido.descargar');
// La ruta resource para contenido debe ir después o asegurarse de que no haya conflictos.
Route::post('/contenido/{id_curso}', [\App\Http\Controllers\api\ContenidoController::class, 'store']);
Route::delete('/contenido/{id_curso}/{id_contenido}', [\App\Http\Controllers\api\ContenidoController::class, 'destroy']);
