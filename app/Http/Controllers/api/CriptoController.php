<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\MiController;
/**
 * @OA\Info(title="API de Clases", version="1.0")
 */
class CriptoController extends MiController
{

    private static $url = "https://api.coingecko.com/api/v3/coins";
    private $vs_currency = "eur";
    private static $api_key = "CG-82Au3XunT489YeLVTddCNdEU";

    /**
     * @OA\Get(
     *     path="/api/cripto",
     *     summary="Obtener listado de criptomonedas",
     *     description="Obtiene un listado de criptomonedas con el precio actual en la moneda seleccionada.",
     *     @OA\Parameter(
     *         name="moneda",
     *         in="query",
     *         description="Moneda en la que se desea obtener el precio",
     *         required=false,
     *         @OA\Schema(type="string", default="eur")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de criptomonedas obtenida correctamente",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en la conexión o procesamiento de la respuesta"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $moneda = $this->vs_currency;
            if ($request->has('moneda')) {
                $moneda = $request->moneda;
            }

            $url = CriptoController::$url . '/market?vs_currency=' . $moneda . '&per_page=15&page=1';

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva verificación SSL en caso de errores SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtener código de respuesta HTTP
            $error = curl_error($ch); // Obtener errores de cURL
            curl_close($ch);

            // Depuración si la respuesta está vacía
            if (!$response) {
                return response()->json([
                    'error' => 'No se recibió respuesta de la API',
                    'http_code' => $httpCode,
                    'curl_error' => $error
                ], 500);
            }

            return response()->json(json_decode($response), $httpCode);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/cripto/{id}",
     *     summary="Obtener información de una criptomoneda específica",
     *     description="Obtiene la información detallada de una criptomoneda en particular.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la criptomoneda",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="moneda",
     *         in="query",
     *         description="Moneda en la que se desea obtener el precio",
     *         required=false,
     *         @OA\Schema(type="string", default="eur")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información de la criptomoneda obtenida correctamente",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en la conexión o procesamiento de la respuesta"
     *     )
     * )
     */
    public function show($id, Request $request)
    {
        try {
            $moneda = $this->vs_currency;
            if ($request->has('moneda')) {
                $moneda = $request->moneda;
            }

            $url = CriptoController::$url . '/'.$id.'?vs_currency=' . $moneda;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva verificación SSL en caso de errores SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtener código de respuesta HTTP
            $error = curl_error($ch); // Obtener errores de cURL
            curl_close($ch);

            // Depuración si la respuesta está vacía
            if (!$response) {
                return response()->json([
                    'error' => 'No se recibió respuesta de la API',
                    'http_code' => $httpCode,
                    'curl_error' => $error
                ], 500);
            }

            return response()->json(json_decode($response), $httpCode);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/cripto/{id}/convertir",
     *     summary="Convertir una cantidad de criptomoneda a otra moneda",
     *     description="Convierte una cantidad de criptomoneda a otra moneda utilizando el precio actual.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la criptomoneda",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="cantidad",
     *         in="query",
     *         description="Cantidad de criptomoneda que se desea convertir",
     *         required=true,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="moneda",
     *         in="query",
     *         description="Moneda en la que se desea obtener el valor",
     *         required=false,
     *         @OA\Schema(type="string", default="eur")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cantidad de criptomoneda convertida correctamente",
     *         @OA\JsonContent(type="object", @OA\Property(property="equivalente", type="number", format="float"))
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error en la conversión"
     *     )
     * )
     */
    public function convertir($id, $cantidad, Request $request)
    {
        try {
            $moneda = $this->vs_currency;
            if ($request->has('moneda')) {
                $moneda = $request->moneda;
            }

            $url = CriptoController::$url . '/'.$id.'?vs_currency=' . $moneda;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactiva verificación SSL en caso de errores SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Obtener código de respuesta HTTP
            $error = curl_error($ch); // Obtener errores de cURL
            curl_close($ch);

            // Depuración si la respuesta está vacía
            if (!$response) {
                return response()->json([
                    'error' => 'No se recibió respuesta de la API',
                    'http_code' => $httpCode,
                    'curl_error' => $error
                ], 500);
            }
            $data = json_decode($response, true);

            $precioCripto = $data['market_data']['current_price'][$moneda];
            $equivalente = $cantidad / $precioCripto;
            return response()->json([
                'equivalente' => $equivalente,
                'moneda' => $precioCripto,
                'cantidad' => $cantidad,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

}
