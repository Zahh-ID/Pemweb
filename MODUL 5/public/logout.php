<?php
require_once __DIR__ . '/../app/bootstrap.php';

logout();
add_flash('success', 'Anda sudah logout.');
redirect('login');
