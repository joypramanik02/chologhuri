<?php require 'includes/db.php';
$pageTitle = 'Register | CholoGhuri';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];
    if (strlen($pass) < 6)
        $error = 'Password must be at least 6 characters.';
    else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $s = $conn->prepare('INSERT INTO users(name,email,phone,password) VALUES(?,?,?,?)');
        $s->bind_param('ssss', $name, $email, $phone, $hash);
        if ($s->execute()) {
            header('Location: login.php?registered=1');
            exit;
        }
        $error = $conn->errno === 1062 ? 'Email already exists.' : 'Registration failed.';
    }
}
require 'includes/header.php'; ?>
<section class="auth">
    <div class="auth-box">
        <h1>Create Account</h1>
        <p class="center">Start planning your next journey.</p><?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group"><label>Full Name</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" required>
            </div>
            <div class="form-group"><label>Phone</label><input class="form-control" name="phone"></div>
            <div class="form-group"><label>Password</label><input class="form-control" type="password" name="password"
                    minlength="6" required></div><button class="btn" style="border:0;width:100%;font:inherit"
                type="submit">Create Account</button>
        </form>
        <p class="center">Already have an account? <a style="color:var(--orange)" href="login.php">Login</a></p>
    </div>
</section><?php require 'includes/footer.php'; ?>