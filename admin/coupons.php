<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Coupons - CholoGhuri';

$errors = [];


/*
|--------------------------------------------------------------------------
| Delete Coupon
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {

    $couponId = (int) ($_POST['coupon_id'] ?? 0);

    if ($couponId <= 0) {

        $errors[] = 'Invalid coupon ID.';

    } else {

        /*
         * Check whether coupon exists.
         */
        $stmt = $pdo->prepare("
            SELECT id, code
            FROM coupons
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$couponId]);

        $coupon = $stmt->fetch();

        if (!$coupon) {

            $errors[] = 'Coupon not found.';

        } else {

            /*
             * Check whether coupon has been used.
             */
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS usage_count
                FROM coupon_usages
                WHERE coupon_id = ?
            ");

            $stmt->execute([$couponId]);

            $usageCount = (int) (
                $stmt->fetch()['usage_count'] ?? 0
            );


            if ($usageCount > 0) {

                redirectWithMessage(
                    'coupons.php',
                    'This coupon has already been used. Please deactivate it instead of deleting it.',
                    'error'
                );

            } else {

                $stmt = $pdo->prepare("
                    DELETE FROM coupons
                    WHERE id = ?
                ");

                $stmt->execute([
                    $couponId
                ]);

                redirectWithMessage(
                    'coupons.php',
                    'Coupon deleted successfully.',
                    'success'
                );
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Toggle Coupon Status
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_coupon'])
) {

    $couponId = (int) ($_POST['coupon_id'] ?? 0);

    if ($couponId <= 0) {

        $errors[] = 'Invalid coupon ID.';

    } else {

        $stmt = $pdo->prepare("
            SELECT status
            FROM coupons
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $couponId
        ]);

        $coupon = $stmt->fetch();

        if (!$coupon) {

            $errors[] = 'Coupon not found.';

        } else {

            $newStatus =
                $coupon['status'] === 'active'
                    ? 'inactive'
                    : 'active';


            $stmt = $pdo->prepare("
                UPDATE coupons
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $newStatus,
                $couponId
            ]);


            redirectWithMessage(
                'coupons.php',
                'Coupon status updated successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Add / Update Coupon
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['save_coupon'])
) {

    $couponId = (int) (
        $_POST['coupon_id'] ?? 0
    );

    $code = strtoupper(
        trim($_POST['code'] ?? '')
    );

    $discountType = $_POST['discount_type'] ?? 'percent';

    $discountValue = trim(
        $_POST['discount_value'] ?? ''
    );

    $usageLimitRaw = trim(
        $_POST['usage_limit'] ?? ''
    );

    $startDate = trim(
        $_POST['start_date'] ?? ''
    );

    $endDate = trim(
        $_POST['end_date'] ?? ''
    );

    $status = $_POST['status'] ?? 'active';


    /*
     * Validation
     */

    if ($code === '') {

        $errors[] = 'Coupon code is required.';

    } elseif (!preg_match(
        '/^[A-Z0-9_-]{3,50}$/',
        $code
    )) {

        $errors[] =
            'Coupon code may contain only letters, numbers, underscore and hyphen.';

    }


    if (
        !in_array(
            $discountType,
            ['percent', 'fixed'],
            true
        )
    ) {

        $errors[] =
            'Invalid discount type.';
    }


    if (
        $discountValue === '' ||
        !is_numeric($discountValue)
    ) {

        $errors[] =
            'Please enter a valid discount value.';

    } elseif (
        (float) $discountValue <= 0
    ) {

        $errors[] =
            'Discount value must be greater than 0.';

    }


    if (
        $discountType === 'percent' &&
        (float) $discountValue > 100
    ) {

        $errors[] =
            'Percentage discount cannot be greater than 100%.';
    }


    $usageLimit = null;

    if ($usageLimitRaw !== '') {

        if (
            !ctype_digit($usageLimitRaw) ||
            (int) $usageLimitRaw <= 0
        ) {

            $errors[] =
                'Usage limit must be a positive whole number.';

        } else {

            $usageLimit =
                (int) $usageLimitRaw;
        }
    }


    /*
     * Validate dates.
     */

    if ($startDate !== '') {

        $startDateObj =
            DateTime::createFromFormat(
                'Y-m-d',
                $startDate
            );

        if (
            !$startDateObj ||
            $startDateObj->format('Y-m-d') !== $startDate
        ) {

            $errors[] =
                'Invalid start date.';
        }
    }


    if ($endDate !== '') {

        $endDateObj =
            DateTime::createFromFormat(
                'Y-m-d',
                $endDate
            );

        if (
            !$endDateObj ||
            $endDateObj->format('Y-m-d') !== $endDate
        ) {

            $errors[] =
                'Invalid end date.';
        }
    }


    if (
        $startDate !== '' &&
        $endDate !== '' &&
        $startDate > $endDate
    ) {

        $errors[] =
            'End date cannot be earlier than start date.';
    }


    if (
        !in_array(
            $status,
            ['active', 'inactive'],
            true
        )
    ) {

        $errors[] =
            'Invalid coupon status.';
    }


    /*
     * Check duplicate code.
     */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM coupons
            WHERE code = ?
            AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $code,
            $couponId
        ]);

        if ($stmt->fetch()) {

            $errors[] =
                'This coupon code already exists.';
        }
    }


    /*
     * Save Coupon
     */

    if (empty($errors)) {

        $discountValueFloat =
            (float) $discountValue;


        if ($couponId > 0) {

            /*
             * Get existing coupon.
             */
            $stmt = $pdo->prepare("
                SELECT *
                FROM coupons
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $couponId
            ]);

            $existingCoupon =
                $stmt->fetch();


            if (!$existingCoupon) {

                $errors[] =
                    'Coupon not found.';

            } else {

                /*
                 * Don't allow usage limit
                 * below already-used count.
                 */
                $usedCount =
                    (int) $existingCoupon['used_count'];


                if (
                    $usageLimit !== null &&
                    $usageLimit < $usedCount
                ) {

                    $errors[] =
                        'Usage limit cannot be lower than the number of times this coupon has already been used.';

                } else {

                    $stmt = $pdo->prepare("
                        UPDATE coupons
                        SET
                            code = ?,
                            discount_type = ?,
                            discount_value = ?,
                            usage_limit = ?,
                            start_date = ?,
                            end_date = ?,
                            status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $code,
                        $discountType,
                        $discountValueFloat,
                        $usageLimit,
                        $startDate !== ''
                            ? $startDate
                            : null,
                        $endDate !== ''
                            ? $endDate
                            : null,
                        $status,
                        $couponId
                    ]);


                    redirectWithMessage(
                        'coupons.php',
                        'Coupon updated successfully.',
                        'success'
                    );
                }
            }

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO coupons (
                    code,
                    discount_type,
                    discount_value,
                    usage_limit,
                    start_date,
                    end_date,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $code,
                $discountType,
                $discountValueFloat,
                $usageLimit,
                $startDate !== ''
                    ? $startDate
                    : null,
                $endDate !== ''
                    ? $endDate
                    : null,
                $status
            ]);


            redirectWithMessage(
                'coupons.php',
                'New coupon created successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Edit Coupon
|--------------------------------------------------------------------------
*/

$editCoupon = null;


if (isset($_GET['edit'])) {

    $editId = (int) $_GET['edit'];

    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT *
            FROM coupons
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $editId
        ]);

        $editCoupon =
            $stmt->fetch();


        if (!$editCoupon) {

            redirectWithMessage(
                'coupons.php',
                'Coupon not found.',
                'error'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Fetch Coupons
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        c.*,

        (
            SELECT COUNT(*)
            FROM coupon_usages cu
            WHERE cu.coupon_id = c.id
        ) AS actual_usage_count

    FROM coupons c

    ORDER BY c.created_at DESC
");

$coupons =
    $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT

        COUNT(*) AS total_coupons,

        SUM(
            CASE
                WHEN status = 'active'
                THEN 1
                ELSE 0
            END
        ) AS active_coupons,

        SUM(
            CASE
                WHEN status = 'inactive'
                THEN 1
                ELSE 0
            END
        ) AS inactive_coupons,

        COALESCE(
            SUM(used_count),
            0
        ) AS total_usage

    FROM coupons
");

$couponStats =
    $stmt->fetch();


include __DIR__ . '/header.php';

?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                Promotion Management
            </span>

            <h2>
                Coupons & Discounts
            </h2>

            <p>
                Create and manage discount coupons for customers.
            </p>

        </div>


        <a
            href="coupons.php#coupon-form"
            class="btn btn-primary"
        >
            + Create Coupon
        </a>

    </div>


    <!-- Statistics -->

    <div class="admin-stats">


        <div class="stat-card">

            <div class="stat-icon">
                🎟️
            </div>

            <div>

                <span>
                    Total Coupons
                </span>

                <strong>
                    <?= (int) (
                        $couponStats['total_coupons']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🟢
            </div>

            <div>

                <span>
                    Active Coupons
                </span>

                <strong>
                    <?= (int) (
                        $couponStats['active_coupons']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⚪
            </div>

            <div>

                <span>
                    Inactive Coupons
                </span>

                <strong>
                    <?= (int) (
                        $couponStats['inactive_coupons']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                📊
            </div>

            <div>

                <span>
                    Total Usage
                </span>

                <strong>
                    <?= (int) (
                        $couponStats['total_usage']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


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
        class="admin-card coupon-form-card"
        id="coupon-form"
    >

        <div class="card-header">

            <div>

                <h3>
                    <?= $editCoupon
                        ? 'Edit Coupon'
                        : 'Create New Coupon'
                    ?>
                </h3>

                <p>
                    Configure discount and usage settings.
                </p>

            </div>


            <?php if ($editCoupon): ?>

                <a
                    href="coupons.php"
                    class="btn btn-outline btn-sm"
                >
                    Cancel Edit
                </a>

            <?php endif; ?>

        </div>


        <form
            action="coupons.php"
            method="POST"
        >


            <?php if ($editCoupon): ?>

                <input
                    type="hidden"
                    name="coupon_id"
                    value="<?= (int) (
                        $editCoupon['id']
                    ) ?>"
                >

            <?php endif; ?>


            <div class="form-row">


                <!-- Code -->

                <div class="form-group">

                    <label for="code">
                        Coupon Code
                    </label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-control coupon-code-input"
                        value="<?= e(
                            $editCoupon['code']
                            ?? ''
                        ) ?>"
                        placeholder="WELCOME10"
                        maxlength="50"
                        required
                    >

                    <small class="form-help">
                        Use letters, numbers, underscore or hyphen.
                    </small>

                </div>


                <!-- Discount Type -->

                <div class="form-group">

                    <label for="discount_type">
                        Discount Type
                    </label>

                    <select
                        id="discount_type"
                        name="discount_type"
                        class="form-control"
                    >

                        <option
                            value="percent"
                            <?= (
                                ($editCoupon[
                                    'discount_type'
                                ] ?? 'percent')
                                === 'percent'
                            )
                                ? 'selected'
                                : '' ?>
                        >
                            Percentage (%)
                        </option>

                        <option
                            value="fixed"
                            <?= (
                                ($editCoupon[
                                    'discount_type'
                                ] ?? '')
                                === 'fixed'
                            )
                                ? 'selected'
                                : '' ?>
                        >
                            Fixed Amount (৳)
                        </option>

                    </select>

                </div>


                <!-- Discount Value -->

                <div class="form-group">

                    <label for="discount_value">
                        Discount Value
                    </label>

                    <input
                        type="number"
                        id="discount_value"
                        name="discount_value"
                        class="form-control"
                        value="<?= e(
                            $editCoupon[
                                'discount_value'
                            ] ?? ''
                        ) ?>"
                        placeholder="10"
                        min="0.01"
                        step="0.01"
                        required
                    >

                </div>


                <!-- Usage Limit -->

                <div class="form-group">

                    <label for="usage_limit">
                        Usage Limit
                    </label>

                    <input
                        type="number"
                        id="usage_limit"
                        name="usage_limit"
                        class="form-control"
                        value="<?= e(
                            $editCoupon[
                                'usage_limit'
                            ] ?? ''
                        ) ?>"
                        placeholder="100"
                        min="1"
                        step="1"
                    >

                    <small class="form-help">
                        Leave empty for unlimited usage.
                    </small>

                </div>

            </div>


            <div class="form-row">


                <!-- Start Date -->

                <div class="form-group">

                    <label for="start_date">
                        Start Date
                    </label>

                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        class="form-control"
                        value="<?= e(
                            $editCoupon[
                                'start_date'
                            ] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- End Date -->

                <div class="form-group">

                    <label for="end_date">
                        End Date
                    </label>

                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        class="form-control"
                        value="<?= e(
                            $editCoupon[
                                'end_date'
                            ] ?? ''
                        ) ?>"
                    >

                </div>


                <!-- Status -->

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
                                ($editCoupon[
                                    'status'
                                ] ?? 'active')
                                === 'active'
                            )
                                ? 'selected'
                                : '' ?>
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            <?= (
                                ($editCoupon[
                                    'status'
                                ] ?? '')
                                === 'inactive'
                            )
                                ? 'selected'
                                : '' ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


            </div>


            <!-- Example -->

            <div class="coupon-preview-box">

                <strong>
                    💡 Example
                </strong>

                <p>
                    If a customer has a ৳5,000 booking and uses
                    <strong>WELCOME10</strong> with a 10% discount,
                    the discount will be ৳500.
                </p>

            </div>


            <div class="form-actions">

                <button
                    type="submit"
                    name="save_coupon"
                    class="btn btn-primary"
                >
                    <?= $editCoupon
                        ? '✓ Update Coupon'
                        : '+ Create Coupon'
                    ?>
                </button>


                <?php if ($editCoupon): ?>

                    <a
                        href="coupons.php"
                        class="btn btn-outline"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>


        </form>

    </div>


    <!-- Coupon List -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    All Coupons
                </h3>

                <p>
                    <?= count($coupons) ?>
                    coupon(s) found
                </p>

            </div>

        </div>


        <?php if (empty($coupons)): ?>


            <div class="empty-state">

                <div class="empty-icon">
                    🎟️
                </div>

                <h3>
                    No Coupons
                </h3>

                <p>
                    Create your first discount coupon using the form above.
                </p>

            </div>


        <?php else: ?>


            <div class="table-wrapper">

                <table class="data-table">


                    <thead>

                        <tr>

                            <th>
                                Coupon
                            </th>

                            <th>
                                Discount
                            </th>

                            <th>
                                Usage
                            </th>

                            <th>
                                Validity
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach (
                        $coupons as $coupon
                    ): ?>


                        <?php

                        $usedCount =
                            (int) (
                                $coupon[
                                    'actual_usage_count'
                                ]
                                ?? $coupon[
                                    'used_count'
                                ]
                                ?? 0
                            );

                        $usageLimit =
                            $coupon[
                                'usage_limit'
                            ] !== null
                                ? (int) $coupon[
                                    'usage_limit'
                                ]
                                : null;

                        ?>


                        <tr>


                            <!-- Coupon -->

                            <td>

                                <div class="coupon-code-display">

                                    <strong>
                                        <?= e(
                                            $coupon['code']
                                        ) ?>
                                    </strong>

                                    <small>
                                        ID #<?= (int) (
                                            $coupon['id']
                                        ) ?>
                                    </small>

                                </div>

                            </td>


                            <!-- Discount -->

                            <td>

                                <span class="discount-badge">

                                    <?php if (
                                        $coupon[
                                            'discount_type'
                                        ] === 'percent'
                                    ): ?>

                                        <?= number_format(
                                            (float) (
                                                $coupon[
                                                    'discount_value'
                                                ]
                                            ),
                                            0
                                        ) ?>%

                                    <?php else: ?>

                                        ৳<?= number_format(
                                            (float) (
                                                $coupon[
                                                    'discount_value'
                                                ]
                                            ),
                                            2
                                        ) ?>

                                    <?php endif; ?>

                                </span>


                                <small>

                                    <?= $coupon[
                                        'discount_type'
                                    ] === 'percent'
                                        ? 'Percentage discount'
                                        : 'Fixed discount'
                                    ?>

                                </small>

                            </td>


                            <!-- Usage -->

                            <td>

                                <strong>
                                    <?= $usedCount ?>
                                </strong>

                                <span>
                                    /
                                    <?= $usageLimit !== null
                                        ? $usageLimit
                                        : '∞' ?>
                                </span>

                                <div class="usage-progress">

                                    <?php

                                    $usagePercentage =
                                        $usageLimit !== null &&
                                        $usageLimit > 0
                                            ? min(
                                                100,
                                                (
                                                    $usedCount /
                                                    $usageLimit
                                                ) * 100
                                            )
                                            : 0;

                                    ?>

                                    <div
                                        class="usage-progress-fill"
                                        style="
                                            width:
                                            <?= $usagePercentage ?>%;
                                        "
                                    ></div>

                                </div>

                            </td>


                            <!-- Validity -->

                            <td>

                                <?php if (
                                    !empty(
                                        $coupon[
                                            'start_date'
                                        ]
                                    ) ||
                                    !empty(
                                        $coupon[
                                            'end_date'
                                        ]
                                    )
                                ): ?>

                                    <small>

                                        <?=
                                            !empty(
                                                $coupon[
                                                    'start_date'
                                                ]
                                            )
                                                ? date(
                                                    'd M Y',
                                                    strtotime(
                                                        $coupon[
                                                            'start_date'
                                                        ]
                                                    )
                                                )
                                                : 'Anytime'
                                        ?>

                                        →

                                        <?=
                                            !empty(
                                                $coupon[
                                                    'end_date'
                                                ]
                                            )
                                                ? date(
                                                    'd M Y',
                                                    strtotime(
                                                        $coupon[
                                                            'end_date'
                                                        ]
                                                    )
                                                )
                                                : 'No expiry'
                                        ?>

                                    </small>

                                <?php else: ?>

                                    <span>
                                        Anytime
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span
                                    class="status status-<?= e(
                                        $coupon[
                                            'status'
                                        ]
                                    ) ?>"
                                >
                                    <?= ucfirst(
                                        e(
                                            $coupon[
                                                'status'
                                            ]
                                        )
                                    ) ?>
                                </span>

                            </td>


                            <!-- Created -->

                            <td>

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $coupon[
                                            'created_at'
                                        ]
                                    )
                                ) ?>

                            </td>


                            <!-- Actions -->

                            <td>

                                <div class="coupon-actions">


                                    <!-- Edit -->

                                    <a
                                        href="coupons.php?edit=<?= (int) (
                                            $coupon['id']
                                        ) ?>#coupon-form"
                                        class="btn btn-warning btn-sm"
                                    >
                                        Edit
                                    </a>


                                    <!-- Toggle -->

                                    <form
                                        action="coupons.php"
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="coupon_id"
                                            value="<?= (int) (
                                                $coupon['id']
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="toggle_coupon"
                                            class="btn btn-secondary btn-sm"
                                        >
                                            <?= $coupon[
                                                'status'
                                            ] === 'active'
                                                ? 'Disable'
                                                : 'Activate'
                                            ?>
                                        </button>

                                    </form>


                                    <!-- Delete -->

                                    <form
                                        action="coupons.php"
                                        method="POST"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="coupon_id"
                                            value="<?= (int) (
                                                $coupon['id']
                                            ) ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="delete_coupon"
                                            class="btn btn-danger btn-sm"
                                            data-confirm="Are you sure you want to permanently delete this coupon?"
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


    <!-- Admin Note -->

    <div class="admin-info-box">

        <strong>
            ℹ️ Coupon Management Note
        </strong>

        <p>
            A coupon that has already been used cannot be permanently
            deleted. You can deactivate it instead. This keeps the
            coupon usage history safe and maintains database consistency.
        </p>

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

