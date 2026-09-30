<?php

require_once __DIR__ . '/../includes/db.php';

requireAdmin();

$pageTitle = 'Manage Reviews - CholoGhuri';

$errors = [];


/*
|--------------------------------------------------------------------------
| Delete Review
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {

    $reviewId = (int) ($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {

        $errors[] = 'Invalid review ID.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM reviews
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$reviewId]);

        if (!$stmt->fetch()) {

            $errors[] = 'Review not found.';

        } else {

            $stmt = $pdo->prepare("
                DELETE FROM reviews
                WHERE id = ?
            ");

            $stmt->execute([$reviewId]);

            redirectWithMessage(
                'reviews.php',
                'Review deleted successfully.',
                'success'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$ratingFilter = $_GET['rating'] ?? '';
$packageFilter = (int) ($_GET['package_id'] ?? 0);
$search = trim($_GET['search'] ?? '');


if (
    $ratingFilter !== '' &&
    !in_array(
        (int) $ratingFilter,
        [1, 2, 3, 4, 5],
        true
    )
) {

    $ratingFilter = '';
}


/*
|--------------------------------------------------------------------------
| Fetch Packages for Filter
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        title,
        destination
    FROM tour_packages
    ORDER BY title ASC
");

$packages = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Build Review Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        r.*,

        u.name AS user_name,
        u.email AS user_email,
        u.profile_image,

        tp.title AS package_title,
        tp.destination AS package_destination

    FROM reviews r

    INNER JOIN users u
        ON u.id = r.user_id

    INNER JOIN tour_packages tp
        ON tp.id = r.package_id

    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| Rating Filter
|--------------------------------------------------------------------------
*/

if ($ratingFilter !== '') {

    $sql .= "
        AND r.rating = ?
    ";

    $params[] = (int) $ratingFilter;
}


/*
|--------------------------------------------------------------------------
| Package Filter
|--------------------------------------------------------------------------
*/

if ($packageFilter > 0) {

    $sql .= "
        AND r.package_id = ?
    ";

    $params[] = $packageFilter;
}


/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR tp.title LIKE ?
            OR tp.destination LIKE ?
            OR r.comment LIKE ?
        )
    ";

    $searchLike = '%' . $search . '%';

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}


$sql .= "
    ORDER BY r.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$reviews = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Review Statistics
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_reviews,

        COALESCE(
            AVG(rating),
            0
        ) AS average_rating,

        SUM(
            CASE
                WHEN rating = 5
                THEN 1
                ELSE 0
            END
        ) AS five_star,

        SUM(
            CASE
                WHEN rating = 4
                THEN 1
                ELSE 0
            END
        ) AS four_star,

        SUM(
            CASE
                WHEN rating = 3
                THEN 1
                ELSE 0
            END
        ) AS three_star,

        SUM(
            CASE
                WHEN rating = 2
                THEN 1
                ELSE 0
            END
        ) AS two_star,

        SUM(
            CASE
                WHEN rating = 1
                THEN 1
                ELSE 0
            END
        ) AS one_star

    FROM reviews r
");

$reviewStats = $stmt->fetch();


include __DIR__ . '/header.php';

?>


