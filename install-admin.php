<?php require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
$err = '';
$done = false;
if ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='Admin'")->fetchColumn() > 0) {
    http_response_code(403);
    exit('Admin sudah ada. Hapus file install-admin.php dari server.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $nama = normalize_text($_POST['nama'] ?? '');
        $username = normalize_text($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (!$nama || !$username || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) throw new RuntimeException('Lengkapi semua data. Kata sandi minimal 8 karakter.');
        $s = $pdo->prepare("INSERT INTO users(nama,username,email,password_hash,role) VALUES(?,?,?,?, 'Admin')");
        $s->execute([$nama, $username, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $done = true;
    } catch (Throwable $e) {
        $err = $e instanceof RuntimeException ? $e->getMessage() : 'Username/email mungkin sudah digunakan.';
    }
}
page_start('Instal Admin', true); ?><div class="container py-5">
    <div class="sp-card mx-auto" style="max-width:560px">
        <h1 class="sp-auth-title">Buat Admin pertama</h1>
        <p class="sp-muted">Skrip ini hanya bisa digunakan sebelum ada akun Admin. Hapus file ini setelah berhasil.</p><?php if ($done): ?><div class="alert alert-success">Admin berhasil dibuat. Demi keamanan, hapus file <code>install-admin.php</code> sekarang.</div><a class="btn sp-btn-primary" href="<?= e(app_url('login.php')) ?>">Ke halaman masuk</a><?php else: if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" required maxlength="100"></div>
                <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required maxlength="50"></div>
                <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required maxlength="100"></div>
                <div class="mb-4"><label class="form-label">Kata sandi (minimal 8 karakter)</label><input class="form-control" type="password" name="password" minlength="8" required></div><button class="btn sp-btn-primary">Buat Admin</button>
            </form><?php endif; ?>
    </div>
</div><?php page_end(true); ?>