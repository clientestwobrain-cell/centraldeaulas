<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/admin.php';

if ($adminSession->user() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$adminSession->validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } else {
        $admin = $adminUserService->authenticate((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));

        if ($admin === false) {
            $error = 'Usuario ou senha invalidos.';
        } else {
            $adminSession->login($admin);
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | Central de Aulas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <main class="login-box">
        <h1>Central de Aulas</h1>
        <p>Acesso administrativo</p>

        <?php if ($error !== null): ?>
            <div class="notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminSession->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <p>
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" required autocomplete="username">
            </p>
            <p>
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </p>
            <button class="button" type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