<div class="admin-page">


    <!-- Page Header -->

    <div class="admin-page-header">

        <div>

            <span class="section-tag">
                Review Management
            </span>

            <h2>
                Customer Reviews
            </h2>

            <p>
                Monitor ratings and customer feedback.
            </p>

        </div>

    </div>


    <!-- Statistics -->

    <div class="admin-stats">


        <div class="stat-card">

            <div class="stat-icon">
                ⭐
            </div>

            <div>

                <span>
                    Total Reviews
                </span>

                <strong>
                    <?= (int) (
                        $reviewStats['total_reviews']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🌟
            </div>

            <div>

                <span>
                    Average Rating
                </span>

                <strong>
                    <?= number_format(
                        (float) (
                            $reviewStats['average_rating']
                            ?? 0
                        ),
                        1
                    ) ?>
                    / 5
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🟢
            </div>

            <div>

                <span>
                    5 Star Reviews
                </span>

                <strong>
                    <?= (int) (
                        $reviewStats['five_star']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                🔴
            </div>

            <div>

                <span>
                    1 Star Reviews
                </span>

                <strong>
                    <?= (int) (
                        $reviewStats['one_star']
                        ?? 0
                    ) ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- Rating Breakdown -->

    <div class="admin-card rating-breakdown-card">

        <div class="card-header">

            <div>

                <h3>
                    Rating Breakdown
                </h3>

                <p>
                    Distribution of customer ratings.
                </p>

            </div>

        </div>


        <div class="rating-breakdown">


            <?php

            $ratingCounts = [
                5 => (int) (
                    $reviewStats['five_star'] ?? 0
                ),
                4 => (int) (
                    $reviewStats['four_star'] ?? 0
                ),
                3 => (int) (
                    $reviewStats['three_star'] ?? 0
                ),
                2 => (int) (
                    $reviewStats['two_star'] ?? 0
                ),
                1 => (int) (
                    $reviewStats['one_star'] ?? 0
                )
            ];

            $totalReviews =
                (int) (
                    $reviewStats['total_reviews']
                    ?? 0
                );

            ?>


            <?php foreach (
                $ratingCounts as $rating => $count
            ): ?>

                <?php

                $percentage =
                    $totalReviews > 0
                        ? ($count / $totalReviews) * 100
                        : 0;

                ?>

                <div class="rating-breakdown-row">


                    <div class="rating-label">

                        <strong>
                            <?= $rating ?>
                        </strong>

                        <span>
                            ⭐
                        </span>

                    </div>


                    <div class="rating-progress">

                        <div
                            class="rating-progress-fill"
                            style="width: <?= $percentage ?>%;"
                        ></div>

                    </div>


                    <div class="rating-count">

                        <?= $count ?>

                    </div>


                </div>

            <?php endforeach; ?>


        </div>

    </div>


    <!-- Filters -->

    <div class="admin-card review-filter-card">

        <div class="card-header">

            <div>

                <h3>
                    Search & Filter
                </h3>

                <p>
                    Find specific customer reviews.
                </p>

            </div>

        </div>


        <form
            action="reviews.php"
            method="GET"
            class="review-filter-form"
        >


            <div class="form-group">

                <label for="search">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="<?= e($search) ?>"
                    placeholder="User, email, package or comment..."
                >

            </div>


            <div class="form-group">

                <label for="rating">
                    Rating
                </label>

                <select
                    id="rating"
                    name="rating"
                    class="form-control"
                >

                    <option value="">
                        All Ratings
                    </option>

                    <?php for (
                        $i = 5;
                        $i >= 1;
                        $i--
                    ): ?>

                        <option
                            value="<?= $i ?>"
                            <?= (int) $ratingFilter === $i
                                ? 'selected'
                                : '' ?>
                        >
                            <?= $i ?> Star
                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="package_id">
                    Package
                </label>

                <select
                    id="package_id"
                    name="package_id"
                    class="form-control"
                >

                    <option value="">
                        All Packages
                    </option>

                    <?php foreach (
                        $packages as $package
                    ): ?>

                        <option
                            value="<?= (int) (
                                $package['id']
                            ) ?>"
                            <?= $packageFilter ===
                                (int) $package['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= e(
                                $package['title']
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="review-filter-actions">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    🔍 Filter
                </button>

                <a
                    href="reviews.php"
                    class="btn btn-outline"
                >
                    Reset
                </a>

            </div>


        </form>

    </div>


    <!-- Reviews -->

    <div class="admin-card">

        <div class="card-header">

            <div>

                <h3>
                    All Reviews
                </h3>

                <p>
                    <?= count($reviews) ?>
                    review(s) found
                </p>

            </div>

        </div>


        <?php if (empty($reviews)): ?>


            <div class="empty-state">

                <div class="empty-icon">
                    ⭐
                </div>

                <h3>
                    No Reviews Found
                </h3>

                <p>
                    No customer review matches your filters.
                </p>

            </div>


        <?php else: ?>


            <div class="review-admin-list">


                <?php foreach (
                    $reviews as $review
                ): ?>


                    <div class="admin-review-card">


                        <!-- User -->

                        <div class="admin-review-user">


                            <div class="admin-review-avatar">

                                <?php if (
                                    !empty(
                                        $review['profile_image']
                                    )
                                ): ?>

                                    <img
                                        src="../<?= e(
                                            $review[
                                                'profile_image'
                                            ]
                                        ) ?>"
                                        alt="<?= e(
                                            $review['user_name']
                                        ) ?>"
                                    >

                                <?php else: ?>

                                    <?= e(
                                        strtoupper(
                                            substr(
                                                $review[
                                                    'user_name'
                                                ],
                                                0,
                                                1
                                            )
                                        )
                                    ) ?>

                                <?php endif; ?>

                            </div>


                            <div>

                                <strong>
                                    <?= e(
                                        $review['user_name']
                                    ) ?>
                                </strong>

                                <small>
                                    <?= e(
                                        $review['user_email']
                                    ) ?>
                                </small>

                            </div>

                        </div>


                        <!-- Review Content -->

                        <div class="admin-review-content">


                            <div class="admin-review-top">


                                <div>

                                    <strong class="review-package-name">
                                        <?= e(
                                            $review[
                                                'package_title'
                                            ]
                                        ) ?>
                                    </strong>

                                    <span class="review-location">
                                        📍
                                        <?= e(
                                            $review[
                                                'package_destination'
                                            ]
                                        ) ?>
                                    </span>

                                </div>


                                <div class="admin-review-rating">

                                    <?php for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ): ?>

                                        <span
                                            class="<?= $i <=
                                                (int) $review['rating']
                                                ? 'star-filled'
                                                : 'star-empty' ?>"
                                        >
                                            ★
                                        </span>

                                    <?php endfor; ?>

                                    <strong>
                                        <?= (int) (
                                            $review['rating']
                                        ) ?>/5
                                    </strong>

                                </div>


                            </div>


                            <?php if (
                                !empty(
                                    $review['comment']
                                )
                            ): ?>

                                <p class="admin-review-comment">
                                    “<?= e(
                                        $review['comment']
                                    ) ?>”
                                </p>

                            <?php else: ?>

                                <p class="admin-review-no-comment">
                                    No written comment provided.
                                </p>

                            <?php endif; ?>


                            <div class="admin-review-footer">

                                <span>
                                    📅
                                    <?= date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $review[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>
                                </span>


                                <form
                                    action="reviews.php"
                                    method="POST"
                                >

                                    <input
                                        type="hidden"
                                        name="review_id"
                                        value="<?= (int) (
                                            $review['id']
                                        ) ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_review"
                                        class="btn btn-danger btn-sm"
                                        data-confirm="Are you sure you want to delete this review?"
                                    >
                                        Delete Review
                                    </button>

                                </form>

                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>


    <!-- Information -->

    <div class="admin-info-box">

        <strong>
            ℹ️ Review Management
        </strong>

        <p>
            Deleted reviews are permanently removed from the system.
            The package's average rating will automatically update
            because ratings are calculated directly from the
            <strong>reviews</strong> table.
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