.coupon-form-card {
    margin-bottom: 25px;
}

.coupon-code-input {
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 1px;
}

.coupon-preview-box {
    margin: 5px 0 20px;
    padding: 15px 18px;
    border-radius: 10px;
    background: #f7f9ff;
    border: 1px solid #dce5ff;
}

.coupon-preview-box strong {
    color: #244fc3;
}

.coupon-preview-box p {
    margin: 6px 0 0;
    color: #555;
    line-height: 1.6;
}

.form-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.coupon-code-display strong {
    display: inline-block;
    padding: 7px 11px;
    border-radius: 7px;
    background: #f1f4fb;
    color: #2747a8;
    letter-spacing: 1px;
}

.coupon-code-display small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 5px;
}

.discount-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    background: #eaf8ef;
    color: #198754;
    font-weight: 700;
}

.data-table td small {
    display: block;
    color: #777;
    font-size: 11px;
    margin-top: 4px;
}

.usage-progress {
    width: 90px;
    height: 6px;
    margin-top: 7px;
    background: #edf0f4;
    border-radius: 10px;
    overflow: hidden;
}

.usage-progress-fill {
    height: 100%;
    background: #3157d5;
    border-radius: 10px;
}

.coupon-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    min-width: 190px;
}

.coupon-actions .inline-form {
    display: inline;
}

.admin-info-box {
    margin-top: 20px;
    padding: 18px 20px;
    border-radius: 10px;
    background: #f4f8ff;
    border: 1px solid #d9e5ff;
    color: #445;
}

.admin-info-box strong {
    color: #234;
}

.admin-info-box p {
    margin: 8px 0 0;
    line-height: 1.6;
}

@media (max-width: 950px) {

    .admin-page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .form-row {
        grid-template-columns: 1fr 1fr;
    }

}

@media (max-width: 650px) {

    .form-row {
        grid-template-columns: 1fr;
    }

    .coupon-actions {
        min-width: 130px;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
