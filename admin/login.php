<?php
require_once __DIR__ . '/../includes/db.php';

if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Admin Login - CholoGhuri';

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = 'Please enter username and password.';

    } else {

        $stmt = $pdo->prepare("
            SELECT
                id,
                username,
                password,
                name
            FROM admin_users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {

            session_regenerate_id(true);

            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];

            header('Location: index.php');
            exit;

        } else {

            $error = 'Invalid admin username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title><?= e($pageTitle) ?></title>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="../css/style.css"
>

</head>

<body class="admin-login-page">

<div class="admin-login-wrapper">

    <div class="admin-login-card">

        <div class="admin-login-logo">

            <div class="admin-logo-icon">
                🧳
            </div>

            <h1>
                Cholo<span>Ghuri</span>
            </h1>

            <p>
                Administration Panel
            </p>

        </div>


        <?php if ($error !== ''): ?>

            <div class="admin-login-error">
                ⚠️ <?= e($error) ?>
            </div>

        <?php endif; ?>


        <form
            action="login.php"
            method="POST"
            class="admin-login-form"
        >

            <div class="form-group">

                <label for="username">
                    Admin Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    value="<?= e($username) ?>"
                    placeholder="Enter admin username"
                    required
                    autofocus
                >

            </div>


            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Enter admin password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="btn btn-primary admin-login-button"
            >
                🔐 Login to Dashboard
            </button>

        </form>


        <div class="admin-login-footer">

            <a href="../index.php">
                ← Back to CholoGhuri
            </a>

        </div>

    </div>

</div>


<style>

.admin-login-page {
    min-height: 100vh;
    background: linear-gradient(
        135deg,
        #f3f7ff,
        #ffffff
    );
}

.admin-login-wrapper {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px 20px;
}

.admin-login-card {
    width: 100%;
    max-width: 430px;
    background: #ffffff;
    padding: 38px;
    border-radius: 18px;
    box-shadow: 0 15px 45px rgba(0,0,0,0.10);
}

.admin-login-logo {
    text-align: center;
    margin-bottom: 30px;
}

.admin-logo-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    border-radius: 50%;
    background: #f1f5ff;
    font-size: 30px;
}

.admin-login-logo h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
}

.admin-login-logo h1 span {
    color: #ff6b35;
}

.admin-login-logo p {
    margin: 6px 0 0;
    color: #777;
    font-size: 14px;
}

.admin-login-error {
    margin-bottom: 20px;
    padding: 13px 15px;
    border-radius: 9px;
    background: #fff1f1;
    border: 1px solid #ffcaca;
    color: #b00020;
    font-size: 14px;
}

.admin-login-form .form-group {
    margin-bottom: 20px;
}

.admin-login-button {
    width: 100%;
    margin-top: 5px;
}

.admin-login-footer {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eeeeee;
    text-align: center;
}

.admin-login-footer a {
    color: #666;
    font-size: 14px;
    text-decoration: none;
}

.admin-login-footer a:hover {
    text-decoration: underline;
}

</style>

<script src="../js/script.js"></script>

</body>
</html>
