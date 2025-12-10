# Laravel Jobs e Filas - TCC

Este projeto demonstra a diferença de performance e resiliência entre processar tarefas **de forma síncrona (sem jobs)** vs **assíncrona (com jobs e filas)** em aplicações Laravel.

## 📋 Pré-requisitos

Certifique-se de ter instalado:

-   **PHP 8.2+**
-   **Composer** (gerenciador de dependências PHP)
-   **SQLite** (já vem com PHP, nenhuma configuração adicional necessária)

### Verificando instalações

```bash
php -v        # Deve mostrar PHP 8.2 ou superior
composer -V   # Deve mostrar Composer instalado
```

## 🚀 Configuração Inicial

### 1. Clone o repositório

```bash
git clone https://github.com/thiago8844/laravel-jobs-tcc.git
cd laravel-jobs-tcc
```

### 2. Instale as dependências

```bash
composer install
```

### 3. Configure o arquivo .env

```bash
cp .env.example .env
php artisan key:generate
```

O arquivo `.env` já está configurado para usar **SQLite** e **QUEUE_CONNECTION=database**.

### 4. Crie o banco de dados e execute as migrations

```bash
touch database/database.sqlite
php artisan migrate
```

Isso criará as tabelas necessárias, incluindo a tabela `jobs` para gerenciar a fila.

### 5. Inicie o servidor de desenvolvimento

```bash
php artisan serve
```

O servidor estará rodando em: **http://localhost:8000**

---

## 📚 Estrutura do Projeto

### Jobs (Tarefas Assíncronas)

-   **`app/Jobs/ProcessarBoletoJob.php`** - Processa boletos bancários (simula 200ms por boleto)
-   **`app/Jobs/EnviarEmailJob.php`** - Envia e-mails (simula 300ms por e-mail)
-   **`app/Jobs/EmitirNotaFiscalJob.php`** - Emite notas fiscais com retry automático (simula 500ms + 30% de falha)

### Controllers

-   **`app/Http/Controllers/TesteBoletoController.php`** - Testa processamento de 100 boletos
-   **`app/Http/Controllers/TesteEmailController.php`** - Testa envio de 50 e-mails
-   **`app/Http/Controllers/TesteNotaFiscalController.php`** - Testa emissão de 20 notas fiscais

---

## 🧪 Como Testar - Passo a Passo

Existem **2 formas** de processar cada tarefa:

1. **SEM JOB (Síncrono)** - Tudo processa na mesma requisição (lento, bloqueia o usuário)
2. **COM JOB (Assíncrono)** - Enfileira as tarefas e processa em background (rápido, não bloqueia)

---

## 🔴 Cenário 1: Processamento de Boletos

### Testando SEM JOB (Forma Síncrona)

```bash
curl -X POST http://localhost:8000/api/testar-boletos-sem-job
```

**O que acontece:**

-   Processa 100 boletos **diretamente na requisição**
-   Cada boleto demora 200ms
-   **Tempo total esperado: ~20 segundos**
-   O usuário fica esperando até tudo terminar

**Resposta esperada:**

```json
{
    "qtd_boletos": 100,
    "tempo_total_segundos": 20.1234,
    "mensagem": "Processado tudo dentro da mesma request (sem job)"
}
```

---

### Testando COM JOB (Forma Assíncrona)

#### Passo 1: Enviar a requisição

```bash
curl -X POST http://localhost:8000/api/testar-boletos-com-job
```

**O que acontece:**

-   Os 100 boletos são **enfileirados** no banco de dados (tabela `jobs`)
-   A requisição retorna **instantaneamente** (~0.1 segundo)
-   Nada é processado ainda!

**Resposta esperada:**

```json
{
    "qtd_boletos_enfileirados": 100,
    "tempo_resposta_segundos": 0.0854,
    "mensagem": "Boletos enviados para a fila (com job). O processamento vai ocorrer pelo worker."
}
```

#### Passo 2: Iniciar o Queue Worker (Processador de Fila)

Abra um **novo terminal** e execute:

```bash
php artisan queue:work --queue=default
```

**O que acontece:**

-   O worker começa a **processar os jobs da fila** um por um
-   Você verá logs no terminal mostrando cada boleto sendo processado
-   O processamento acontece em **background**, sem bloquear o servidor

