<?php

/*
|--------------------------------------------------------------------------
| CholoGhuri Database Connection
|--------------------------------------------------------------------------
*/

$host = 'localhost';
$db   = 'chologhuri';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die(
        '<div style="
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 12px;
            background: #fff3f3;
            border: 1px solid #ffcccc;
            color: #b00020;
        ">
            <h2>Database Connection Failed</h2>
            <p>Please make sure XAMPP Apache and MySQL are running.</p>
            <p>Also check that the <strong>chologhuri</strong> database exists.</p>
        </div>'
    );
}


/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Escape HTML output.
 */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Check whether a user is logged in.
 */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/**
 * Check whether admin is logged in.
 */
function isAdminLoggedIn()
{
    return isset($_SESSION['admin_id']);
}


/**
 * Require user login.
 */
function requireUser()
{
    if (!isUserLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}


/**
 * Require admin login.
 */
function requireAdmin()
{
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}


/**
 * Redirect with a message.
 */
function redirectWithMessage($url, $message, $type = 'success')
{
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;

    header("Location: $url");
    exit;
}


/**
 * Display flash message.
 */
function showFlashMessage()
{
    if (!empty($_SESSION['flash_message'])) {

        $message = e($_SESSION['flash_message']);
        $type = $_SESSION['flash_type'] ?? 'success';

        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);

        echo '<div class="flash-message flash-' . e($type) . '">'
            . $message .
            '</div>';
    }
}
