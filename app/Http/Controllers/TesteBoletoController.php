<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessarBoletoJob;
use Illuminate\Http\Request;

class TesteBoletoController extends Controller
{
    // mock de 1000 boletos fake
    private function gerarBoletosFake($qtd = 100)
    {
        $boletos = [];
        for ($i = 0; $i < $qtd; $i++) {
            $boletos[] = [
                'nosso_numero' => 'FAKE-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'valor'        => 100.00,
                'vencimento'   => now()->addDays(5)->toDateString(),
                'sacado'       => 'Cliente Teste ' . $i,
            ];
        }
        return $boletos;
    }

    // simula chamada na API bancária (lenta)
    // IMPORTANTE: isso NÃO chama api real, só dorme pra simular latência
    private function registrarBoletoNaApiBancariaFake(array $boleto)
    {
        // simula 200ms de tempo de rede/processamento
        usleep(200 * 100); // 200ms
        // retorna uma resposta fake
        return [
            'status' => 'ok',
            'linha_digitavel' => '34191.79001...' . rand(1000, 9999),
        ];
    }

    // --------- CENÁRIO 1: sem job (processa tudo na request) ---------
    public function semJob(Request $request)
    {
        $inicio = microtime(true);

        $boletos = $this->gerarBoletosFake(100);

        $resultados = [];
        foreach ($boletos as $boleto) {
            $respostaApi = $this->registrarBoletoNaApiBancariaFake($boleto);
            $resultados[] = $respostaApi;
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio; // em segundos

        return response()->json([
            'qtd_boletos' => count($boletos),
            'tempo_total_segundos' => $duracao,
            'mensagem' => 'Processado tudo dentro da mesma request (sem job)',
        ]);
    }

    // --------- CENÁRIO 2: com job (fila) ---------
    public function comJob(Request $request)
    {
        $inicio = microtime(true);

        $boletos = $this->gerarBoletosFake(100);

        foreach ($boletos as $boleto) {
            // cada boleto vai ser processado depois, em background
            ProcessarBoletoJob::dispatch($boleto);
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio;

        return response()->json([
            'qtd_boletos_enfileirados' => count($boletos),
            'tempo_resposta_segundos' => $duracao,
            'mensagem' => 'Boletos enviados para a fila (com job). O processamento vai ocorrer pelo worker.',
        ]);
    }
}
