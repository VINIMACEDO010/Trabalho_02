<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require_once 'main.php';

function processar_requisicao_api(): void
{
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    if ($metodo !== 'POST') {
        http_response_code(405);
        echo json_encode(['erro' => 'Método não permitido. Use POST.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $corpo = file_get_contents('php://input');
    $dados = json_decode($corpo, true);

    if (!isset($dados['rotas']) || !is_array($dados['rotas'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Campo "rotas" é obrigatório e deve ser um array.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $taxa_penalidade = isset($dados['taxa_penalidade_por_minuto']) ? (float)$dados['taxa_penalidade_por_minuto'] : 0.50;

    $resultados_rotas = processar_rotas($dados['rotas'], $taxa_penalidade);

    $custo_global = calcular_custo_global($resultados_rotas);
    $total_rateado = calcular_total_rateado($resultados_rotas);

    $invariante_custo_global_ok = verificar_invariante_custo_global($resultados_rotas);
    $invariante_km_rodados_ok = verificar_invariante_km_rodados_positivos($dados['rotas']);

    http_response_code(200);
    echo json_encode([
        'sucesso' => true,
        'resultados' => $resultados_rotas,
        'sumario' => [
            'custo_global_total' => round($custo_global, 2),
            'total_rateado' => round($total_rateado, 2),
            'invariante_custo_global' => $invariante_custo_global_ok,
            'invariante_km_rodados' => $invariante_km_rodados_ok
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

processar_requisicao_api();
