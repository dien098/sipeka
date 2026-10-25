<?php require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
if (!empty($_SESSION['user'])) redirect(app_url($_SESSION['user']['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $login = trim($_POST['login'] ?? '');
    $pass = $_POST['password'] ?? '';
    $s = $pdo->prepare('SELECT id_user,nama,username,email,password_hash,role FROM users WHERE username=? OR email=? LIMIT 1');
    $s->execute([$login, $login]);
    $u = $s->fetch();
    if ($u && password_verify($pass, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id_user' => (int)$u['id_user'], 'nama' => $u['nama'], 'username' => $u['username'], 'email' => $u['email'], 'role' => $u['role']];
        redirect(app_url($u['role'] === 'Admin' ? 'admin/dashboard.php' : 'user/dashboard.php'));
    }
    $err = 'Username/email atau kata sandi tidak sesuai.';
}
page_start('Masuk', true); ?>
<div class="sp-auth-wrap">
    <div class="sp-auth-brand"><a class="sp-brand mb-5" href="<?= e(app_url()) ?>"><img class="sp-brand-logo" src="<?= e(app_url('assets/img/logo.png')) ?>" alt=""> SIPEKA</a>
        <div class="sp-eyebrow">SELAMAT DATANG KEMBALI</div>
        <h1 class="sp-hero-title mt-3">Fasilitas Terawat<br><span class="sp-accent">Belajar Nyaman.</span></h1>
        <div class="mt-4">
            <p>✓ &nbsp;Lihat status laporan</p>
            <p>✓ &nbsp;Riwayat proses transparan</p>
            <p>✓ &nbsp;Bantu jaga fasilitas kampus</p>
        </div>
    </div>
    <div class="sp-auth-form">
        <div class="sp-card"><a href="<?= e(app_url()) ?>" class="sp-muted text-decoration-none small">← Kembali ke beranda</a>
            <h1 class="sp-auth-title mt-4">Masuk ke SIPEKA</h1>
            <p class="sp-muted">Masukkan username atau email Anda.</p><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?><form method="post" class="mt-4"><?= csrf_field() ?><div class="mb-3"><label class="form-label" for="login">Username atau email</label><input class="form-control" id="login" name="login" required autocomplete="username" value="<?= e($_POST['login'] ?? '') ?>"></div>
                <div class="mb-4"><label class="form-label" for="password">Kata sandi</label><input class="form-control" type="password" id="password" name="password" required autocomplete="current-password"></div><button class="btn sp-btn-primary w-100" type="submit">Masuk →</button>
            </form>
            <p class="text-center mt-4 mb-0 sp-muted">Belum punya akun? <a href="<?= e(app_url('register.php')) ?>">Daftar</a></p>
        </div>
    </div>
</div><?php page_end(true); ?>