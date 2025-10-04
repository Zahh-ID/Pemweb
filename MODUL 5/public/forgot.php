<?php
require_once __DIR__ . '/../app/bootstrap.php';

if (current_user()) {
    redirect('profile');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username === '') {
        add_flash('danger', 'Username wajib diisi.');
    } elseif (reset_password_by_username($username, $newPassword)) {
        add_flash('success', 'Password baru: ' . sanitize($newPassword));
        redirect('login');
    } else {
        add_flash('danger', 'Username tidak ditemukan.');
    }
    redirect('forgot');
}

render_header('Lupa Password', null);
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-shadow">
            <div class="card-header bg-white"><h5 class="mb-0">Lupa Password</h5></div>
            <div class="card-body">
                <p class="text-muted small">Masukkan username. Sistem akan membuat password baru otomatis lalu menampilkannya di sini.</p>
                <form method="post" action="<?= base_url('forgot'); ?>" class="js-validate" novalidate>
                    <div class="mb-3">
                        <label for="forgotUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="forgotUsername" name="username" required minlength="3">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-white text-center">
                <small><a href="<?= base_url('login'); ?>">Kembali ke Login</a></small>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
