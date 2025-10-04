<?php
require_once __DIR__ . '/../app/bootstrap.php';

$currentUser = current_user();
redirect($currentUser ? 'profile' : 'login');