.rating-breakdown-card {
    margin-bottom: 25px;
}

.rating-breakdown {
    max-width: 700px;
}

.rating-breakdown-row {
    display: grid;
    grid-template-columns: 55px 1fr 45px;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.rating-label {
    display: flex;
    align-items: center;
    gap: 5px;
}

.rating-progress {
    height: 9px;
    background: #edf0f5;
    border-radius: 10px;
    overflow: hidden;
}

.rating-progress-fill {
    height: 100%;
    background: #f5b301;
    border-radius: 10px;
}

.rating-count {
    text-align: right;
    font-size: 13px;
    color: #666;
}

.review-filter-card {
    margin-bottom: 25px;
}

.review-filter-form {
    display: grid;
    grid-template-columns:
        2fr 1fr 1.5fr auto;
    gap: 15px;
    align-items: end;
}

.review-filter-form .form-group {
    margin: 0;
}

.review-filter-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.review-admin-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.admin-review-card {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 25px;
    padding: 20px;
    border: 1px solid #e7e7e7;
    border-radius: 12px;
    background: #fff;
}

.admin-review-user {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.admin-review-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #eef3ff;
    color: #3157d5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    overflow: hidden;
    flex-shrink: 0;
}

.admin-review-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.admin-review-user strong {
    display: block;
    margin-bottom: 3px;
}

.admin-review-user small {
    color: #777;
    font-size: 11px;
    word-break: break-word;
}

.admin-review-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}

.review-package-name {
    display: block;
}

.review-location {
    display: block;
    color: #777;
    font-size: 12px;
    margin-top: 4px;
}

.admin-review-rating {
    display: flex;
    align-items: center;
    gap: 2px;
    white-space: nowrap;
}

.admin-review-rating strong {
    margin-left: 7px;
    font-size: 13px;
}

.star-filled {
    color: #f5b301;
}

.star-empty {
    color: #d9dde5;
}

.admin-review-comment {
    margin: 18px 0;
    line-height: 1.7;
    color: #444;
    font-size: 14px;
}

.admin-review-no-comment {
    margin: 18px 0;
    color: #999;
    font-style: italic;
}

.admin-review-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    padding-top: 12px;
    border-top: 1px solid #eee;
    color: #777;
    font-size: 12px;
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

    .review-filter-form {
        grid-template-columns: 1fr 1fr;
    }

    .review-filter-actions {
        grid-column: 1 / -1;
    }

}

@media (max-width: 700px) {

    .review-filter-form {
        grid-template-columns: 1fr;
    }

    .review-filter-actions {
        grid-column: auto;
    }

    .review-filter-actions .btn {
        flex: 1;
    }

    .admin-review-card {
        grid-template-columns: 1fr;
        gap: 15px;
    }

    .admin-review-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .admin-review-footer {
        align-items: flex-start;
        flex-direction: column;
    }

}

</style>


<?php include __DIR__ . '/footer.php'; ?>
