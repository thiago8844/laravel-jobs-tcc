<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $usuario;

    public function __construct(array $usuario)
    {
        $this->usuario = $usuario;
    }

    public function handle(): void
    {
        // simula o tempo de envio do e-mail
        usleep(300 * 1000); // 300ms

        // registra log do envio simulado
        Log::info('E-mail enviado em job', [
            'email' => $this->usuario['email'],
            'nome'  => $this->usuario['nome']
        ]);
    }
}
