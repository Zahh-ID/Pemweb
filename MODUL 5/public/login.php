<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('profile');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);
    if ($username === '' || $password === '') {
        add_flash('danger', 'Username dan password wajib diisi.');
    } elseif (attempt_login($username, $password, $remember)) {
        add_flash('success', 'Login berhasil.');
        redirect('profile');
    } else {
        add_flash('danger', 'Kombinasi username dan password tidak sesuai.');
    }
    redirect('login');
}

render_header('Login', null);
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-shadow">
            <div class="card-header bg-white"><h5 class="mb-0">Masuk</h5></div>
            <div class="card-body">
                <form method="post" action="<?= base_url('login'); ?>" class="js-validate" novalidate>
                    <div class="mb-3">
                        <label for="loginUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="loginUsername" name="username" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="loginPassword" name="password" required minlength="6">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ingat saya (auto login)</label>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white text-center">
                <small>Belum punya akun? <a href="<?= base_url('register'); ?>">Daftar</a> · <a href="<?= base_url('forgot'); ?>">Lupa Password</a></small>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
