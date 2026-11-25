
<?php
require_once __DIR__ . '/../includes/auth.php';   // ← naik 1 level

$base = defined('BASE_URL') ? BASE_URL : '/sparepart/';

if (isLogin()) redirect($base . 'index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (attemptLogin($conn, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        redirect($base . 'index.php');
    }
    $err = 'Username atau password salah!';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PT. Modern Plasindo Mutiara</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            border: none;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-icon {
            width: 60px;
            height: 60px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 15px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: #2563eb;
            color: #2563eb;
        }
        .btn-login {
            background: #2563eb;
            color: #fff;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: #1e40af;
            color: #fff;
            transform: translateY(-1px);
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="text-uppercase text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Inventory Management System</div>
        <h4 class="fw-bold mt-1 mb-0 text-dark">PT. Modern Plasindo Mutiara</h4>
        <div class="text-muted" style="font-size: 0.85rem;">Sparepart Management System</div>
    </div>

    <h5 class="fw-bold mb-2">Welcome Back.</h5>
    <p class="text-muted mb-4" style="font-size: 0.9rem;">Silakan login untuk melanjutkan ke dashboard.</p>

    <?php if ($err): ?>
        <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-3" style="font-size: 0.9rem; border-radius: 10px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($err) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="username" class="form-label">USERNAME</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">PASSWORD</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius: 0 10px 10px 0; border-color: #e2e8f0; border-left: none; background: #fff;">
                    <i class="bi bi-eye-slash" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-login w-100 mb-3 d-flex justify-content-center align-items-center gap-2">
            LOGIN TO DASHBOARD <i class="bi bi-arrow-right"></i>
        </button>
        
        <div class="text-center">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">
                <i class="bi bi-shield-lock me-1"></i> Secure System Access
            </small>
        </div>
    </form>
</div>

<!-- Script untuk Toggle Password -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('togglePassword').addEventListener('click', function (e) {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    });
</script>
</body>
</html>