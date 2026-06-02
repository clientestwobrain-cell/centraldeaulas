<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap/admin.php';

$adminSession->requireLogin();

$message = null;
$error = null;
$generatedAccess = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$adminSession->validateCsrf($_POST['csrf_token'] ?? null)) {
        $error = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } else {
        try {
            $generatedAccess = $accessPasswordService->registerOrReuseAccessPassword(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                (string) ($_POST['phone'] ?? ''),
                [
                    'browser' => 'admin',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]
            );
            $message = $generatedAccess['reused']
                ? 'Chave ativa reaproveitada para este telefone.'
                : 'Nova chave gerada com sucesso.';
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$stats = $adminDashboardService->stats();
$leadAccessRecords = $adminDashboardService->latestLeadAccessRecords();
$adminUser = $adminSession->user();
$date = static function (?string $value): string {
    return $value === null ? '-' : (new DateTimeImmutable($value))->format('d/m/Y');
};
$time = static function (?string $value): string {
    return $value === null ? '-' : (new DateTimeImmutable($value))->format('H:i:s');
};
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Central de Aulas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <h1>Central de Aulas</h1>
        <nav>
            <a class="active" href="index.php">Dashboard</a>
            <a href="admins.php">Admins</a>
            <a href="api.php">API</a>
            <a href="logout.php">Sair</a>
        </nav>
    </aside>
    <main class="content">
        <div class="topbar">
            <div>
                <h2>Ola, <?= htmlspecialchars($adminUser['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>!</h2>
                <p>Visao geral dos leads, chaves e acessos.</p>
            </div>
            <a class="button secondary" href="index.php">Atualizar</a>
        </div>

        <?php if ($message !== null): ?>
            <div class="notice success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error !== null): ?>
            <div class="notice error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="grid metrics">
            <div class="metric"><span>Leads</span><strong><?= $stats['users'] ?></strong></div>
            <div class="metric"><span>Chaves</span><strong><?= $stats['passwords'] ?></strong></div>
            <div class="metric"><span>Ativas</span><strong><?= $stats['active_passwords'] ?></strong></div>
            <div class="metric"><span>Expiradas</span><strong><?= $stats['expired_passwords'] ?></strong></div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>Gerar ou Reutilizar Chave</h3></div>
            <div class="panel-body">
                <?php if ($generatedAccess !== null): ?>
                    <div class="notice success">
                        Chave: <strong><?= htmlspecialchars($generatedAccess['password'], ENT_QUOTES, 'UTF-8') ?></strong>
                        | Expira em: <?= htmlspecialchars($generatedAccess['expires_at'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
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
                        <label for="phone">Telefone</label>
                        <input id="phone" name="phone" required maxlength="30">
                    </p>
                    <p class="full">
                        <button class="button" type="submit">Gerar chave</button>
                    </p>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header"><h3>Leads e Chaves</h3></div>
            <div class="panel-body">
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th>Lead ID</th>
                                <th>Chave ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Telefone</th>
                                <th>Data Cadastro</th>
                                <th>Hora Cadastro</th>
                                <th>Lead Ativo</th>
                                <th>Data Chave</th>
                                <th>Hora Chave</th>
                                <th>Data Expira</th>
                                <th>Hora Expira</th>
                                <th>Expirada</th>
                                <th>Chave Ativa</th>
                                <th>Navegador</th>
                                <th>Versao</th>
                                <th>IP</th>
                                <th>Cidade</th>
                                <th>Regiao</th>
                                <th>Pais</th>
                                <th>Timezone</th>
                                <th>Idioma</th>
                                <th>Dispositivo</th>
                                <th>Sistema</th>
                                <th>Tela</th>
                                <th>Viewport</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($leadAccessRecords as $record): ?>
                            <tr>
                                <td><?= (int) $record['user_id'] ?></td>
                                <td><?= $record['access_password_id'] === null ? '-' : (int) $record['access_password_id'] ?></td>
                                <td><?= htmlspecialchars($record['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($record['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) $record['phone'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($date($record['user_created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($time($record['user_created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= (int) $record['user_is_active'] === 1 ? 'Sim' : 'Nao' ?></td>
                                <td><?= htmlspecialchars($date($record['generated_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($time($record['generated_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($date($record['expires_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($time($record['expires_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $record['is_expired'] === null ? '-' : ((int) $record['is_expired'] === 1 ? 'Sim' : 'Nao') ?></td>
                                <td><?= $record['password_is_active'] === null ? '-' : ((int) $record['password_is_active'] === 1 ? 'Sim' : 'Nao') ?></td>
                                <td><?= htmlspecialchars((string) ($record['browser'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['browser_version'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['ip_address'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['ip_city'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['ip_region'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['ip_country_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['timezone'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['language'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['device_type'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['operating_system'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($record['screen_resolution'] ?? trim((string) ($record['screen_width'] ?? '') . 'x' . (string) ($record['screen_height'] ?? ''), 'x')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(trim((string) ($record['viewport_width'] ?? '') . 'x' . (string) ($record['viewport_height'] ?? ''), 'x') ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>