**Output esperado no terminal:**

```
[2025-12-10 10:30:15] Processing: App\Jobs\ProcessarBoletoJob
[2025-12-10 10:30:15] Processed:  App\Jobs\ProcessarBoletoJob
[2025-12-10 10:30:16] Processing: App\Jobs\ProcessarBoletoJob
[2025-12-10 10:30:16] Processed:  App\Jobs\ProcessarBoletoJob
...
```

**Para parar o worker:** Pressione `Ctrl+C`

---

## 📧 Cenário 2: Envio de E-mails

### Testando SEM JOB

```bash
curl http://localhost:8000/api/teste-email/sem-job
```

**O que acontece:**

-   Envia 50 e-mails **sincronamente**
-   Cada e-mail demora 300ms
-   **Tempo total esperado: ~15 segundos**

**Resposta esperada:**

```json
{
    "qtd_emails": 50,
    "tempo_total_segundos": 15.234,
    "mensagem": "Envio realizado de forma síncrona (sem job)"
}
```

---

### Testando COM JOB

#### Passo 1: Enfileirar os e-mails

```bash
curl http://localhost:8000/api/teste-email/com-job
```

**Resposta esperada:**

```json
{
    "qtd_emails_enfileirados": 50,
    "tempo_resposta_segundos": 0.042,
    "mensagem": "E-mails enfileirados para envio em background (com job)"
}
```

#### Passo 2: Processar com o worker

```bash
php artisan queue:work
```

**Observação:** O worker processa os jobs em background. Você pode fechar a requisição e os e-mails continuarão sendo enviados.

---

## 📄 Cenário 3: Emissão de Notas Fiscais (com Retry)

Este cenário é especial porque **simula falhas** (30% de chance de erro) para demonstrar o **retry automático** dos jobs.

### Testando SEM JOB

```bash
curl http://localhost:8000/api/teste-nota/sem-job
```

**O que acontece:**

-   Tenta emitir 20 notas fiscais **sincronamente**
-   Algumas falharão (erro simulado da SEFAZ)
-   **Notas que falharem NÃO serão reprocessadas**
-   Tempo total: ~10 segundos

**Resposta esperada:**

```json
{
    "qtd_notas": 20,
    "tempo_total_segundos": 10.123,
    "resultados": [
        {
            "numero": "NF-0000",
            "status": "autorizada",
            "chave": "35112345678901234567890123456789012345678901234567"
        },
        {
            "numero": "NF-0001",
            "status": "erro",
            "mensagem": "Erro: SEFAZ fora do ar"
        }
    ],
    "mensagem": "Processamento síncrono (sem job)"
}
```

---

### Testando COM JOB (com Retry Automático)

#### Passo 1: Enfileirar as notas fiscais

```bash
curl http://localhost:8000/api/teste-nota/com-job
```

**Resposta esperada:**

```json
{
    "qtd_notas_enfileiradas": 20,
    "tempo_resposta_segundos": 0.038,
    "mensagem": "Notas fiscais enfileiradas (com job e reenvio automático)"
}
```

#### Passo 2: Processar com o worker

```bash
php artisan queue:work
```

**O que acontece de especial:**

-   Jobs que **falharem** serão **automaticamente reprocessados** até 3 vezes
-   O worker espera 5 segundos antes de tentar novamente
-   Você verá no terminal quando um job falha e é reenfileirado

**Output esperado no terminal:**

```
[2025-12-10 10:35:10] Processing: App\Jobs\EmitirNotaFiscalJob
[2025-12-10 10:35:11] Processed:  App\Jobs\EmitirNotaFiscalJob
[2025-12-10 10:35:11] Processing: App\Jobs\EmitirNotaFiscalJob
[2025-12-10 10:35:11] Failed:     App\Jobs\EmitirNotaFiscalJob
[2025-12-10 10:35:16] Processing: App\Jobs\EmitirNotaFiscalJob (Retry 1 of 3)
[2025-12-10 10:35:16] Processed:  App\Jobs\EmitirNotaFiscalJob
...
```

---

## 📊 Resumo das Rotas

