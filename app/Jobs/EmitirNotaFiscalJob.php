<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmitirNotaFiscalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $nota;
    public $tries = 5; // Máx. de tentativas automáticas
    public $backoff = [5, 10, 15, 20, 25]; // tempos de espera progressivos (em segundos)

    public function __construct(array $nota)
    {
        $this->nota = $nota;
    }

    public function handle(): void
    {
        $inicio = microtime(true);

        try {
            // simula chance de instabilidade da SEFAZ
            if (rand(1, 10) <= 3) {
                throw new \Exception('Erro: SEFAZ fora do ar (simulado)');
            }

            // simula tempo de resposta médio de 500ms
            usleep(500 * 1000);

            // simula nota aprovada
            $fim = microtime(true);
            $duracao = round($fim - $inicio, 3);

            Log::info('Nota fiscal emitida com sucesso', [
                'numero' => $this->nota['numero'],
                'valor' => $this->nota['valor'],
                'duracao_segundos' => $duracao,
            ]);
        } catch (\Exception $e) {
            // lança exceção para o Laravel reagendar o Job
            $tentativa = $this->attempts();

            Log::warning('Falha na emissão da nota fiscal', [
                'numero' => $this->nota['numero'],
                'tentativa' => $tentativa,
                'mensagem' => $e->getMessage(),
            ]);

            // se ainda há tentativas restantes, relança o erro para o reenvio automático
            if ($tentativa < $this->tries) {
                throw $e;
            }

            // após todas as tentativas falharem, loga como erro final
            Log::error('Nota fiscal não emitida após várias tentativas', [
                'numero' => $this->nota['numero'],
                'mensagem' => $e->getMessage(),
            ]);
        }
    }
}
