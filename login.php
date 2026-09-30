<?php require 'includes/db.php';
if (session_status() === PHP_SESSION_NONE)
    session_start();
$pageTitle = 'Login | CholoGhuri';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $s = $conn->prepare('SELECT id,name,password FROM users WHERE email=?');
    $s->bind_param('s', $email);
    $s->execute();
    $u = $s->get_result()->fetch_assoc();
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['user_name'] = $u['name'];
        header('Location: packages.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
require 'includes/header.php'; ?>
<section class="auth">
    <div class="auth-box">
        <h1>Welcome Back 👋</h1>
        <p class="center">Login to manage your trips.</p><?php if (isset($_GET['registered'])): ?>
            <div class="alert success">Account created successfully. Please login.</div><?php endif; ?><?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required>
            </div>
            <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password"
                    required></div><button class="btn" style="border:0;width:100%;font:inherit"
                type="submit">Login</button>
        </form>
        <p class="center">New here? <a style="color:var(--orange)" href="register.php">Create an account</a></p>
    </div>
</section><?php require 'includes/footer.php'; ?>