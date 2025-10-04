# Modul 5 Login & Profile App

A minimal PHP web application for Modul 5 Praktikum Pemrograman Web. The app implements user authentication, profile management, cookie-based auto login, and account deletion with a very compact page layout.

---

## 1. Struktur Direktori

```
modul5/
├── app/
│   └── bootstrap.php         # Inti konfigurasi, helper, dan layout sederhana
├── db/
│   └── database.sql          # Skema database MySQL (tabel users dan items)
├── public/
│   ├── index.php             # Redirect awal menuju login atau profil
│   ├── login.php             # Form login + remember me cookie
│   ├── register.php          # Form pendaftaran pengguna baru
│   ├── forgot.php            # Reset password sederhana berdasarkan username
│   ├── profile.php           # Detail profil, update data, dan hapus akun
│   └── logout.php            # Mengakhiri sesi dan cookie
├── .htaccess                 # Pretty URL: /login, /profile, dst.
└── README.md                 # Dokumentasi proyek
```

> Pastikan virtual host atau document root mengarah ke folder proyek ini; `.htaccess` akan melanjutkan request ke `public/` secara otomatis.

---

## 2. Konfigurasi dan Helper (`app/bootstrap.php`)

File ini _wajib_ disertakan di setiap page melalui `require_once __DIR__ . '/../app/bootstrap.php';`. Berikut pemecahan fungsi-fungsi penting di dalamnya:

### 2.1. Konstanta & Utilitas Dasar
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` — kredensial MySQL (ubah sesuai server Anda).
- `REMEMBER_COOKIE_NAME`, `REMEMBER_COOKIE_LIFETIME` — nama dan lama waktu cookie untuk fitur auto login.
- `base_path()` — mendeteksi path dasar aplikasi dan memotong suffix `/public` jika ada.
- `base_url($path)` — membuat URL relatif terhadap root aplikasi.
- `redirect($path)` — mengirim header `Location` dan menghentikan eksekusi.
- `sanitize($value)` — melindungi output HTML dari XSS dengan `htmlspecialchars`.

### 2.2. Database & Data User
- `db()` — membuat koneksi `mysqli` tunggal (singleton) dengan charset `utf8mb4`.
- `find_user_by_username($username)` — mengambil satu baris user berdasar username.
- `find_user_by_id($id)` — mengambil user berdasarkan ID.
- `create_user($username, $email, $fullName, $password)` — registrasi user baru dengan `password_hash`.
- `update_profile($userId, $fullName, $email)` — menyimpan perubahan profil.
- `update_password($userId, $newPassword)` — menyetel password baru (terpakai oleh reset password dan edit password jika diperlukan).
- `verify_user_password($userId, $password)` — memverifikasi password dengan `password_verify`.
- `random_simple_password()` — generator password 8 karakter hex sederhana.
- `reset_password_by_username($username, &$generatedPassword)` — mengatur password acak dan mengembalikannya melalui variabel referensi.
- `delete_user($userId)` — menghapus user beserta catatan terkait (karena foreign key).

### 2.3. Otentikasi & Session
- `complete_login($userId)` — menyimpan user ID ke `$_SESSION`.
- `persist_login_cookie($userId)` — membuat token acak, menyimpannya di DB (`remember_token`), lalu men-set cookie.<br>
- `clear_login_cookie()` — menghapus cookie dan mengosongkan token di DB bila masih ada.
- `attempt_login($username, $password, $remember)` — proses login utama (memanggil helper di atas).
- `try_auto_login_from_cookie()` — dipanggil awal file untuk auto login jika cookie valid.
- `logout()` — menghapus cookie, mengosongkan session, dan meregenerasi ID session.
- `current_user()` — helper pengambilan data user aktif (atau `null`).
- `require_login()` — redirect ke `login` jika belum autentikasi.

### 2.4. Flash Message
- `add_flash($type, $message)` — menambahkan pesan (misal `success`, `danger`) ke session.
- `flash_messages()` — mengambil seluruh pesan dan menghapusnya dari session.

### 2.5. Layout Minimal
- `render_header($title, ?array $currentUser)` — mencetak elemen `<html>` dasar, memuat Bootstrap, menampilkan flash message.
- `render_footer()` — menutup container + memuat skrip jQuery, Bootstrap, dan jQuery Validation.

> Kedua fungsi layout di atas dipakai di setiap halaman sehingga tampilan konsisten dan tetap simpel.

### 2.6. Fungsi CRUD Catatan
Fitur catatan (CRUD) dipertahankan pada helper ini untuk kompatibilitas masa depan:
- `fetch_items($userId)` — mengambil semua catatan pengguna.
- `find_item($userId, $id)`, `create_item(...)`, `update_item(...)`, `delete_item(...)` — helper CRUD catatan.

Walau halaman catatan sudah dihilangkan, fungsi tetap ada bila dibutuhkan lagi.

---

## 3. Halaman Publik (`public/`)

Semua halaman memanggil `render_header()` dan `render_footer()` untuk membungkus konten.

### 3.1. `index.php`
- Menentukan landing page sederhana: jika user login diarahkan ke `/profile`, jika tidak ke `/login`.

### 3.2. `login.php`
- Form login dengan opsi “ingat saya” (remember me cookie).
- Saat POST: validasi input, memanggil `attempt_login`, dan memberi flash message.

### 3.3. `register.php`
- Form pendaftaran pengguna baru.
- Validasi: semua field wajib, email valid, password ≥ 6 karakter, konfirmasi password cocok, serta username unik.
- Sukses → redirect ke login; gagal → tampil pesan flash.

### 3.4. `forgot.php`
- Reset password sederhana: cukup memasukkan username.
- Jika username ditemukan, app membuat password baru & menampilkannya lewat flash.

### 3.5. `profile.php`
- Hanya bisa diakses setelah login (`require_login`).
- Menampilkan detail profil (username, nama, email, tanggal buat/update).
- Form update nama/email.
- Tombol “Hapus Akun” yang memanggil `delete_user` lalu logout otomatis.

### 3.6. `logout.php`
- Membersihkan session dan cookie, lalu redirect ke login dengan pesan flash.

---

## 4. Pretty URL & Routing

File `.htaccess` (Apache) memetakan URL human-friendly ke file dalam `public/`:

```
RewriteRule ^$ public/index.php [L]
RewriteRule ^([a-zA-Z0-9_-]+)/?$ public/$1.php [QSA,L]
```

Dengan demikian, akses dilakukan melalui:
- `http://localhost/PemrogramanWeb/modul5/` → `public/index.php`
- `http://localhost/PemrogramanWeb/modul5/login` → `public/login.php`
- dst.

