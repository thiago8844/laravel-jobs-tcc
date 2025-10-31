<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarEmailJob;
use Illuminate\Http\Request;

class TesteEmailController extends Controller
{
    // simula uma lista de e-mails de usuários recém-cadastrados
    private function gerarUsuariosFake($qtd = 50)
    {
        $usuarios = [];
        for ($i = 0; $i < $qtd; $i++) {
            $usuarios[] = [
                'email' => "usuario{$i}@teste.com",
                'nome'  => "Usuário {$i}"
            ];
        }
        return $usuarios;
    }

    // simula envio de e-mail (lento)
    private function enviarEmailFake(array $usuario)
    {
        // simula 300ms de latência
        usleep(300 * 1000);

        // retorno fake
        return [
            'email' => $usuario['email'],
            'status' => 'enviado'
        ];
    }

    // --------- CENÁRIO 1: sem job ---------
    public function semJob(Request $request)
    {
        $inicio = microtime(true);

        $usuarios = $this->gerarUsuariosFake(50);

        $resultados = [];
        foreach ($usuarios as $usuario) {
            $resultados[] = $this->enviarEmailFake($usuario);
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio;

        return response()->json([
            'qtd_emails' => count($usuarios),
            'tempo_total_segundos' => round($duracao, 3),
            'mensagem' => 'Envio realizado de forma síncrona (sem job)'
        ]);
    }

    // --------- CENÁRIO 2: com job ---------
    public function comJob(Request $request)
    {
        $inicio = microtime(true);

        $usuarios = $this->gerarUsuariosFake(50);

        foreach ($usuarios as $usuario) {
            EnviarEmailJob::dispatch($usuario);
        }

        $fim = microtime(true);
        $duracao = $fim - $inicio;

        return response()->json([
            'qtd_emails_enfileirados' => count($usuarios),
            'tempo_resposta_segundos' => round($duracao, 3),
            'mensagem' => 'E-mails enfileirados para envio em background (com job)'
        ]);
    }
}
