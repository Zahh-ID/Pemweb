<?php
session_start();

// === Konfigurasi database === //
const DB_HOST = 'localhost';
const DB_NAME = 'modul5';
const DB_USER = 'root';
const DB_PASS = '';

// === Konstanta aplikasi === //
const REMEMBER_COOKIE_NAME = 'modul5_remember';
const REMEMBER_COOKIE_LIFETIME = 60 * 60 * 24 * 7; // 7 hari

// === Utilitas dasar === //
function base_path(): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($scriptDir !== '') {
        $scriptDir = preg_replace('#/public$#', '', $scriptDir);
    }
    return $scriptDir === '' ? '/' : $scriptDir . '/';
}

function base_url(string $path = ''): string
{
    return base_path() . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function sanitize(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// === Koneksi database === //
function db(): mysqli
{
    static $connection;
    if (!$connection) {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($connection->connect_error) {
            throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
        }
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}

// === Helper user & auth === //
function find_user_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function create_user(string $username, string $email, string $fullName, string $password): bool
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $username, $email, $fullName, $hash);
    return $stmt->execute();
}

function update_profile(int $userId, string $fullName, string $email): bool
{
    $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('ssi', $fullName, $email, $userId);
    return $stmt->execute();
}

function update_password(int $userId, string $newPassword): bool
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $hash, $userId);
    return $stmt->execute();
}

function verify_user_password(int $userId, string $password): bool
{
    $user = find_user_by_id($userId);
    return $user ? password_verify($password, $user['password_hash']) : false;
}

function random_simple_password(): string
{
    return substr(bin2hex(random_bytes(4)), 0, 8);
}

function reset_password_by_username(string $username, string &$generatedPassword): bool
{
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }
    $generatedPassword = random_simple_password();
    return update_password((int) $user['id'], $generatedPassword);
}

function complete_login(int $userId): void
{
    $_SESSION['user_id'] = $userId;
}

function persist_login_cookie(int $userId): void
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('UPDATE users SET remember_token = ? WHERE id = ?');
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
    setcookie(REMEMBER_COOKIE_NAME, $userId . ':' . $token, time() + REMEMBER_COOKIE_LIFETIME, '/');
}

function clear_login_cookie(): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        [$userId] = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]) + [null];
        if ($userId) {
            $stmt = db()->prepare('UPDATE users SET remember_token = NULL WHERE id = ?');
            $id = (int) $userId;
            $stmt->bind_param('i', $id);
            $stmt->execute();
        }
        setcookie(REMEMBER_COOKIE_NAME, '', time() - 3600, '/');
    }
}

function attempt_login(string $username, string $password, bool $remember): bool
{
    $user = find_user_by_username($username);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }
    complete_login((int) $user['id']);
    if ($remember) {
        persist_login_cookie((int) $user['id']);
    } else {
        clear_login_cookie();
    }
    return true;
}

function try_auto_login_from_cookie(): void
{
    if (isset($_SESSION['user_id']) || empty($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return;
    }
    [$userId, $token] = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME]) + [null, null];
    if (!$userId || !$token) {
        return;
    }
    $user = find_user_by_id((int) $userId);
    if ($user && hash_equals($user['remember_token'] ?? '', hash('sha256', $token))) {
        complete_login((int) $user['id']);
    }
}

function logout(): void
{
    clear_login_cookie();
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function current_user(): ?array
{
    return empty($_SESSION['user_id']) ? null : find_user_by_id((int) $_SESSION['user_id']);
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect('login');
    }
}

// === Flash message === //
function add_flash(string $type, string $message): void
{
    $_SESSION['flash'][$type][] = $message;
}

function flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// === CRUD catatan === //
function fetch_items(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function find_item(int $userId, int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM items WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?: null;
}

function create_item(int $userId, string $title, string $description): bool
{
    $stmt = db()->prepare('INSERT INTO items (user_id, title, description) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $userId, $title, $description);
    return $stmt->execute();
}

function update_item(int $userId, int $id, string $title, string $description): bool
{
    $stmt = db()->prepare('UPDATE items SET title = ?, description = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ssii', $title, $description, $id, $userId);
    return $stmt->execute();
}

function delete_item(int $userId, int $id): bool
{
    $stmt = db()->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $id, $userId);
    return $stmt->execute();
}

function delete_user(int $userId): bool
{
    $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    return $stmt->execute();
}

// === Layout helper === //
function render_header(string $title, ?array $currentUser = null): void
{
    $messages = flash_messages();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?= sanitize($title); ?> - Modul 5</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
            body { background-color: #f5f5f5; }
            .card-shadow { box-shadow: 0 0.35rem 0.75rem rgba(0,0,0,0.08); }
            .is-invalid + .invalid-feedback { display: block; }
        </style>
    </head>
    <body>
    <div class="container py-4">
        <?php if (!empty($messages)): ?>
            <?php foreach ($messages as $type => $list): ?>
                <?php foreach ($list as $message): ?>
                    <div class="alert alert-<?= sanitize($type); ?> alert-dismissible fade show" role="alert">
                        <?= sanitize($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
    <script>
        $(function () {
            $('.js-validate').each(function () {
                $(this).validate({
                    errorClass: 'is-invalid',
                    validClass: 'is-valid',
                    errorElement: 'div',
                    errorPlacement: function (error, element) {
                        error.addClass('invalid-feedback');
                        if (element.parent('.input-group').length) {
                            error.insertAfter(element.parent());
                        } else {
                            error.insertAfter(element);
                        }
                    },
                    highlight: function (element) {
                        $(element).addClass('is-invalid').removeClass('is-valid');
                    },
                    unhighlight: function (element) {
                        $(element).removeClass('is-invalid').addClass('is-valid');
                    }
                });
            });
        });
    </script>
    </body>
    </html>
    <?php
}

try_auto_login_from_cookie();
