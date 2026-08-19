<?php
/**
 * Single Course - Reviews Tab Part View
 */

if (!defined('ABSPATH')) {
    exit;
}

$course_id   = get_the_ID();
$user_id     = get_current_user_id();

// Fetch summary, reviews list, and user's previous votes
$summary     = class_exists('SMLMS_Reviews') ? SMLMS_Reviews::get_rating_summary($course_id) : ['avg_rating' => 0, 'total_count' => 0, 'breakdown' => [5=>0,4=>0,3=>0,2=>0,1=>0]];
$reviews     = class_exists('SMLMS_Reviews') ? SMLMS_Reviews::get_course_reviews($course_id) : [];
$is_eligible = class_exists('SMLMS_Reviews') ? SMLMS_Reviews::is_user_eligible_to_review($user_id, $course_id) : false;
$user_votes  = ($user_id && class_exists('SMLMS_Reviews')) ? SMLMS_Reviews::get_user_course_review_votes($user_id, $course_id) : [];
?>

<div class="smlms-reviews-tab-container">

    <!-- Top Action Button -->
    <?php if ($is_eligible || !$user_id): ?>
        <div class="smlms-reviews-top-action">
            <a href="#smlms-write-review-form" class="smlms-btn-purple smlms-btn-pill">WRITE YOUR REVIEW</a>
        </div>
    <?php endif; ?>

    <!-- Rating Summary Card -->
    <div class="smlms-rating-summary-card">
        
        <!-- Left: Rating Score Box -->
        <div class="smlms-summary-score-box">
            <div class="smlms-big-rating-number"><?php echo esc_html($summary['avg_rating']); ?></div>
            <div class="smlms-star-rating-display">
                <?php 
                $avg_round = round($summary['avg_rating']);
                for ($i = 1; $i <= 5; $i++): 
                    $filled = ($i <= $avg_round) ? 'star-filled' : 'star-empty';
                ?>
                    <span class="dashicons dashicons-star-filled <?php echo esc_attr($filled); ?>"></span>
                <?php endfor; ?>
            </div>
            <div class="smlms-rating-count-text"><?php echo esc_html($summary['total_count']); ?> <?php echo ($summary['total_count'] === 1) ? 'Review' : 'Reviews'; ?></div>
        </div>

        <!-- Right: 5-Star Percentage Progress Bars -->
        <div class="smlms-summary-bars-box">
            <?php foreach ([5, 4, 3, 2, 1] as $star_num): 
                $pct = $summary['breakdown'][$star_num] ?? 0;
            ?>
                <div class="smlms-rating-bar-row">
                    <span class="smlms-star-label"><?php echo $star_num; ?> star</span>
                    <div class="smlms-bar-track">
                        <div class="smlms-bar-fill" style="width: <?php echo esc_attr($pct); ?>%;"></div>
                    </div>
                    <span class="smlms-bar-percentage"><?php echo esc_html($pct); ?> %</span>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <h3 class="smlms-reviews-list-title">Reviews</h3>

    <!-- Filter & Search Controls -->
    <div class="smlms-reviews-filter-bar">
        <div class="smlms-filter-input-wrap search">
            <input type="text" id="smlms-review-search-input" placeholder="Search Reviews">
            <span class="dashicons dashicons-search search-icon"></span>
        </div>

        <div class="smlms-filter-dropdowns-group">
            <div class="smlms-filter-input-wrap dropdown">
                <select id="smlms-review-sort-select">
                    <option value="recent">Most recent</option>
                    <option value="highest">Highest rating</option>
                    <option value="lowest">Lowest rating</option>
                </select>
            </div>

            <div class="smlms-filter-input-wrap dropdown">
                <select id="smlms-review-star-filter">
                    <option value="all">All stars</option>
                    <option value="5">5 stars</option>
                    <option value="4">4 stars</option>
                    <option value="3">3 stars</option>
                    <option value="2">2 stars</option>
                    <option value="1">1 star</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Reviews List -->
    <?php if (empty($reviews)): ?>
        <p class="smlms-no-reviews-msg">There are no reviews for this course yet.</p>
    <?php else: ?>
        <div class="smlms-reviews-list-group" id="smlms-reviews-list-group">
            <?php foreach ($reviews as $rev): 
                $reviewer       = get_userdata($rev->user_id);
                $reviewer_name  = $reviewer ? $reviewer->display_name : 'Anonymous';
                $reviewer_avatar= get_avatar_url($rev->user_id, ['size' => 48]);
                $formatted_date = date('F j, Y', strtotime($rev->created_at));
                $current_vote   = $user_votes[$rev->id] ?? '';
                $helpful_count  = intval($rev->helpful_count);
            ?>
                <div class="smlms-review-item-card" data-rating="<?php echo esc_attr($rev->rating); ?>" data-text="<?php echo esc_attr(strtolower($rev->headline . ' ' . $rev->review_text)); ?>">
                    
                    <div class="smlms-review-item-main">
                        <img src="<?php echo esc_url($reviewer_avatar); ?>" class="smlms-reviewer-avatar" alt="Reviewer Avatar">
                        
                        <div class="smlms-review-item-content">
                            <div class="smlms-review-item-header">
                                <div class="smlms-review-stars-row">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <span class="dashicons dashicons-star-filled <?php echo ($s <= $rev->rating) ? 'star-filled' : 'star-empty'; ?>"></span>
                                    <?php endfor; ?>
                                </div>
                                <strong class="smlms-review-headline-text"><?php echo esc_html($rev->headline); ?></strong>
                            </div>

                            <div class="smlms-review-author-byline">
                                by <strong><?php echo esc_html($reviewer_name); ?></strong> <span class="smlms-byline-sep">|</span> <?php echo esc_html($formatted_date); ?>
                            </div>

                            <div class="smlms-review-item-body">
                                <p><?php echo nl2br(esc_html($rev->review_text)); ?></p>
                            </div>

                            <!-- Helpful Count Line -->
                            <div class="smlms-helpful-count-line" id="smlms-helpful-count-line-<?php echo esc_attr($rev->id); ?>" style="<?php echo ($helpful_count > 0) ? 'display:block;' : 'display:none;'; ?>">
                                <span class="smlms-helpful-count-num"><?php echo esc_html($helpful_count); ?></span> <?php echo ($helpful_count === 1) ? 'person found this helpful' : 'people found this helpful'; ?>
                            </div>

                            <!-- Action Buttons: Helpful, Not Helpful, Comment -->
                            <div class="smlms-review-item-footer">
                                <button type="button" 
                                        class="smlms-btn-vote-action smlms-btn-helpful <?php echo ($current_vote === 'helpful') ? 'active-helpful' : ''; ?>" 
                                        data-review-id="<?php echo esc_attr($rev->id); ?>" 
                                        data-vote="helpful">
                                    <?php echo ($current_vote === 'helpful') ? '✓ Helpful' : 'Helpful'; ?>
                                </button>

                                <button type="button" 
                                        class="smlms-btn-vote-action smlms-btn-not-helpful <?php echo ($current_vote === 'not_helpful') ? 'active-not-helpful' : ''; ?>" 
                                        data-review-id="<?php echo esc_attr($rev->id); ?>" 
                                        data-vote="not_helpful">
                                    <?php echo ($current_vote === 'not_helpful') ? '✕ Not Helpful' : 'Not Helpful'; ?>
                                </button>

                                <span class="smlms-comment-link-text">Comment</span>
                            </div>

                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Write a Review Form Section -->
    <div class="smlms-write-review-section" id="smlms-write-review-form">
        <h3 class="smlms-write-review-title">Write a review</h3>

        <?php if (!$user_id): ?>
            <p class="smlms-review-notice-msg">
                Please <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">log in</a> to write a review.
            </p>
        <?php elseif (!$is_eligible): ?>
            <p class="smlms-review-notice-msg restriction-alert">
                You have not completed lessons required to submit a review for this course.
            </p>
        <?php else: ?>
            <form id="smlms-review-submission-form" class="smlms-review-form-body">
                
                <p class="smlms-rating-prompt-text">How would you rate this course?</p>

                <!-- Interactive Star Picker -->
                <div class="smlms-interactive-star-picker" id="smlms-interactive-star-picker">
                    <span class="dashicons dashicons-star-filled star-pick active" data-value="1"></span>
                    <span class="dashicons dashicons-star-filled star-pick active" data-value="2"></span>
                    <span class="dashicons dashicons-star-filled star-pick active" data-value="3"></span>
                    <span class="dashicons dashicons-star-filled star-pick active" data-value="4"></span>
                    <span class="dashicons dashicons-star-filled star-pick active" data-value="5"></span>
                    <input type="hidden" name="rating" id="smlms_rating_input_val" value="5">
                </div>

                <div class="smlms-form-field-row">
                    <input type="text" name="headline" id="smlms_headline_input" class="smlms-review-text-field" placeholder="Headline" required>
                </div>

                <div class="smlms-form-field-row">
                    <textarea name="review_text" id="smlms_review_textarea" class="smlms-review-textarea" rows="5" placeholder="Write your review here." required></textarea>
                </div>

                <input type="hidden" name="course_id" value="<?php echo esc_attr($course_id); ?>">
                <?php wp_nonce_field('smlms_review_nonce', '_wpnonce'); ?>

                <div class="smlms-form-submit-row">
                    <button type="submit" id="smlms-submit-review-btn" class="smlms-btn-purple smlms-btn-pill">SUBMIT</button>
                </div>

                <div id="smlms-review-feedback-msg" class="smlms-feedback-msg"></div>

            </form>
        <?php endif; ?>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const isUserLoggedIn = <?php echo $user_id ? 'true' : 'false'; ?>;

    // Interactive Star Rating Selection
    $(document).on('click', '.star-pick', function() {
        var val = $(this).data('value');
        $('#smlms_rating_input_val').val(val);

        $('.star-pick').each(function() {
            if ($(this).data('value') <= val) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    });

    // Helpful & Not Helpful Vote Button Handler
    $(document).on('click', '.smlms-btn-vote-action', function(e) {
        e.preventDefault();

        if (!isUserLoggedIn) {
            alert('Please log in to vote on reviews.');
            return;
        }

        var btn      = $(this);
        var card     = btn.closest('.smlms-review-item-card');
        var reviewId = btn.data('review-id');
        var voteType = btn.data('vote');
        var nonce    = $('#_wpnonce').val();

        btn.prop('disabled', true);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'smlms_vote_review',
                review_id: reviewId,
                vote_type: voteType,
                _wpnonce: nonce
            },
            success: function(res) {
                btn.prop('disabled', false);

                if (res.success) {
                    var data = res.data;
                    var helpfulBtn    = card.find('.smlms-btn-helpful');
                    var notHelpfulBtn = card.find('.smlms-btn-not-helpful');
                    var countLine     = $('#smlms-helpful-count-line-' + reviewId);

                    // Reset button states
                    helpfulBtn.removeClass('active-helpful').text('Helpful');
                    notHelpfulBtn.removeClass('active-not-helpful').text('Not Helpful');

                    // Set active selection state
                    if (data.user_vote === 'helpful') {
                        helpfulBtn.addClass('active-helpful').html('&#10003; Helpful');
                    } else if (data.user_vote === 'not_helpful') {
                        notHelpfulBtn.addClass('active-not-helpful').html('&#10005; Not Helpful');
                    }

                    // Update Helpful Count Display
                    if (data.helpful_count > 0) {
                        var txt = data.helpful_count === 1 ? 'person found this helpful' : 'people found this helpful';
                        countLine.html('<span class="smlms-helpful-count-num">' + data.helpful_count + '</span> ' + txt).show();
                    } else {
                        countLine.hide();
                    }

                } else {
                    alert(res.data.message || 'Error processing vote.');
                }
            },
            error: function() {
                btn.prop('disabled', false);
                alert('An error occurred. Please try again.');
            }
        });
    });

    // Review Form AJAX Submission
    $('#smlms-review-submission-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn  = $('#smlms-submit-review-btn');
        var msg  = $('#smlms-review-feedback-msg');

        btn.prop('disabled', true);
        msg.removeClass('success error').text('');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: form.serialize() + '&action=smlms_submit_review',
            success: function(res) {
                btn.prop('disabled', false);
                if (res.success) {
                    msg.addClass('success').text(res.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    msg.addClass('error').text(res.data.message);
                }
            },
            error: function() {
                btn.prop('disabled', false);
                msg.addClass('error').text('An error occurred. Please try again.');
            }
        });
    });

    // Client-side Live Review Search and Filters
    $('#smlms-review-search-input, #smlms-review-star-filter').on('keyup change', function() {
        var searchTerm = $('#smlms-review-search-input').val().toLowerCase();
        var starFilter = $('#smlms-review-star-filter').val();

        $('.smlms-review-item-card').each(function() {
            var itemStar = $(this).data('rating');
            var itemText = $(this).data('text');

            var matchSearch = (searchTerm === '' || itemText.indexOf(searchTerm) > -1);
            var matchStar   = (starFilter === 'all' || itemStar == starFilter);

            if (matchSearch && matchStar) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>