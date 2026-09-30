
<?php
require_once 'includes/db.php';

requireUser();

$pageTitle = 'My Profile - CholoGhuri';

$userId = (int) $_SESSION['user_id'];

$error = '';
$success = '';

/* =========================================
   GET USER
========================================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        phone,
        password,
        profile_image,
        address,
        created_at

    FROM users

    WHERE id = :id

    LIMIT 1
");

$stmt->execute([
    ':id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    session_destroy();

    header('Location: login.php');
    exit;
}


/* =========================================
   UPDATE PROFILE
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'profile'
) {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');


    /* Validate Name */

    if ($name === '') {

        $error = 'Please enter your name.';

    } elseif (strlen($name) < 2) {

        $error = 'Name must contain at least 2 characters.';

    } elseif (strlen($name) > 100) {

        $error = 'Name is too long.';

    }


    /* Validate Phone */

    if ($error === '' && $phone !== '') {

        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {

            $error = 'Please enter a valid phone number.';

        }

    }


    /* Validate Address */

    if ($error === '' && strlen($address) > 255) {

        $error = 'Address is too long.';

    }


    /* -----------------------------------------
       Profile Image
    ------------------------------------------ */

    $profileImage = $user['profile_image'];

    if (
        $error === '' &&
        isset($_FILES['profile_image']) &&
        $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['profile_image'];


        if ($file['error'] !== UPLOAD_ERR_OK) {

            $error = 'There was a problem uploading the image.';

        } elseif ($file['size'] > 2 * 1024 * 1024) {

            $error = 'Profile image must be smaller than 2 MB.';

        } else {

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $file['tmp_name']
            );


            if (!isset($allowedTypes[$mimeType])) {

                $error =
                    'Only JPG, PNG and WEBP images are allowed.';

            } else {

                $uploadDirectory =
                    __DIR__ . '/uploads/profiles/';


                if (!is_dir($uploadDirectory)) {

                    mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    );

                }


                $extension =
                    $allowedTypes[$mimeType];


                $fileName =
                    'user_' .
                    $userId .
                    '_' .
                    time() .
                    '.' .
                    $extension;


                $destination =
                    $uploadDirectory .
                    $fileName;


                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $destination
                    )
                ) {

                    /* Delete old image */

                    if (
                        !empty($user['profile_image']) &&
                        strpos(
                            $user['profile_image'],
                            'uploads/profiles/'
                        ) === 0
                    ) {

                        $oldImage =
                            __DIR__ . '/' .
                            $user['profile_image'];

                        if (
                            file_exists($oldImage)
                        ) {

                            unlink($oldImage);

                        }

                    }


                    $profileImage =
                        'uploads/profiles/' .
                        $fileName;

                } else {

                    $error =
                        'Unable to save the profile image.';

                }

            }

        }

    }


    /* -----------------------------------------
       Update Database
    ------------------------------------------ */

    if ($error === '') {

        $updateStmt = $pdo->prepare("
            UPDATE users

            SET
                name = :name,
                phone = :phone,
                address = :address,
                profile_image = :profile_image

            WHERE id = :id
        ");

        $updateStmt->execute([
            ':name' => $name,
            ':phone' => $phone,
            ':address' => $address,
            ':profile_image' => $profileImage,
            ':id' => $userId
        ]);


        $success =
            'Your profile has been updated successfully.';


        /* Refresh user data */

        $stmt->execute([
            ':id' => $userId
        ]);

        $user = $stmt->fetch();

    }

}


/* =========================================
   CHANGE PASSWORD
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_type'] ?? '') === 'password'
) {

    $currentPassword =
        $_POST['current_password'] ?? '';

    $newPassword =
        $_POST['new_password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /* Current Password */

    if ($currentPassword === '') {

        $error =
            'Please enter your current password.';

    }


    /* New Password */

    if ($error === '' && strlen($newPassword) < 6) {

        $error =
            'New password must contain at least 6 characters.';

    }


    /* Confirm Password */

    if (
        $error === '' &&
        $newPassword !== $confirmPassword
    ) {

        $error =
            'New password and confirm password do not match.';

    }


    /* Verify Current Password */

    if ($error === '') {

        if (
            !password_verify(
                $currentPassword,
                $user['password']
            )
        ) {

            $error =
                'Current password is incorrect.';

        }

    }


    /* Update Password */

    if ($error === '') {

        $hashedPassword =
            password_hash(
                $newPassword,
                PASSWORD_DEFAULT
            );


        $passwordStmt = $pdo->prepare("
            UPDATE users

            SET password = :password

            WHERE id = :id
        ");

        $passwordStmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);


        $success =
            'Your password has been changed successfully.';

    }

}


/* =========================================
   BOOKING STATISTICS
========================================= */

$bookingStatsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_bookings,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Confirmed'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS confirmed_bookings,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS pending_bookings,

        COALESCE(
            SUM(
                CASE
                    WHEN status = 'Cancelled'
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS cancelled_bookings

    FROM bookings

    WHERE user_id = :user_id
");

$bookingStatsStmt->execute([
    ':user_id' => $userId
]);

$bookingStats =
    $bookingStatsStmt->fetch();


/* =========================================
   WISHLIST COUNT
========================================= */

$wishlistStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM wishlist
    WHERE user_id = :user_id
");

$wishlistStmt->execute([
    ':user_id' => $userId
]);

$wishlistCount =
    (int) $wishlistStmt->fetch()['total'];


include 'includes/header.php';
?>


<!-- =========================================
     PAGE BANNER
========================================= -->

<section class="page-banner">

    <div class="container">

        <h1>
            My Profile
        </h1>

        <p>
            Manage your personal information and account settings.
        </p>

    </div>

</section>


<!-- =========================================
     PROFILE SECTION
========================================= -->

<section class="section">

    <div class="container">

        <?php if ($error !== ''): ?>

            <div class="flash-message flash-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success !== ''): ?>

            <div class="flash-message flash-success">
                <?= e($success) ?>
            </div>

        <?php endif; ?>


        <div class="profile-grid">


            <!-- =================================
                 PROFILE CARD
            ================================== -->

            <div class="profile-card">

                <div class="profile-avatar">

                    <?php if (!empty($user['profile_image'])): ?>

                        <img
                            src="<?= e($user['profile_image']) ?>"
                            alt="<?= e($user['name']) ?>"
                        >

                    <?php else: ?>

                        <span>
                            <?= strtoupper(
                                substr(
                                    $user['name'],
                                    0,
                                    1
                                )
                            ) ?>
                        </span>

                    <?php endif; ?>

                </div>


                <h2>
                    <?= e($user['name']) ?>
                </h2>


                <p class="profile-email">
                    <?= e($user['email']) ?>
                </p>


                <span class="profile-member">

                    Member since
                    <?= date(
                        'M Y',
                        strtotime($user['created_at'])
                    ) ?>

                </span>


                <div class="profile-links">

                    <a href="profile.php" class="active">
                        👤 Profile
                    </a>

                    <a href="my-bookings.php">
                        📋 My Bookings
                    </a>

                    <a href="wishlist.php">
                        ♡ My Wishlist
                    </a>

                    <a href="packages.php">
                        🧳 Explore Packages
                    </a>

                    <a href="logout.php">
                        🚪 Logout
                    </a>

                </div>

            </div>


            <!-- =================================
                 PROFILE FORM
            ================================== -->

            <div class="profile-content">


                <!-- Personal Information -->

                <div class="admin-card profile-form-card">

                    <div class="card-header">

                        <h2>
                            Personal Information
                        </h2>

                        <p>
                            Update your profile information.
                        </p>

                    </div>


                    <form
                        method="POST"
                        enctype="multipart/form-data"
                    >

                        <input
                            type="hidden"
                            name="form_type"
                            value="profile"
                        >


                        <div class="form-group">

                            <label for="profile_image">
                                Profile Picture
                            </label>

                            <input
                                type="file"
                                name="profile_image"
                                id="profile_image"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp"
                            >

                            <small class="form-help">
                                Maximum 2 MB. JPG, PNG or WEBP.
                            </small>

                        </div>


                        <div class="form-group">

                            <label for="name">
                                Full Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="name"
                                class="form-control"
                                maxlength="100"
                                value="<?= e($user['name']) ?>"
                                required
                            >

                        </div>


                        <div class="form-row">

                            <div class="form-group">

                                <label for="email">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    class="form-control"
                                    value="<?= e($user['email']) ?>"
                                    readonly
                                >

                                <small class="form-help">
                                    Email address cannot be changed.
                                </small>

                            </div>


                            <div class="form-group">

                                <label for="phone">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    class="form-control"
                                    maxlength="30"
                                    placeholder="+880 1XXXXXXXXX"
                                    value="<?= e($user['phone'] ?? '') ?>"
                                >

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="address">
                                Address
                            </label>

                            <textarea
                                name="address"
                                id="address"
                                class="form-control"
                                rows="4"
                                maxlength="255"
                                placeholder="Enter your address..."
                            ><?= e($user['address'] ?? '') ?></textarea>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Save Changes
                        </button>

                    </form>

                </div>


                <!-- Password -->

                <div class="admin-card profile-form-card">

                    <div class="card-header">

                        <h2>
                            🔐 Change Password
                        </h2>

                        <p>
                            Keep your account secure with a strong password.
                        </p>

                    </div>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="form_type"
                            value="password"
                        >


                        <div class="form-group">

                            <label for="current_password">
                                Current Password
                            </label>

                            <input
                                type="password"
                                name="current_password"
                                id="current_password"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="form-row">

                            <div class="form-group">

                                <label for="new_password">
                                    New Password
                                </label>

                                <input
                                    type="password"
                                    name="new_password"
                                    id="new_password"
                                    class="form-control"
                                    minlength="6"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label for="confirm_password">
                                    Confirm New Password
                                </label>

                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm_password"
                                    class="form-control"
                                    minlength="6"
                                    required
                                >

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-warning"
                        >
                            Change Password
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     ACCOUNT STATISTICS
========================================= -->

<section class="section light-section">

    <div class="container">

        <div class="section-heading">

            <span class="section-tag">
                Account Overview
            </span>

            <h2 class="section-title">
                Your Travel Activity
            </h2>

        </div>


        <div class="admin-stats">


            <div class="stat-card">

                <div class="stat-icon">
                    📋
                </div>

                <div>

                    <span>
                        Total Bookings
                    </span>

                    <strong>
                        <?= (int) $bookingStats['total_bookings'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div>

                    <span>
                        Confirmed
                    </span>

                    <strong>
                        <?= (int) $bookingStats['confirmed_bookings'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ⏳
                </div>

                <div>

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= (int) $bookingStats['pending_bookings'] ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ♡
                </div>

                <div>

                    <span>
                        Wishlist
                    </span>

                    <strong>
                        <?= $wishlistCount ?>
                    </strong>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =========================================
     SECURITY NOTE
========================================= -->

<section class="section">

    <div class="container">

        <div class="profile-security-note">

            <div class="info-icon">
                🛡️
            </div>

            <div>

                <h3>
                    Keep Your Account Secure
                </h3>

                <p>
                    Never share your password with anyone.
                    Use a strong password containing letters,
                    numbers and special characters.
                </p>

            </div>

        </div>

    </div>

</section>


<?php include 'includes/footer.php'; ?>
