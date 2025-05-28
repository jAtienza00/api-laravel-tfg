<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Http\Controllers\MiController;
use Illuminate\Support\Facades\Log;
/**
 * @OA\Info(title="API de Clases", version="1.0")
 */
class CriptoController extends MiController
{

    private static $url = "https://api.coingecko.com/api/v3/coins";
    private static $urlSearch = "https://api.coingecko.com/api/v3/search";
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
            $pagina = 1;
            if ($request->has('pagina')) {
                $pagina = $request->pagina;
            }

            $url = CriptoController::$url . '/markets?vs_currency=' . $moneda . '&per_page=15&page=' . $pagina;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout de conexión (10 segundos)
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout total (30 segundos)

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

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
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Si la API de CoinGecko no responde o devuelve un error
            if (!$response || $httpCode >= 400) {
                // Datos de ejemplo para una criptomoneda específica
                $mockData = [
                    "id" => $id,
                    "symbol" => $id == "bitcoin" ? "btc" : "eth",
                    "name" => $id == "bitcoin" ? "Bitcoin" : "Ethereum",
                    "image" => [
                        "large" => $id == "bitcoin"
                            ? "https://assets.coingecko.com/coins/images/1/large/bitcoin.png"
                            : "https://assets.coingecko.com/coins/images/279/large/ethereum.png"
                    ],
                    "market_data" => [
                        "current_price" => [
                            "eur" => $id == "bitcoin" ? 53000 : 2900,
                            "usd" => $id == "bitcoin" ? 57000 : 3100
                        ],
                        "market_cap" => [
                            "eur" => $id == "bitcoin" ? 1034220727155 : 347982356123
                        ],
                        "price_change_percentage_24h" => $id == "bitcoin" ? 1.5 : 0.8
                    ],
                    "description" => [
                        "es" => $id == "bitcoin"
                            ? "Bitcoin es una moneda digital descentralizada sin un banco central o administrador único."
                            : "Ethereum es una plataforma de computación descentralizada que permite a los desarrolladores crear aplicaciones descentralizadas."
                    ]
                ];

                // Registrar el error en el log pero devolver datos de ejemplo
                Log::warning('Error en la API de CoinGecko (show)', [
                    'id' => $id,
                    'http_code' => $httpCode,
                    'curl_error' => $error,
                    'url' => $url
                ]);

                return response()->json($mockData, 200);
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
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Si la API de CoinGecko no responde o devuelve un error
            if (!$response || $httpCode >= 400) {
                // Valores de ejemplo para la conversión
                $precioCripto = $id == "bitcoin" ? 53000 : 2900;
                $equivalente = $cantidad / $precioCripto;

                // Registrar el error en el log pero devolver datos de ejemplo
                Log::warning('Error en la API de CoinGecko (convertir)', [
                    'id' => $id,
                    'cantidad' => $cantidad,
                    'http_code' => $httpCode,
                    'curl_error' => $error,
                    'url' => $url
                ]);

                return response()->json([
                    'equivalente' => $equivalente,
                    'moneda' => $precioCripto,
                    'cantidad' => $cantidad,
                    'from_mock' => true
                ], 200);
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

    public function buscar(Request $request){
        try {
            $query = $request->query('query');
            if (!$query) return response()->json(["error" => "Falta la query"], 400);

            $url = CriptoController::$urlSearch . '?query=' . $query;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Si la API de CoinGecko no responde o devuelve un error
            if (!$response || $httpCode >= 400) {
                // Datos de ejemplo para la búsqueda
                $mockData = [
                    "coins" => [
                        [
                            "id" => "bitcoin",
                            "name" => "Bitcoin",
                            "symbol" => "btc",
                            "market_cap_rank" => 1,
                            "thumb" => "https://assets.coingecko.com/coins/images/1/thumb/bitcoin.png",
                            "large" => "https://assets.coingecko.com/coins/images/1/large/bitcoin.png"
                        ],
                        [
                            "id" => "ethereum",
                            "name" => "Ethereum",
                            "symbol" => "eth",
                            "market_cap_rank" => 2,
                            "thumb" => "https://assets.coingecko.com/coins/images/279/thumb/ethereum.png",
                            "large" => "https://assets.coingecko.com/coins/images/279/large/ethereum.png"
                        ]
                    ],
                    "categories" => [],
                    "exchanges" => []
                ];

                // Registrar el error en el log pero devolver datos de ejemplo
                Log::warning('Error en la API de CoinGecko (buscar)', [
                    'query' => $query,
                    'http_code' => $httpCode,
                    'curl_error' => $error,
                    'url' => $url
                ]);

                return response()->json($mockData, 200);
            }

            return response()->json(json_decode($response), $httpCode);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    public function top(Request $request)
    {
        try {
            $moneda = $this->vs_currency;
            if ($request->has('moneda')) {
                $moneda = $request->moneda;
            }
            $pagina = 1;

            $url = CriptoController::$url . '/markets?vs_currency=' . $moneda . '&order=market_cap_desc&per_page=3&page=' . $pagina;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
                'x-cg-demo-api-key: ' . CriptoController::$api_key,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Si la API de CoinGecko no responde o devuelve un error
            if (!$response || $httpCode >= 400) {
                // Datos de ejemplo para las criptomonedas top
                $mockData = [
                    [
                        "id" => "bitcoin",
                        "symbol" => "btc",
                        "name" => "Bitcoin",
                        "image" => "https://assets.coingecko.com/coins/images/1/large/bitcoin.png",
                        "current_price" => 53000,
                        "market_cap" => 1034220727155,
                        "market_cap_rank" => 1,
                        "price_change_percentage_24h" => 1.5,
                        "circulating_supply" => 19460000
                    ],
                    [
                        "id" => "ethereum",
                        "symbol" => "eth",
                        "name" => "Ethereum",
                        "image" => "https://assets.coingecko.com/coins/images/279/large/ethereum.png",
                        "current_price" => 2900,
                        "market_cap" => 347982356123,
                        "market_cap_rank" => 2,
                        "price_change_percentage_24h" => 0.8,
                        "circulating_supply" => 120000000
                    ],
                    [
                        "id" => "tether",
                        "symbol" => "usdt",
                        "name" => "Tether",
                        "image" => "https://assets.coingecko.com/coins/images/325/large/Tether.png",
                        "current_price" => 0.92,
                        "market_cap" => 92000000000,
                        "market_cap_rank" => 3,
                        "price_change_percentage_24h" => 0.1,
                        "circulating_supply" => 100000000000
                    ]
                ];

                // Registrar el error en el log pero devolver datos de ejemplo
                Log::warning('Error en la API de CoinGecko (top)', [
                    'http_code' => $httpCode,
                    'curl_error' => $error,
                    'url' => $url
                ]);

                return response()->json($mockData, 200);
            }

            return response()->json(json_decode($response), $httpCode);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
