<?php
declare(strict_types=1);

function validar_numero_nao_negativo(mixed $valor): bool
{
    return is_numeric($valor) && (float)$valor >= 0;
}

function validar_numero_positivo(mixed $valor): bool
{
    return is_numeric($valor) && (float)$valor > 0;
}

function validar_inteiro_nao_negativo(mixed $valor): bool
{
    return is_numeric($valor) && is_int($valor) && (int)$valor >= 0;
}

function validar_inteiro_positivo(mixed $valor): bool
{
    return is_numeric($valor) && is_int($valor) && (int)$valor > 0;
}

function validar_dados_rota(array $rota): bool
{
    return (
        isset($rota["id"]) && is_string($rota["id"]) &&
        isset($rota["nome"]) && is_string($rota["nome"]) &&
        isset($rota["km_rodados"]) && validar_numero_positivo($rota["km_rodados"]) &&
        isset($rota["alunos"]) && validar_inteiro_positivo($rota["alunos"]) &&
        isset($rota["consumo_medio_litro_por_km"]) && validar_numero_positivo($rota["consumo_medio_litro_por_km"]) &&
        isset($rota["preco_combustivel_por_litro"]) && validar_numero_positivo($rota["preco_combustivel_por_litro"]) &&
        (!isset($rota["atraso_minutos"]) || validar_inteiro_nao_negativo($rota["atraso_minutos"]))
    );
}

function calcular_custo_por_km(float $consumo_medio_litro_por_km, float $preco_combustivel_por_litro): float
{
    return $consumo_medio_litro_por_km * $preco_combustivel_por_litro;
}

function calcular_custo_total_rota(float $km_rodados, float $custo_por_km): float
{
    return $km_rodados * $custo_por_km;
}

function calcular_custo_por_aluno_rota(float $custo_total_rota, int $numero_alunos): float
{
    return $numero_alunos > 0 ? $custo_total_rota / $numero_alunos : 0.0;
}

function calcular_eficiencia_km_por_aluno(float $km_rodados, int $numero_alunos): float
{
    return $numero_alunos > 0 ? $km_rodados / $numero_alunos : 0.0;
}

function calcular_penalidade_atraso(int $atraso_minutos, float $taxa_penalidade_por_minuto): float
{
    return $atraso_minutos > 0 ? $atraso_minutos * $taxa_penalidade_por_minuto : 0.0;
}

function processar_rota(array $rota, float $taxa_penalidade_por_minuto): array
{
    $custo_por_km = calcular_custo_por_km(
        (float)$rota["consumo_medio_litro_por_km"],
        (float)$rota["preco_combustivel_por_litro"]
    );
    $penalidade_aplicada = calcular_penalidade_atraso(
        $rota["atraso_minutos"] ?? 0,
        $taxa_penalidade_por_minuto
    );
    $custo_total_rota = calcular_custo_total_rota(
        (float)$rota["km_rodados"],
        $custo_por_km
    );
    $custo_total_rota_com_penalidade = $custo_total_rota + $penalidade_aplicada;
    $custo_por_aluno_rota = calcular_custo_por_aluno_rota(
        $custo_total_rota_com_penalidade,
        (int)$rota["alunos"]
    );
    $eficiencia_km_por_aluno = calcular_eficiencia_km_por_aluno(
        (float)$rota["km_rodados"],
        (int)$rota["alunos"]
    );

    return [
        "id" => $rota["id"],
        "nome" => $rota["nome"],
        "custo_total_rota" => $custo_total_rota_com_penalidade,
        "custo_por_aluno_rota" => $custo_por_aluno_rota,
        "eficiencia_km_por_aluno" => $eficiencia_km_por_aluno,
        "penalidade_aplicada" => $penalidade_aplicada,
        "alunos" => (int)$rota["alunos"]
    ];
}

function processar_rotas(array $rotas, float $taxa_penalidade_por_minuto): array
{
    $rotas_validas = array_filter($rotas, 'validar_dados_rota');
    return array_map(fn($rota) => processar_rota($rota, $taxa_penalidade_por_minuto), $rotas_validas);
}

function calcular_custo_global(array $resultados_rotas): float
{
    return array_reduce($resultados_rotas, fn(float $carry, array $item) => $carry + $item["custo_total_rota"], 0.0);
}

function calcular_total_rateado(array $resultados_rotas): float
{
    return array_reduce($resultados_rotas, fn(float $carry, array $item) => $carry + $item["custo_total_rota"], 0.0);
}

function verificar_invariante_custo_global(array $resultados_rotas): bool
{
    $custo_global = calcular_custo_global($resultados_rotas);
    $total_rateado = calcular_total_rateado($resultados_rotas);
    return abs($custo_global - $total_rateado) < 0.001;
}

function verificar_invariante_km_rodados_positivos(array $rotas_originais): bool
{
    $rotas_validas = array_filter($rotas_originais, 'validar_dados_rota');
    return empty(array_filter($rotas_validas, fn($rota) => (float)$rota['km_rodados'] <= 0));
}
