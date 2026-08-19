<?php
/**
 * Sabin Mathew LMS - Admin Course Reviews Native WP List Table
 */

if (!defined('ABSPATH')) {
    exit;
}

class SMLMS_Admin_Reviews {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_submenu_page'], 25);
        add_action('admin_init', [__CLASS__, 'handle_admin_actions']);
    }

    /**
     * Register Reviews Submenu under Sabin Mathew LMS
     */
    public static function register_submenu_page() {
        add_submenu_page(
            'smlms-setup', // Parent menu slug (e.g. 'smlms-setup' or 'sabin-mathew-lms')
            'Course Reviews',
            'Reviews',
            'manage_options',
            'smlms-reviews',
            [__CLASS__, 'render_reviews_page']
        );
    }

    /**
     * Handle Single & Bulk Admin Actions (Approve, Unapprove, Delete)
     */
    public static function handle_admin_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'smlms-reviews') {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $table_reviews = $wpdb->prefix . 'smlms_reviews';
        $table_votes   = $wpdb->prefix . 'smlms_review_votes';

        // 1. Single Item Actions
        if (isset($_GET['action_type']) && isset($_GET['review_id'])) {
            $action_type = sanitize_text_field($_GET['action_type']);
            $review_id   = intval($_GET['review_id']);

            check_admin_referer('smlms_admin_review_' . $review_id);

            if ($action_type === 'approve') {
                $wpdb->update($table_reviews, ['status' => 'approved'], ['id' => $review_id], ['%s'], ['%d']);
                wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'approved'], admin_url('admin.php')));
                exit;
            } elseif ($action_type === 'unapprove') {
                $wpdb->update($table_reviews, ['status' => 'pending'], ['id' => $review_id], ['%s'], ['%d']);
                wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'unapproved'], admin_url('admin.php')));
                exit;
            } elseif ($action_type === 'delete') {
                $wpdb->delete($table_reviews, ['id' => $review_id], ['%d']);
                $wpdb->delete($table_votes, ['review_id' => $review_id], ['%d']);
                wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'deleted'], admin_url('admin.php')));
                exit;
            }
        }

        // 2. Bulk Actions
        if ((isset($_GET['bulk_action']) && $_GET['bulk_action'] !== '-1') || (isset($_GET['bulk_action2']) && $_GET['bulk_action2'] !== '-1')) {
            $bulk_action = ($_GET['bulk_action'] !== '-1') ? sanitize_text_field($_GET['bulk_action']) : sanitize_text_field($_GET['bulk_action2']);
            $review_ids  = isset($_GET['review']) ? array_map('intval', (array)$_GET['review']) : [];

            if (!empty($review_ids)) {
                check_admin_referer('smlms_bulk_reviews_nonce');

                if ($bulk_action === 'approve') {
                    $ids_sql = implode(',', $review_ids);
                    $wpdb->query("UPDATE {$table_reviews} SET status = 'approved' WHERE id IN ({$ids_sql})");
                    wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'bulk_approved'], admin_url('admin.php')));
                    exit;
                } elseif ($bulk_action === 'unapprove') {
                    $ids_sql = implode(',', $review_ids);
                    $wpdb->query("UPDATE {$table_reviews} SET status = 'pending' WHERE id IN ({$ids_sql})");
                    wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'bulk_unapproved'], admin_url('admin.php')));
                    exit;
                } elseif ($bulk_action === 'delete') {
                    $ids_sql = implode(',', $review_ids);
                    $wpdb->query("DELETE FROM {$table_reviews} WHERE id IN ({$ids_sql})");
                    $wpdb->query("DELETE FROM {$table_votes} WHERE review_id IN ({$ids_sql})");
                    wp_redirect(add_query_arg(['page' => 'smlms-reviews', 'message' => 'bulk_deleted'], admin_url('admin.php')));
                    exit;
                }
            }
        }
    }

    /**
     * Render Native WP List Table Page
     */
    public static function render_reviews_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'smlms_reviews';

        // URL Parameters
        $status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'all';
        $course_filter = isset($_GET['course_filter']) ? intval($_GET['course_filter']) : 0;
        $star_filter   = isset($_GET['star_filter']) ? intval($_GET['star_filter']) : 0;
        $search_term   = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        // Status Counts
        $total_all      = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table}"));
        $total_approved = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'approved'"));
        $total_pending  = intval($wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'pending'"));

        // Build Query Conditions
        $where = [];
        if ($status_filter === 'approved') {
            $where[] = "status = 'approved'";
        } elseif ($status_filter === 'pending') {
            $where[] = "status = 'pending'";
        }

        if ($course_filter > 0) {
            $where[] = $wpdb->prepare("course_id = %d", $course_filter);
        }

        if ($star_filter > 0) {
            $where[] = $wpdb->prepare("rating = %d", $star_filter);
        }

        if (!empty($search_term)) {
            $like = '%' . $wpdb->esc_like($search_term) . '%';
            $where[] = $wpdb->prepare("(headline LIKE %s OR review_text LIKE %s)", $like, $like);
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $reviews   = $wpdb->get_results("SELECT * FROM {$table} {$where_sql} ORDER BY created_at DESC");

        // Fetch Courses for Dropdown Filter
        $courses = get_posts([
            'post_type'      => 'smlms_course',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>

        <div class="wrap smlms-admin-reviews-wrap">
            <h1 class="wp-heading-inline">Course Reviews</h1>
            <hr class="wp-header-end">

            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php 
                        switch ($_GET['message']) {
                            case 'approved': echo 'Review approved.'; break;
                            case 'unapproved': echo 'Review set to pending.'; break;
                            case 'deleted': echo 'Review deleted.'; break;
                            case 'bulk_approved': echo 'Selected reviews approved.'; break;
                            case 'bulk_unapproved': echo 'Selected reviews set to pending.'; break;
                            case 'bulk_deleted': echo 'Selected reviews deleted.'; break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Status Navigation Links (All | Published/Approved | Pending) -->
            <ul class="subsubsub">
                <li class="all">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smlms-reviews')); ?>" class="<?php echo ($status_filter === 'all') ? 'current' : ''; ?>">
                        All <span class="count">(<?php echo $total_all; ?>)</span>
                    </a> |
                </li>
                <li class="approved">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smlms-reviews&status_filter=approved')); ?>" class="<?php echo ($status_filter === 'approved') ? 'current' : ''; ?>">
                        Published <span class="count">(<?php echo $total_approved; ?>)</span>
                    </a> |
                </li>
                <li class="pending">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=smlms-reviews&status_filter=pending')); ?>" class="<?php echo ($status_filter === 'pending') ? 'current' : ''; ?>">
                        Pending <span class="count">(<?php echo $total_pending; ?>)</span>
                    </a>
                </li>
            </ul>

            <form id="smlms-reviews-filter-form" method="get">
                <input type="hidden" name="page" value="smlms-reviews">
                <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="status_filter" value="<?php echo esc_attr($status_filter); ?>">
                <?php endif; ?>

                <!-- Top Search Box -->
                <p class="search-box">
                    <label class="screen-reader-text" for="smlms-review-search-input">Search Reviews:</label>
                    <input type="search" id="smlms-review-search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
                    <input type="submit" id="search-submit" class="button" value="Search Reviews">
                </p>

                <!-- Bulk Actions & Dropdown Filters Bar -->
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <?php wp_nonce_field('smlms_bulk_reviews_nonce'); ?>
                        <select name="bulk_action">
                            <option value="-1">Bulk actions</option>
                            <option value="approve">Approve</option>
                            <option value="unapprove">Unapprove</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>

                    <div class="alignleft actions">
                        <!-- Course Filter Dropdown -->
                        <select name="course_filter">
                            <option value="0">All courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?php echo esc_attr($c->ID); ?>" <?php selected($course_filter, $c->ID); ?>>
                                    <?php echo esc_html($c->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- Star Rating Filter Dropdown -->
                        <select name="star_filter">
                            <option value="0">All stars</option>
                            <option value="5" <?php selected($star_filter, 5); ?>>5 stars</option>
                            <option value="4" <?php selected($star_filter, 4); ?>>4 stars</option>
                            <option value="3" <?php selected($star_filter, 3); ?>>3 stars</option>
                            <option value="2" <?php selected($star_filter, 2); ?>>2 stars</option>
                            <option value="1" <?php selected($star_filter, 1); ?>>1 star</option>
                        </select>

                        <input type="submit" class="button" value="Filter">
                    </div>

                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo count($reviews); ?> items</span>
                    </div>
                </div>

                <!-- Main Native WP List Table -->
                <table class="wp-list-table widefat fixed striped table-view-list posts">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <input id="cb-select-all-1" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-title column-primary">Title</th>
                            <th scope="col" class="manage-column column-comments" style="width: 50px;">
                                <span class="vers comment-grey-bubble" title="Comments"></span>
                            </th>
                            <th scope="col" class="manage-column column-ratings-count" style="width: 50px;">
                                <span class="dashicons dashicons-star-filled" style="font-size: 16px; color: #64748b;" title="Helpful Votes"></span>
                            </th>
                            <th scope="col" class="manage-column column-course">Course</th>
                            <th scope="col" class="manage-column column-rating" style="width: 130px;">Rating</th>
                            <th scope="col" class="manage-column column-author" style="width: 150px;">Author</th>
                            <th scope="col" class="manage-column column-date" style="width: 180px;">Date</th>
                        </tr>
                    </thead>

                    <tbody id="the-list">
                        <?php if (empty($reviews)): ?>
                            <tr class="no-items">
                                <td class="colspanchange" colspan="8">No reviews found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): 
                                $user         = get_userdata($rev->user_id);
                                $author_name  = $user ? $user->display_name : 'Guest';
                                $course_title = get_the_title($rev->course_id) ?: 'Deleted Course';
                                $course_link  = get_permalink($rev->course_id);

                                $title_display = !empty($rev->headline) ? esc_html($rev->headline) : '(no title)';
                                if ($rev->status === 'pending') {
                                    $title_display .= ' — <span class="post-state" style="color: #b45309;">Pending</span>';
                                }

                                $approve_url = wp_nonce_url(
                                    add_query_arg(['page' => 'smlms-reviews', 'action_type' => 'approve', 'review_id' => $rev->id], admin_url('admin.php')),
                                    'smlms_admin_review_' . $rev->id
                                );
                                $unapprove_url = wp_nonce_url(
                                    add_query_arg(['page' => 'smlms-reviews', 'action_type' => 'unapprove', 'review_id' => $rev->id], admin_url('admin.php')),
                                    'smlms_admin_review_' . $rev->id
                                );
                                $delete_url = wp_nonce_url(
                                    add_query_arg(['page' => 'smlms-reviews', 'action_type' => 'delete', 'review_id' => $rev->id], admin_url('admin.php')),
                                    'smlms_admin_review_' . $rev->id
                                );
                            ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="review[]" value="<?php echo esc_attr($rev->id); ?>">
                                    </th>

                                    <!-- Title & Row Actions -->
                                    <td class="title column-title has-row-actions column-primary page-title">
                                        <strong>
                                            <a class="row-title" href="<?php echo esc_url($course_link); ?>#reviews" target="_blank">
                                                <?php echo $title_display; ?>
                                            </a>
                                        </strong>
                                        
                                        <div class="row-actions">
                                            <?php if ($rev->status === 'approved'): ?>
                                                <span class="unapprove"><a href="<?php echo esc_url($unapprove_url); ?>">Unapprove</a> | </span>
                                            <?php else: ?>
                                                <span class="approve"><a href="<?php echo esc_url($approve_url); ?>" style="color: #15803d; font-weight: 600;">Approve</a> | </span>
                                            <?php endif; ?>
                                            <span class="trash"><a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" onclick="return confirm('Are you sure you want to delete this review?');">Delete</a> | </span>
                                            <span class="view"><a href="<?php echo esc_url($course_link); ?>" target="_blank">View Course</a></span>
                                        </div>
                                    </td>

                                    <!-- Comments Count -->
                                    <td class="comments column-comments">
                                        <div class="post-com-count-wrapper">
                                            <span class="post-com-count post-com-count-no-comments">
                                                <span class="comment-count-approved">0</span>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Helpful Votes Count -->
                                    <td class="ratings-count column-ratings-count">
                                        <span class="post-com-count" style="background-color: #f1f5f9; color: #475569;">
                                            <span class="comment-count-approved"><?php echo esc_html($rev->helpful_count); ?></span>
                                        </span>
                                    </td>

                                    <!-- Course Name Link -->
                                    <td class="course column-course">
                                        <a href="<?php echo esc_url($course_link); ?>" target="_blank">
                                            <?php echo esc_html($course_title); ?>
                                        </a>
                                    </td>

                                    <!-- Gold Stars Rating -->
                                    <td class="rating column-rating">
                                        <span style="color: #f59e0b; font-size: 15px; letter-spacing: 1px;">
                                            <?php 
                                            for ($s = 1; $s <= 5; $s++) {
                                                echo ($s <= $rev->rating) ? '★' : '☆';
                                            }
                                            ?>
                                        </span>
                                    </td>

                                    <!-- Author Name -->
                                    <td class="author column-author">
                                        <strong><?php echo esc_html($author_name); ?></strong>
                                    </td>

                                    <!-- Date -->
                                    <td class="date column-date">
                                        Published<br>
                                        <abbr title="<?php echo esc_attr($rev->created_at); ?>">
                                            <?php echo date('Y/m/d \a\t g:i a', strtotime($rev->created_at)); ?>
                                        </abbr>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input id="cb-select-all-2" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-title column-primary">Title</th>
                            <th scope="col" class="manage-column column-comments"><span class="vers comment-grey-bubble"></span></th>
                            <th scope="col" class="manage-column column-ratings-count"><span class="dashicons dashicons-star-filled" style="font-size: 16px; color: #64748b;"></span></th>
                            <th scope="col" class="manage-column column-course">Course</th>
                            <th scope="col" class="manage-column column-rating">Rating</th>
                            <th scope="col" class="manage-column column-author">Author</th>
                            <th scope="col" class="manage-column column-date">Date</th>
                        </tr>
                    </tfoot>
                </table>

                <!-- Bottom Bulk Actions Bar -->
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action2">
                            <option value="-1">Bulk actions</option>
                            <option value="approve">Approve</option>
                            <option value="unapprove">Unapprove</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo count($reviews); ?> items</span>
                    </div>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Check All Checkboxes Toggle
            $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
                var checked = $(this).prop('checked');
                $('input[name="review[]"]').prop('checked', checked);
                $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
            });
        });
        </script>
        <?php
    }
}

SMLMS_Admin_Reviews::init();