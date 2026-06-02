<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/admin.php';

$adminSession->requireLogin();

$message = null;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$adminSession->validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } else {
        try {
            $adminUserService->create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['password'] ?? '')
            );
            $message = 'Administrador criado com sucesso.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$admins = $adminUserService->all();
$adminUser = $adminSession->user();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admins | Central de Aulas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h1>Central de Aulas</h1>
        <nav>
            <a href="index.php">Dashboard</a>
            <a class="active" href="admins.php">Admins</a>
            <a href="api.php">API</a>
            <a href="logout.php">Sair</a>
        </nav>
    </aside>
    <main class="content">
        <div class="topbar">
            <h2>Administradores</h2>
            <span><?= htmlspecialchars($adminUser['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <?php if ($message !== null): ?>
            <div class="notice success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-header"><h3>Criar Novo Admin</h3></div>
            <div class="panel-body">
                <form method="post" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminSession->csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <p>
                        <label for="name">Nome</label>
                        <input id="name" name="name" required maxlength="150">
                    </p>
                    <p>
                        <label for="email">E-mail</label>
                        <input id="email" name="email" type="email" required maxlength="190">
                    </p>
                    <p>
                        <label for="password">Senha</label>
                        <input id="password" name="password" type="password" required minlength="8">
                    </p>
                    <p class="full">
                        <button class="button" type="submit">Criar admin</button>
                    </p>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>Admins Cadastrados</h3></div>
            <div class="panel-body">
                <table>
                    <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Ultimo login</th><th>Criado</th><th>Ativo</th></tr></thead>
                    <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= (int) $admin['id'] ?></td>
                            <td><?= htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($admin['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $admin['last_login_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($admin['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $admin['is_active'] === 1 ? 'Sim' : 'Nao' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
