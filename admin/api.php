<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/admin.php';

$adminSession->requireLogin();

$adminUser = $adminSession->user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API | Central de Aulas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h1>Central de Aulas</h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="admins.php">Admins</a>
            <a class="active" href="api.php">API</a>
            <a href="logout.php">Sair</a>
        </nav>
    </aside>
    <main class="content">
        <div class="topbar">
            <h2>Integração da API</h2>
            <span><?= htmlspecialchars($adminUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <section class="panel">
            <div class="panel-header"><h3>Testar API</h3></div>
            <div class="panel-body">
                <div class="form-grid">
                    <p>
                        <label for="test_name">Nome</label>
                        <input id="test_name" value="Aluno Teste">
                    </p>
                    <p>
                        <label for="test_email">E-mail</label>
                        <input id="test_email" type="email" value="aluno.teste@example.com">
                    </p>
                    <p>
                        <label for="test_phone">Telefone</label>
                        <input id="test_phone" value="11999999999">
                    </p>
                    <p>
                        <label for="test_password">Senha</label>
                        <input id="test_password" placeholder="Preenchida ao gerar chave">
                    </p>
                    <p class="full">
                        <label for="test_token">Token CRUD</label>
                        <input id="test_token" value="centraldeaulas-local-api-token">
                    </p>
                </div>

                <div class="actions">
                    <button class="button" type="button" data-api-test="register">Gerar chave</button>
                    <button class="button secondary" type="button" data-api-test="verify">Validar chave</button>
                    <button class="button secondary" type="button" data-api-test="users">Listar leads</button>
                    <button class="button secondary" type="button" data-api-test="keys">Listar chaves</button>
                </div>

                <pre id="api_result" class="code result">Clique em um teste para ver a resposta JSON real.</pre>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>Gerar ou Reutilizar Chave</h3></div>
            <div class="panel-body">
                <div class="code">POST /centraldeaulas/api/register.php
Content-Type: application/json

{
  "name": "Nome do aluno",
  "email": "aluno@email.com",
  "phone": "11999999999",
  "client_context": {
    "browser": "Chrome",
    "screen_width": 1440,
    "screen_height": 900,
    "timezone": "America/Sao_Paulo",
    "language": "pt-BR",
    "latitude": -23.55052,
    "longitude": -46.63331
  }
}</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>Validar Chave</h3></div>
            <div class="panel-body">
                <div class="code">POST /centraldeaulas/api/verify.php
Content-Type: application/json

{
  "phone": "11999999999",
  "password": "12345678"
}</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>CORS</h3></div>
            <div class="panel-body">
                <div class="code">API_CORS_ALLOWED_ORIGINS=*
API_CORS_ALLOWED_METHODS=GET,POST,PUT,PATCH,DELETE,OPTIONS
API_CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With</div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>CRUD</h3></div>
            <div class="panel-body">
                <div class="code">X-API-Key: centraldeaulas-local-api-token
Authorization: Bearer centraldeaulas-local-api-token

GET    /centraldeaulas/api/users.php
GET    /centraldeaulas/api/users.php?id=1
POST   /centraldeaulas/api/users.php
PUT    /centraldeaulas/api/users.php?id=1
PATCH  /centraldeaulas/api/users.php?id=1
DELETE /centraldeaulas/api/users.php?id=1

GET    /centraldeaulas/api/access-passwords.php
GET    /centraldeaulas/api/access-passwords.php?id=1
POST   /centraldeaulas/api/access-passwords.php
PUT    /centraldeaulas/api/access-passwords.php?id=1
PATCH  /centraldeaulas/api/access-passwords.php?id=1
DELETE /centraldeaulas/api/access-passwords.php?id=1</div>
            </div>
        </section>
    </main>
</div>
<script>
const result = document.querySelector('#api_result');
const fields = {
    name: document.querySelector('#test_name'),
    email: document.querySelector('#test_email'),
    phone: document.querySelector('#test_phone'),
    password: document.querySelector('#test_password'),
    token: document.querySelector('#test_token')
};

function printResult(payload) {
    result.textContent = JSON.stringify(payload, null, 2);
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, options);
    const payload = await response.json();

    return {
        http_status: response.status,
        response: payload
    };
}

document.querySelectorAll('[data-api-test]').forEach((button) => {
    button.addEventListener('click', async () => {
        result.textContent = 'Testando...';

        try {
            const action = button.dataset.apiTest;
            let output;

            if (action === 'register') {
                output = await requestJson('../api/register.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        name: fields.name.value,
                        email: fields.email.value,
                        phone: fields.phone.value,
                        client_context: {
                            browser: 'Browser',
                            user_agent: navigator.userAgent,
                            screen_width: screen.width,
                            screen_height: screen.height,
                            viewport_width: window.innerWidth,
                            viewport_height: window.innerHeight,
                            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                            language: navigator.language,
                            platform: navigator.platform,
                            cookies_enabled: navigator.cookieEnabled
                        }
                    })
                });

                if (output.response?.data?.password) {
                    fields.password.value = output.response.data.password;
                }
            }

            if (action === 'verify') {
                output = await requestJson('../api/verify.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        phone: fields.phone.value,
                        password: fields.password.value
                    })
                });
            }

            if (action === 'users') {
                output = await requestJson('../api/users.php?page=1&per_page=5', {
                    headers: {'X-API-Key': fields.token.value}
                });
            }

            if (action === 'keys') {
                output = await requestJson('../api/access-passwords.php?page=1&per_page=5', {
                    headers: {'X-API-Key': fields.token.value}
                });
            }

            printResult(output);
        } catch (error) {
            printResult({
                status: 'error',
                message: error.message
            });
        }
    });
});
</script>
</body>
</html>