---

## 5. Database

Gunakan `db/database.sql` untuk membuat tabel:
- `users` — menyimpan profil, hash password, dan `remember_token`.
- `items` — contoh tabel catatan terkait user (dengan foreign key cascade delete).

Langkah singkat:
1. Masuk ke MySQL (`mysql -u root`) atau gunakan phpMyAdmin.
2. Buat database `modul5` (atau sesuaikan dengan konstanta `DB_NAME`).
3. Import `db/database.sql`.

---

## 6. Menjalankan Aplikasi

1. Pastikan PHP + MySQL + Apache (misal XAMPP) aktif.
2. Tempatkan proyek di `htdocs/PemrogramanWeb/modul5` atau direktori yang diinginkan.
3. Arahkan browser ke `http://localhost/PemrogramanWeb/modul5/`.
4. Buat akun baru, login, cek detail profil, ubah data, dan uji fitur hapus akun.

---

## 7. Catatan Tambahan

- Semua halaman sudah memakai validasi sisi klien via jQuery Validation (wajib memuat JS dari `render_footer`).
- Seluruh password tersimpan aman menggunakan `password_hash()`.
- Cookie remember me memakai token acak (disimpan hash-nya di database) untuk keamanan.
- Meskipun fungsi CRUD catatan tersedia, tampilan catatan sengaja tidak disertakan sesuai permintaan terbaru.

Silakan adaptasi atau perluas sesuai kebutuhan praktikum Anda.
