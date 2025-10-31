<?php

namespace App\Http\Controllers;

use App\Jobs\EmitirNotaFiscalJob;
use Illuminate\Http\Request;

class TesteNotaFiscalController extends Controller
{
    // gera notas fiscais simuladas
    private function gerarNotasFake($qtd = 20)
    {
        $notas = [];
        for ($i = 0; $i < $qtd; $i++) {
            $notas[] = [
                'numero' => 'NF-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'cliente' => 'Cliente ' . $i,
                'valor' => rand(100, 500),
            ];
        }
        return $notas;
    }

    // simula emissão direta (sem job)
    private function emitirNaSefazFake(array $nota)
    {
        // simula instabilidade: 30% de chance de erro
        if (rand(1, 10) <= 3) {
            // falha simulada
            throw new \Exception('Erro: SEFAZ fora do ar');
        }

        // simula latência de 500ms
        usleep(500 * 1000);

        return [
            'numero' => $nota['numero'],
            'status' => 'autorizada',
            'chave' => '351' . rand(10000000000000, 99999999999999),
        ];
    }

    // -------- CENÁRIO 1: sem job (sincrono) --------
    public function semJob(Request $request)
    {
        $inicio = microtime(true);
        $notas = $this->gerarNotasFake(20);
        $resultados = [];

        foreach ($notas as $nota) {
            try {
                $resultados[] = $this->emitirNaSefazFake($nota);
            } catch (\Exception $e) {
                $resultados[] = [
                    'numero' => $nota['numero'],
                    'status' => 'erro',
                    'mensagem' => $e->getMessage(),
                ];
            }
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio;

        return response()->json([
            'qtd_notas' => count($notas),
            'tempo_total_segundos' => round($duracao, 3),
            'resultados' => $resultados,
            'mensagem' => 'Processamento síncrono (sem job)',
        ]);
    }

    // -------- CENÁRIO 2: com job (assíncrono e resiliente) --------
    public function comJob(Request $request)
    {
        $inicio = microtime(true);
        $notas = $this->gerarNotasFake(20);

        foreach ($notas as $nota) {
            EmitirNotaFiscalJob::dispatch($nota);
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio;

        return response()->json([
            'qtd_notas_enfileiradas' => count($notas),
            'tempo_resposta_segundos' => round($duracao, 3),
            'mensagem' => 'Notas fiscais enfileiradas (com job e reenvio automático)',
        ]);
    }
}