<?php
// Expects $vehicle_id and $user_id to be available in scope
// Handle Review Submission
$review_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!empty($_SESSION['user_id'])) {
        $r_rating = intval($_POST['rating']);
        $r_comment = mysqli_real_escape_string($conn, trim($_POST['comment']));
        $u_id = $_SESSION['user_id'];
        
        // Check availability of a completed booking (mock logic for MVP: Allow if ANY booking exists or just allow logged in for demo)
        // Ideally: data check. For MVP ease-of-testing: Allow all logged in users.
        $can_review = true; 
        
        if ($can_review) {
            // Need a booking_id. Let's find latest booking or insert strict fake one if none?
            // Schema requires booking_id.
            // Check for existing booking
            $b_check = mysqli_query($conn, "SELECT id FROM bookings WHERE user_id=$u_id AND vehicle_id=$vehicle_id LIMIT 1");
            $b_row = mysqli_fetch_assoc($b_check);
            
            if ($b_row) {
                $bid = $b_row['id'];
                $r_sql = "INSERT INTO reviews (booking_id, user_id, vehicle_id, rating, comment) VALUES ($bid, $u_id, $vehicle_id, $r_rating, '$r_comment')";
                if (mysqli_query($conn, $r_sql)) {
                    $review_msg = "Review submitted successfully!";
                } else {
                    $review_msg = "Error submitting review.";
                }
            } else {
                $review_msg = "You need to book this car before reviewing it.";
            }
        }
    } else {
        header("Location: auth/login.html"); exit;
    }
}

// Fetch Reviews
$rev_sql = "SELECT r.*, u.name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.vehicle_id = $vehicle_id ORDER BY r.created_at DESC";
$rev_res = mysqli_query($conn, $rev_sql);
?>

<div class="reviews-section" style="margin-top:40px;padding-top:20px;border-top:1px solid #eee;">
    <h3>Reviews & Ratings</h3>
    
    <?php if ($review_msg): ?>
        <div style="background:#e2e6ea;padding:10px;margin-bottom:15px;border-radius:4px;"><?= $review_msg ?></div>
    <?php endif; ?>

    <div class="reviews-list">
        <?php if (mysqli_num_rows($rev_res) > 0): ?>
            <?php while ($rev = mysqli_fetch_assoc($rev_res)): ?>
                <div class="review-item" style="margin-bottom:20px;background:#f9f9f9;padding:15px;border-radius:8px;">
                    <div style="display:flex;justify-content:space-between;">
                        <strong><?= htmlspecialchars($rev['name']) ?></strong>
                        <span style="color:#f1c40f;">
                            <?= str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']) ?>
                        </span>
                    </div>
                    <p style="margin:5px 0 0;color:#555;"><?= htmlspecialchars($rev['comment']) ?></p>
                    <small style="color:#999;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to rent and review!</p>
        <?php endif; ?>
    </div>

    <!-- Review Form -->
    <?php if (!empty($_SESSION['user_id'])): ?>
    <div class="review-form" style="margin-top:30px;background:#fff;border:1px solid #ddd;padding:20px;border-radius:8px;">
        <h4>Leave a Review</h4>
        <form method="POST">
            <input type="hidden" name="submit_review" value="1">
            <div style="margin-bottom:10px;">
                <label>Rating:</label>
                <select name="rating" style="padding:5px;">
                    <option value="5">★★★★★ (Excellent)</option>
                    <option value="4">★★★★ (Good)</option>
                    <option value="3">★★★ (Average)</option>
                    <option value="2">★★ (Poor)</option>
                    <option value="1">★ (Terrible)</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <textarea name="comment" rows="3" placeholder="Share your experience..." style="width:100%;padding:10px;border-radius:4px;border:1px solid #ccc;" required></textarea>
            </div>
            <button class="btn-primary" type="submit">Submit Review</button>
        </form>
    </div>
    <?php endif; ?>
</div>
