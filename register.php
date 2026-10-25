<?php require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
if (!empty($_SESSION['user'])) redirect(app_url($_SESSION['user']['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $nama = normalize_text($_POST['nama'] ?? '');
        $username = normalize_text($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($nama === '' || mb_strlen($nama) > 100 || $username === '' || mb_strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100 || strlen($pass) < 8) throw new RuntimeException('Periksa nama, username, email, dan kata sandi (minimal 8 karakter).');
        $s = $pdo->prepare('INSERT INTO users(nama,username,email,password_hash,role) VALUES(?,?,?,?,?)');
        $s->execute([$nama, $username, $email, password_hash($pass, PASSWORD_DEFAULT), 'User']);
        flash('success', 'Registrasi berhasil. Silakan masuk.');
        redirect(app_url('login.php'));
    } catch (Throwable $e) {
        if ($e instanceof PDOException && $e->getCode() === '23000') $err = 'Username atau email sudah digunakan.';
        else $err = $e instanceof RuntimeException ? $e->getMessage() : 'Registrasi gagal. Periksa data lalu coba lagi.';
        error_log($e instanceof PDOException ? $e->getMessage() : '');
    }
}
page_start('Daftar', true); ?>
<div class="sp-auth-wrap">
    <div class="sp-auth-brand"><a class="sp-brand mb-5" href="<?= e(app_url()) ?>"><img class="sp-brand-logo" src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""> SIPEKA</a>
        <div class="sp-eyebrow">LAPORAN FASILITAS KAMPUS</div>
        <h1 class="sp-hero-title mt-3">Bantu kampus<br><span class="sp-accent">lebih nyaman.</span></h1>
        <p class="sp-muted mt-3">Satu laporan kecil bisa membantu memperbaiki ruang belajar bersama.</p>
        <div class="mt-4"><?php foreach (['Pelaporan mudah dan terarah', 'Pantau status kapan saja', 'Riwayat proses yang jelas'] as $x): ?><p>✓ &nbsp;<?= e($x) ?></p><?php endforeach; ?></div>
    </div>
    <div class="sp-auth-form">
        <div class="sp-card"><a href="<?= e(app_url()) ?>" class="sp-muted text-decoration-none small">← Kembali ke beranda</a>
            <h1 class="sp-auth-title mt-4">Buat akun</h1>
            <p class="sp-muted">Daftar untuk mulai melaporkan kerusakan.</p><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><form method="post" class="mt-4" novalidate><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="nama">Nama lengkap</label><input class="form-control" id="nama" name="nama" maxlength="100" required value="<?= e($_POST['nama'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" maxlength="50" required value="<?= e($_POST['username'] ?? '') ?>"></div>
                <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" maxlength="100" required value="<?= e($_POST['email'] ?? '') ?>"></div>
                <div class="mb-4"><label class="form-label" for="password">Kata sandi</label><input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                    <div class="form-text sp-muted">Minimal 8 karakter.</div>
                </div><button class="btn sp-btn-primary w-100" type="submit">Daftar Sekarang →</button>
            </form>
            <p class="text-center mt-4 mb-0 sp-muted">Sudah punya akun? <a href="<?= e(app_url('login.php')) ?>">Masuk</a></p>
        </div>
    </div>
</div><?php page_end(true); ?>