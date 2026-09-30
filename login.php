<?php
require_once __DIR__ . '/includes/db.php';

if (isUserLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Login - CholoGhuri';

$email = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($email === '') {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                email,
                password
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];

            redirectWithMessage(
                'index.php',
                'Login successful. Welcome back, ' . $user['name'] . '!',
                'success'
            );

        } else {

            $errors[] = 'Incorrect email or password.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-wrapper">

    <div class="auth-card">

        <div class="auth-header">

            <div class="auth-icon">
                🔐
            </div>

            <h1>Welcome Back</h1>

            <p>
                Login to manage your tours and bookings.
            </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="auth-errors">

                <?php foreach ($errors as $error): ?>

                    <div>
                        ⚠️ <?= e($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="login.php"
            class="auth-form"
            novalidate
        >

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= e($email) ?>"
                    placeholder="example@email.com"
                    maxlength="150"
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
                        placeholder="Enter your password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="password"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <button
                type="submit"
                class="btn btn-primary btn-full"
            >
                Login
            </button>

        </form>


        <div class="auth-footer">

            <p>
                Don't have an account?
                <a href="register.php">
                    Create Account
                </a>
            </p>

        </div>

    </div>

</section>


<style>

.auth-errors {
    margin-bottom: 20px;
    padding: 14px 16px;
    background: #fff1f1;
    border: 1px solid #ffcaca;
    border-radius: 10px;
    color: #b00020;
}

.auth-errors div {
    margin: 5px 0;
}

.auth-form .form-group {
    margin-bottom: 18px;
}

.password-wrapper {
    position: relative;
}

.password-wrapper .form-control {
    padding-right: 50px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 18px;
}

.btn-full {
    width: 100%;
    margin-top: 8px;
}

.auth-footer {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #eeeeee;
    text-align: center;
}

.auth-footer p {
    margin: 0;
    color: #666;
}

.auth-footer a {
    font-weight: 600;
}

</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