| Rota                          | Método | Descrição                      | Quantidade  | Tempo Esperado (sem job) |
| ----------------------------- | ------ | ------------------------------ | ----------- | ------------------------ |
| `/api/testar-boletos-sem-job` | POST   | Processa boletos sincronamente | 100 boletos | ~20s                     |
| `/api/testar-boletos-com-job` | POST   | Enfileira boletos              | 100 boletos | ~0.1s                    |
| `/api/teste-email/sem-job`    | GET    | Envia e-mails sincronamente    | 50 e-mails  | ~15s                     |
| `/api/teste-email/com-job`    | GET    | Enfileira e-mails              | 50 e-mails  | ~0.05s                   |
| `/api/teste-nota/sem-job`     | GET    | Emite notas sincronamente      | 20 notas    | ~10s                     |
| `/api/teste-nota/com-job`     | GET    | Enfileira notas fiscais        | 20 notas    | ~0.04s                   |

---

## 🔧 Comandos Úteis do Queue Worker

### Processar jobs continuamente

```bash
php artisan queue:work
```

### Processar apenas 1 job e parar

```bash
php artisan queue:work --once
```

### Processar jobs de uma fila específica

```bash
php artisan queue:work --queue=emails
```

### Ver jobs falhados

```bash
php artisan queue:failed
```

### Reprocessar todos os jobs falhados

```bash
php artisan queue:retry all
```

### Limpar todos os jobs falhados

```bash
php artisan queue:flush
```

### Monitorar a fila em tempo real (com verbosidade)

```bash
php artisan queue:work --verbose
```

---

## 📝 Observações Importantes

### 1. Worker em Produção

Em produção, você deve usar **Supervisor** ou **systemd** para manter o worker rodando:

```bash
# Exemplo de configuração do Supervisor
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /caminho/do/projeto/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
```

### 2. Configuração da Fila

O projeto usa **database** como driver de fila (configurado em `.env`):

```env
QUEUE_CONNECTION=database
```

Outras opções disponíveis:

-   `sync` - Processamento síncrono (sem fila, para testes)
-   `redis` - Requer Redis instalado (mais rápido)
-   `sqs` - Amazon SQS (para ambientes AWS)

### 3. Testando com Postman ou Insomnia

Você pode usar ferramentas como **Postman** ou **Insomnia** para testar as rotas:

#### Exemplo para Boletos SEM JOB:

-   **Método:** POST
-   **URL:** http://localhost:8000/api/testar-boletos-sem-job
-   **Headers:** `Content-Type: application/json`
-   **Body:** (vazio)

#### Exemplo para E-mails COM JOB:

-   **Método:** GET
-   **URL:** http://localhost:8000/api/teste-email/com-job

---

## 🎯 Benefícios Demonstrados

### ✅ Processamento Síncrono (SEM JOB)

-   ❌ Resposta lenta para o usuário
-   ❌ Timeout em processos longos
-   ❌ Sem retry automático
-   ✅ Mais simples de implementar

### ✅ Processamento Assíncrono (COM JOB)

-   ✅ Resposta instantânea para o usuário
-   ✅ Processa em background
-   ✅ Retry automático em caso de falha
-   ✅ Escalável (pode rodar múltiplos workers)
-   ✅ Resiliência (jobs não se perdem se o servidor cair)

---

## 🐛 Troubleshooting

### Jobs não estão sendo processados

1. Verifique se o worker está rodando:

```bash
php artisan queue:work
```

2. Verifique se há jobs na fila:

```bash
php artisan queue:work --once
```

### Erro "Class not found"

Execute:

```bash
composer dump-autoload
```

### Database locked (SQLite)

Pare o worker e tente novamente:

```bash
# Pressione Ctrl+C no terminal do worker
# Execute novamente
php artisan queue:work
```

---

## 📚 Documentação Adicional

-   [Laravel Queues Documentation](https://laravel.com/docs/11.x/queues)
-   [Laravel Jobs Documentation](https://laravel.com/docs/11.x/queues#creating-jobs)

---

## 👨‍💻 Autor

**Thiago** - TCC sobre Laravel Jobs e Filas

## 📄 Licença

Este projeto é open-source e está disponível para fins educacionais.

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
