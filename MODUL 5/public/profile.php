<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'update_profile':
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if ($fullName === '' || $email === '') {
                add_flash('danger', 'Nama lengkap dan email wajib diisi.');
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                add_flash('danger', 'Format email tidak valid.');
            } elseif (update_profile((int) $user['id'], $fullName, $email)) {
                add_flash('success', 'Profil berhasil diperbarui.');
            } else {
                add_flash('danger', 'Profil gagal diperbarui.');
            }
            redirect('profile');
            break;
        case 'delete_account':
            if (delete_user((int) $user['id'])) {
                logout();
                add_flash('success', 'Akun Anda telah dihapus.');
                redirect('register');
            } else {
                add_flash('danger', 'Akun gagal dihapus.');
                redirect('profile');
            }
            break;
    }
}

render_header('Profil Saya', $user);
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div class="card card-shadow mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Detail Profil</h5>
                <span class="badge bg-secondary">Terakhir diperbarui: <?= sanitize($user['updated_at'] ?? $user['created_at']); ?></span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Username</dt>
                    <dd class="col-sm-8"><?= sanitize($user['username']); ?></dd>
                    <dt class="col-sm-4">Nama Lengkap</dt>
                    <dd class="col-sm-8"><?= sanitize($user['full_name']); ?></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= sanitize($user['email']); ?></dd>
                    <dt class="col-sm-4">Gabung Sejak</dt>
                    <dd class="col-sm-8"><?= sanitize($user['created_at']); ?></dd>
                </dl>
            </div>
        </div>

        <div class="card card-shadow mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Perbarui Profil</h5></div>
            <div class="card-body">
                <form method="post" action="<?= base_url('profile'); ?>" class="js-validate" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label class="form-label" for="profileFullName">Nama Lengkap</label>
                        <input type="text" class="form-control" id="profileFullName" name="full_name" required value="<?= sanitize($user['full_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profileEmail">Email</label>
                        <input type="email" class="form-control" id="profileEmail" name="email" required value="<?= sanitize($user['email']); ?>">
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-shadow border-danger">
            <div class="card-header bg-danger text-white"><h5 class="mb-0">Hapus Akun</h5></div>
            <div class="card-body">
                <p class="mb-3">Tindakan ini akan menghapus akun dan seluruh data Anda secara permanen.</p>
                <form method="post" action="<?= base_url('profile'); ?>" onsubmit="return confirm('Yakin ingin menghapus akun secara permanen?');">
                    <input type="hidden" name="action" value="delete_account">
                    <button type="submit" class="btn btn-danger">Hapus Akun Saya</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
