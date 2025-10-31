<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessarBoletoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $boleto;

    public function __construct(array $boleto)
    {
        $this->boleto = $boleto;
    }

    public function handle(): void
    {
        // simula a mesma “API bancária lenta”
        usleep(200 * 1000); // 200ms

        // loga pra provar que ele rodou
        Log::info('Boleto processado em job', [
            'nosso_numero' => $this->boleto['nosso_numero'],
        ]);
    }
}
