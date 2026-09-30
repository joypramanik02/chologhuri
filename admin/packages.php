<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Tour Packages - CholoGhuri';

$errors = [];


/*
|--------------------------------------------------------------------------
| Delete Package
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_package'])) {

    $packageId = (int) ($_POST['package_id'] ?? 0);

    if ($packageId > 0) {

        /*
         * Check whether package exists.
         */
        $stmt = $pdo->prepare("
            SELECT id, image
            FROM tour_packages
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$packageId]);

        $package = $stmt->fetch();

        if ($package) {

            /*
             * Delete package.
             *
             * Related bookings/reviews/wishlist records may also
             * be deleted because of the database foreign keys.
             */
            $stmt = $pdo->prepare("
                DELETE FROM tour_packages
                WHERE id = ?
            ");

            $stmt->execute([$packageId]);

            /*
             * Delete local uploaded image if applicable.
             */
            if (!empty($package['image'])) {

                $imagePath = __DIR__ . '/../' . $package['image'];

                if (
                    file_exists($imagePath) &&
                    is_file($imagePath)
                ) {
                    @unlink($imagePath);
                }
            }

            redirectWithMessage(
                'packages.php',
                'Tour package deleted successfully.',
                'success'
            );

        } else {

            redirectWithMessage(
                'packages.php',
                'Package not found.',
                'error'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Toggle Package Status
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {

    $packageId = (int) ($_POST['package_id'] ?? 0);

    if ($packageId > 0) {

        $stmt = $pdo->prepare("
            SELECT status
            FROM tour_packages
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$packageId]);

        $package = $stmt->fetch();

        if ($package) {

            $newStatus =
                $package['status'] === 'active'
                    ? 'inactive'
                    : 'active';

            $stmt = $pdo->prepare("
                UPDATE tour_packages
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $packageId
            ]);

            redirectWithMessage(
                'packages.php',
                'Package status updated successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Add / Update Package
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_package'])) {

    $packageId = (int) ($_POST['package_id'] ?? 0);

    $title = trim($_POST['title'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $totalSeats = (int) ($_POST['total_seats'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';

    /*
     * Validation
     */

    if ($title === '') {
        $errors[] = 'Package title is required.';
    }

    if ($destination === '') {
        $errors[] = 'Destination is required.';
    }

    if ($duration === '') {
        $errors[] = 'Duration is required.';
    }

    if ($price === '' || !is_numeric($price)) {
        $errors[] = 'Please enter a valid price.';
    } elseif ((float) $price < 0) {
        $errors[] = 'Price cannot be negative.';
    }

    if ($totalSeats <= 0) {
        $errors[] = 'Total seats must be greater than 0.';
    }

    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = 'Invalid package status.';
    }

    if (strlen($description) > 10000) {
        $errors[] = 'Description is too long.';
    }


    /*
     * Image Upload
     */

    $imagePath = null;

    if (
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {

            $errors[] = 'There was a problem uploading the image.';

        } else {

            $fileSize = (int) $_FILES['image']['size'];

            if ($fileSize > 5 * 1024 * 1024) {

                $errors[] =
                    'Package image must be smaller than 5MB.';

            } else {

                $tmpName = $_FILES['image']['tmp_name'];

                $fileInfo = @getimagesize($tmpName);

                if ($fileInfo === false) {

                    $errors[] =
                        'Please upload a valid image file.';

                } else {

                    $allowedMimeTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];

                    if (
                        !in_array(
                            $fileInfo['mime'],
                            $allowedMimeTypes,
                            true
                        )
                    ) {

                        $errors[] =
                            'Only JPG, PNG and WEBP images are allowed.';

                    } else {

                        $uploadDirectory =
                            __DIR__ . '/../uploads/packages/';

                        if (
                            !is_dir($uploadDirectory) &&
                            !mkdir(
                                $uploadDirectory,
                                0755,
                                true
                            )
                        ) {

                            $errors[] =
                                'Unable to create upload directory.';

                        } else {

                            $extension =
                                match ($fileInfo['mime']) {
                                    'image/jpeg' => 'jpg',
                                    'image/png' => 'png',
                                    'image/webp' => 'webp',
                                    default => 'jpg'
                                };

                            $fileName =
                                'package_' .
                                time() .
                                '_' .
                                bin2hex(random_bytes(5)) .
                                '.' .
                                $extension;

                            $destinationPath =
                                $uploadDirectory .
                                $fileName;

                            if (
                                move_uploaded_file(
                                    $tmpName,
                                    $destinationPath
                                )
                            ) {

                                $imagePath =
                                    'uploads/packages/' .
                                    $fileName;

                            } else {

                                $errors[] =
                                    'Unable to save uploaded image.';
                            }
                        }
                    }
                }
            }
        }
    }


    /*
     * Save Package
     */

    if (empty($errors)) {

        $priceValue = (float) $price;

        if ($packageId > 0) {

            /*
             * Get old image.
             */
            $stmt = $pdo->prepare("
                SELECT image
                FROM tour_packages
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$packageId]);

            $existingPackage = $stmt->fetch();

            if (!$existingPackage) {

                $errors[] = 'Package not found.';

            } else {

                if ($imagePath !== null) {

                    $stmt = $pdo->prepare("
                        UPDATE tour_packages
                        SET
                            title = ?,
                            destination = ?,
                            duration = ?,
                            price = ?,
                            image = ?,
                            description = ?,
                            total_seats = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $title,
                        $destination,
                        $duration,
                        $priceValue,
                        $imagePath,
                        $description,
                        $totalSeats,
                        $status,
                        $packageId
                    ]);

                    /*
                     * Delete old image.
                     */
                    if (!empty($existingPackage['image'])) {

                        $oldImagePath =
                            __DIR__ .
                            '/../' .
                            $existingPackage['image'];

                        if (
                            file_exists($oldImagePath) &&
                            is_file($oldImagePath)
                        ) {
                            @unlink($oldImagePath);
                        }
                    }

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE tour_packages
                        SET
                            title = ?,
                            destination = ?,
                            duration = ?,
                            price = ?,
                            description = ?,
                            total_seats = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $title,
                        $destination,
                        $duration,
                        $priceValue,
                        $description,
                        $totalSeats,
                        $status,
                        $packageId
                    ]);
                }

                redirectWithMessage(
                    'packages.php',
                    'Tour package updated successfully.',
                    'success'
                );
            }

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO tour_packages (
                    title,
                    destination,
                    duration,
                    price,
                    image,
                    description,
                    total_seats,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $destination,
                $duration,
                $priceValue,
                $imagePath,
                $description,
                $totalSeats,
                $status
            ]);

            redirectWithMessage(
                'packages.php',
                'New tour package added successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Edit Package
|--------------------------------------------------------------------------
*/

$editPackage = null;

if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM tour_packages
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$editId]);

        $editPackage = $stmt->fetch();

        if (!$editPackage) {

            redirectWithMessage(
                'packages.php',
                'Package not found.',
                'error'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Packages
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        tp.*,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(DISTINCT r.id) AS review_count,
        COALESCE(
            (
                SELECT SUM(b.people)
                FROM bookings b
                WHERE b.package_id = tp.id
                AND b.status != 'Cancelled'
            ),
            0
        ) AS booked_seats
    FROM tour_packages tp
    LEFT JOIN reviews r
        ON r.package_id = tp.id
    GROUP BY tp.id
    ORDER BY tp.created_at DESC
");

$packages = $stmt->fetchAll();

include __DIR__ . '/header.php';
?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                Tour Management
            </span>

            <h2>
                Tour Packages
            </h2>

            <p>
                Add, edit and manage all available tour packages.
            </p>

        </div>

        <a
            href="packages.php#package-form"
            class="btn btn-primary"
        >
            + Add New Package
        </a>

    </div>


    <!-- Errors -->

    <?php if (!empty($errors)): ?>

        <div class="admin-form-errors">

            <?php foreach ($errors as $error): ?>

                <div>
                    ⚠️ <?= e($error) ?>
                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- Add/Edit Form -->

    <div
        class="admin-card package-form-card"
        id="package-form"
    >

        <div class="card-header">

            <div>

                <h3>
                    <?= $editPackage
                        ? 'Edit Tour Package'
                        : 'Add New Tour Package'
                    ?>
                </h3>

                <p>
                    Enter package information below.
                </p>

            </div>

            <?php if ($editPackage): ?>

                <a
                    href="packages.php"
                    class="btn btn-outline btn-sm"
                >
                    Cancel Edit
                </a>

            <?php endif; ?>

        </div>


        <form
            action="packages.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <?php if ($editPackage): ?>

                <input
                    type="hidden"
                    name="package_id"
                    value="<?= (int) $editPackage['id'] ?>"
                >

            <?php endif; ?>


            <div class="form-row">

                <div class="form-group">

                    <label for="title">
                        Package Title
                    </label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        value="<?= e(
                            $editPackage['title'] ?? ''
                        ) ?>"
                        placeholder="e.g. Cox's Bazar Beach Escape"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="destination">
                        Destination
                    </label>

                    <input
                        type="text"
                        id="destination"
                        name="destination"
                        class="form-control"
                        value="<?= e(
                            $editPackage['destination'] ?? ''
                        ) ?>"
                        placeholder="e.g. Cox's Bazar"
                        maxlength="100"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label for="duration">
                        Duration
                    </label>

                    <input
                        type="text"
                        id="duration"
                        name="duration"
                        class="form-control"
                        value="<?= e(
                            $editPackage['duration'] ?? ''
                        ) ?>"
                        placeholder="e.g. 3 Days 2 Nights"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="price">
                        Price per Person (৳)
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        class="form-control"
                        value="<?= e(
                            $editPackage['price'] ?? ''
                        ) ?>"
                        placeholder="5500"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="total_seats">
                        Total Seats
                    </label>

                    <input
                        type="number"
                        id="total_seats"
                        name="total_seats"
                        class="form-control"
                        value="<?= e(
                            $editPackage['total_seats'] ?? 20
                        ) ?>"
                        min="1"
                        max="10000"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-control"
                    >

                        <option
                            value="active"
                            <?= (
                                ($editPackage['status'] ?? 'active')
                                === 'active'
                            ) ? 'selected' : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= (
                                ($editPackage['status'] ?? '')
                                === 'inactive'
                            ) ? 'selected' : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-group">

                <label for="description">
                    Package Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    rows="6"
                    maxlength="10000"
                    placeholder="Describe the tour package, places to visit, facilities, etc."
                ><?= e(
                    $editPackage['description'] ?? ''
                ) ?></textarea>

            </div>


            <div class="form-group">

                <label for="image">
                    Package Image
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small class="form-help">
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>

            </div>


            <?php if (
                $editPackage &&
                !empty($editPackage['image'])
            ): ?>

                <div class="current-package-image">

                    <p>
                        Current Image
                    </p>

                    <img
                        src="../<?= e($editPackage['image']) ?>"
                        alt="Current package image"
                    >

                </div>

            <?php endif; ?>


            <div class="form-actions">

                <button
                    type="submit"
                    name="save_package"
                    class="btn btn-primary"
                >
                    <?= $editPackage
                        ? '✓ Update Package'
                        : '+ Add Package'
                    ?>
                </button>

                <?php if ($editPackage): ?>

                    <a
                        href="packages.php"
                        class="btn btn-outline"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- Package List -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    All Tour Packages
                </h3>

                <p>
                    <?= count($packages) ?>
                    package(s) found
                </p>

            </div>

        </div>


        <?php if (empty($packages)): ?>

            <div class="empty-state">

                <div class="empty-icon">
                    🗺️
                </div>

                <h3>
                    No Tour Packages
                </h3>

                <p>
                    Add your first tour package using the form above.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table class="data-table">

                    <thead>

                        <tr>

                            <th>
                                Package
                            </th>

                            <th>
                                Destination
                            </th>

                            <th>
                                Duration
                            </th>

                            <th>
                                Price
                            </th>

                            <th>
                                Seats
                            </th>

                            <th>
                                Rating
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach ($packages as $package): ?>

                        <?php

                        $bookedSeats =
                            (int) $package['booked_seats'];

                        $availableSeats =
                            max(
                                0,
                                (int) $package['total_seats']
                                - $bookedSeats
                            );

                        ?>

                        <tr>


                            <!-- Package -->

                            <td>

                                <div class="admin-package-cell">

                                    <?php if (
                                        !empty($package['image'])
                                    ): ?>

                                        <img
                                            src="../<?= e($package['image']) ?>"
                                            alt="<?= e($package['title']) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="admin-package-placeholder">
                                            🌴
                                        </div>

                                    <?php endif; ?>


                                    <div>

                                        <strong>
                                            <?= e($package['title']) ?>
                                        </strong>

                                        <small>
                                            ID #<?= (int) $package['id'] ?>
                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- Destination -->

                            <td>
                                📍 <?= e(
                                    $package['destination']
                                ) ?>
                            </td>


                            <!-- Duration -->

                            <td>
                                <?= e(
                                    $package['duration']
                                ) ?>
                            </td>


                            <!-- Price -->

                            <td>

                                <strong>
                                    ৳<?= number_format(
                                        $package['price'],
                                        2
                                    ) ?>
                                </strong>

                                <small>
                                    per person
                                </small>

                            </td>


                            <!-- Seats -->

                            <td>

                                <strong>
                                    <?= $availableSeats ?>
                                </strong>
                                /
                                <?= (int) $package['total_seats'] ?>

                                <small>
                                    available
                                </small>

                            </td>


                            <!-- Rating -->

                            <td>

                                <?php if (
                                    (float) $package['review_count'] > 0
                                ): ?>

                                    <strong>
                                        ⭐
                                        <?= number_format(
                                            $package['avg_rating'],
                                            1
                                        ) ?>
                                    </strong>

                                    <small>
                                        (
                                        <?= (int) $package['review_count'] ?>
                                        reviews)
                                    </small>

                                <?php else: ?>

                                    <span>
                                        No reviews
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="status status-<?= e(
                                        $package['status']
                                    ) ?>"
                                >
                                    <?= ucfirst(
                                        e($package['status'])
                                    ) ?>
                                </span>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="admin-action-buttons">

                                    <a
                                        href="packages.php?edit=<?= (int) $package['id'] ?>#package-form"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>


                                    <form
                                        action="packages.php"
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="package_id"
                                            value="<?= (int) $package['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="toggle_status"
                                            class="btn btn-secondary btn-sm"
                                        >
                                            <?= $package['status'] === 'active'
                                                ? 'Disable'
                                                : 'Activate'
                                            ?>
                                        </button>

                                    </form>


                                    <form
                                        action="packages.php"
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="package_id"
                                            value="<?= (int) $package['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_package"
                                            class="btn btn-danger btn-sm"
                                            data-confirm="Are you sure you want to permanently delete this tour package?"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>


<style>

.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.admin-page-header h2 {
    margin: 6px 0;
}

.admin-page-header p {
    margin: 0;
    color: #777;
}

.admin-form-errors {
    margin-bottom: 25px;
    padding: 15px 18px;
    border-radius: 10px;
    background: #fff1f1;
    border: 1px solid #ffcaca;
    color: #b00020;
}

.admin-form-errors div {
    margin: 4px 0;
}

.package-form-card {
    margin-bottom: 25px;
}

.current-package-image {
    margin-bottom: 20px;
}

.current-package-image p {
    font-weight: 600;
    margin-bottom: 8px;
}

.current-package-image img {
    width: 180px;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
}

.form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.admin-package-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 230px;
}

.admin-package-cell img,
.admin-package-placeholder {
    width: 65px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    flex-shrink: 0;
}

.admin-package-placeholder {
    background: #f1f3f5;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 22px;
}

.admin-package-cell strong {
    display: block;
}

.admin-package-cell small,
.data-table td small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 3px;
}

.admin-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    min-width: 180px;
}

.admin-action-buttons .inline-form {
    display: inline;
}

@media (max-width: 900px) {

    .admin-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
