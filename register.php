<?php
require_once __DIR__ . '/includes/db.php';

if (isUserLoggedIn()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Create Account - CholoGhuri';

$name = '';
$email = '';
$phone = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {
        $errors[] = 'Please enter your full name.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must contain at least 2 characters.';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Name is too long.';
    }

    if ($email === '') {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($phone !== '') {
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
    }

    if ($password === '') {
        $errors[] = 'Please enter a password.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must contain at least 6 characters.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Please confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }


    /*
    |--------------------------------------------------------------------------
    | Check Existing Email
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create Account
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $pdo->prepare("
            INSERT INTO users (
                name,
                email,
                phone,
                password
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $email,
            $phone !== '' ? $phone : null,
            $hashedPassword
        ]);

        $userId = $pdo->lastInsertId();

        /*
         * Automatically login after registration.
         */
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        redirectWithMessage(
            'index.php',
            'Account created successfully. Welcome to CholoGhuri!',
            'success'
        );
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="auth-wrapper">

    <div class="auth-card">

        <div class="auth-header">

            <div class="auth-icon">
                ✈️
            </div>

            <h1>Create Your Account</h1>

            <p>
                Join CholoGhuri and start exploring Bangladesh.
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
            action="register.php"
            class="auth-form"
            novalidate
        >

            <div class="form-group">

                <label for="name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?= e($name) ?>"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                >

            </div>


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
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                    <span class="optional">
                        (Optional)
                    </span>
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control"
                    value="<?= e($phone) ?>"
                    placeholder="+880 1XXXXXXXXX"
                    maxlength="30"
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
                        placeholder="Minimum 6 characters"
                        minlength="6"
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


            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Re-enter your password"
                        minlength="6"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        data-target="confirm_password"
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
                Create Account
            </button>

        </form>


        <div class="auth-footer">

            <p>
                Already have an account?
                <a href="login.php">
                    Login here
                </a>
            </p>

        </div>

    </div>

</section>


<style>

.optional {
    color: #888;
    font-size: 13px;
    font-weight: 400;
}

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
