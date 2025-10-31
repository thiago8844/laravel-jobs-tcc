<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MockController extends Controller
{
  // Mock bancário: simula registro de boleto
  public function mockBanco(Request $request)
  {
    // Simula latência de 1 a 3 segundos
    sleep(rand(1, 3));
    // Simula sucesso ou erro aleatório
    $success = rand(0, 1) === 1;
    return response()->json([
      'status' => $success ? 'success' : 'error',
      'message' => $success ? 'Boleto registrado com sucesso (mock)' : 'Falha ao registrar boleto (mock)'
    ]);
  }

  // Mock de envio de e-mail
  public function mockEmail(Request $request)
  {
    // Simula latência de 0.5 a 2 segundos
    usleep(rand(500000, 2000000));
    // Simula log do envio
    Log::info('Mock: E-mail enviado para ' . $request->input('email'));
    return response()->json([
      'status' => 'success',
      'message' => 'E-mail simulado enviado para ' . $request->input('email')
    ]);
  }
}
