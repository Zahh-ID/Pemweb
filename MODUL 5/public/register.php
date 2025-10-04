<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('profile');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($username === '' || $fullName === '' || $email === '' || $password === '') {
        add_flash('danger', 'Semua form wajib diisi.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        add_flash('danger', 'Format email tidak valid.');
    } elseif (strlen($password) < 6) {
        add_flash('danger', 'Password minimal 6 karakter.');
    } elseif ($password !== $confirm) {
        add_flash('danger', 'Konfirmasi password tidak sesuai.');
    } elseif (find_user_by_username($username)) {
        add_flash('danger', 'Username sudah digunakan.');
    } elseif (create_user($username, $email, $fullName, $password)) {
        add_flash('success', 'Registrasi berhasil, silakan login.');
        redirect('login');
    } else {
        add_flash('danger', 'Registrasi gagal, silakan coba lagi.');
    }
    redirect('register');
}

render_header('Registrasi', null);
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-shadow">
            <div class="card-header bg-white"><h5 class="mb-0">Daftar Akun</h5></div>
            <div class="card-body">
                <form method="post" action="<?= base_url('register'); ?>" class="js-validate" novalidate>
                    <div class="mb-3">
                        <label for="registerUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="registerUsername" name="username" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label for="registerFullName" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="registerFullName" name="full_name" required minlength="3">
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="registerEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="registerPassword" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="registerPasswordConfirmation" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="registerPasswordConfirmation" name="password_confirmation" required data-rule-equalto="#registerPassword">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Daftar</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white text-center">
                <small>Sudah punya akun? <a href="<?= base_url('login'); ?>">Login</a></small>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
