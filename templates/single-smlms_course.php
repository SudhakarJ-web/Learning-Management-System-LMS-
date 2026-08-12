<?php
/**
 * Single Course Landing Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$course_id   = get_the_ID();
$user_id     = get_current_user_id();
$is_enrolled = $user_id ? SMLMS_DB::is_user_enrolled($user_id, $course_id) : false;
$access_type = get_post_meta($course_id, '_smlms_access_type', true) ?: 'closed';

// Strict Access Control Rules
if ($access_type === 'open') {
    $has_access = true;
} elseif ($access_type === 'free') {
    $has_access = ($user_id > 0) && (current_user_can('manage_options') || $is_enrolled);
} else {
    $has_access = current_user_can('manage_options') || $is_enrolled;
}

// Author Lookup
$author_id   = get_post_field('post_author', $course_id);
$author_name = get_the_author_meta('display_name', $author_id) ?: 'Sabin Mathew';
$author_bio  = get_the_author_meta('description', $author_id);

// Hierarchy Lookup
$hierarchy = SMLMS_DB::get_course_hierarchy($course_id, $user_id);

// First Step URL Lookup
$first_step_url = get_permalink($course_id);
if (!empty($hierarchy[0]['permalink'])) {
    $first_step_url = $hierarchy[0]['permalink'];
}

// Media & Button Details
$price      = get_post_meta($course_id, '_smlms_price', true);
$button_url = get_post_meta($course_id, '_smlms_custom_checkout_url', true);
if (empty($button_url)) {
    $button_url = get_post_meta($course_id, '_smlms_button_url', true) ?: '#';
}

// Media Embed Video Parser (Vimeo ID / Vimeo URL / YouTube / Iframe)
$raw_embed = get_post_meta($course_id, '_smlms_media_embed', true);
$embed_url = '';

if (!empty($raw_embed)) {
    $raw_embed = trim($raw_embed);
    if (strpos($raw_embed, '<iframe') !== false) {
        preg_match('/src=["\']([^"\']+)["\']/', $raw_embed, $matches);
        $embed_url = !empty($matches[1]) ? $matches[1] : '';
    } elseif (is_numeric($raw_embed)) {
        $embed_url = 'https://player.vimeo.com/video/' . $raw_embed;
    } elseif (strpos($raw_embed, 'vimeo.com') !== false) {
        preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $raw_embed, $matches);
        $embed_url = !empty($matches[1]) ? 'https://player.vimeo.com/video/' . $matches[1] : esc_url($raw_embed);
    } elseif (strpos($raw_embed, 'youtube.com') !== false || strpos($raw_embed, 'youtu.be') !== false) {
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/', $raw_embed, $matches);
        $embed_url = !empty($matches[1]) ? 'https://www.youtube.com/embed/' . $matches[1] : esc_url($raw_embed);
    } else {
        $embed_url = esc_url($raw_embed);
    }
}

$context = [
    'course_id'      => $course_id,
    'user_id'        => $user_id,
    'is_enrolled'    => $is_enrolled,
    'has_access'     => $has_access,
    'access_type'    => $access_type,
    'hierarchy'      => $hierarchy,
    'first_step_url' => $first_step_url,
    'price'          => $price,
    'button_url'     => $button_url,
    'embed_url'      => $embed_url,
    'author_id'      => $author_id,
    'author_name'    => $author_name,
];
?>

<div class="smlms-single-course-page">

    <!-- Hero Header Banner -->
    <?php include SMLMS_PLUGIN_DIR . 'templates/parts/course-hero.php'; ?>

    <!-- Navigation Tabs Bar -->
    <nav class="smlms-course-nav-tabs">
        <div class="smlms-tabs-container">
            <button class="smlms-course-tab-btn active" type="button" data-target="#tab-curriculum">Curriculum</button>
            <button class="smlms-course-tab-btn" type="button" data-target="#tab-overview">Overview</button>
            <button class="smlms-course-tab-btn" type="button" data-target="#tab-instructors">Instructors</button>
            <button class="smlms-course-tab-btn" type="button" data-target="#tab-reviews">Reviews</button>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="smlms-course-body-container">
        <main class="smlms-main-column">
            
            <!-- Curriculum Tab Pane -->
            <?php include SMLMS_PLUGIN_DIR . 'templates/parts/tab-curriculum.php'; ?>

            <!-- Overview Tab Pane -->
            <div id="tab-overview" class="smlms-course-tab-pane">
                <div class="smlms-tab-content-inner" style="background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0; line-height: 1.6;">
                    <?php 
                    while (have_posts()) : the_post();
                        the_content();
                    endwhile;
                    ?>
                </div>
            </div>

            <!-- Instructors Tab Pane -->
            <div id="tab-instructors" class="smlms-course-tab-pane">
                <div class="smlms-instructor-card-box">
                    <div class="smlms-instructor-header">
                        <?php echo get_avatar($author_id, 80, '', 'Instructor Avatar', ['class' => 'smlms-instructor-avatar']); ?>
                        <div class="smlms-instructor-info">
                            <h3 class="smlms-instructor-name"><?php echo esc_html($author_name); ?></h3>
                            <span class="smlms-instructor-role">Course Instructor</span>
                        </div>
                    </div>
                    <?php if (!empty($author_bio)): ?>
                        <div class="smlms-instructor-bio">
                            <?php echo wpautop(esc_html($author_bio)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews Tab Pane -->
            <div id="tab-reviews" class="smlms-course-tab-pane">
                <div class="smlms-tab-content-inner" style="background: #ffffff; padding: 30px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h3>Student Reviews</h3>
                    <p>No reviews posted yet.</p>
                </div>
            </div>

        </main>
    </div>

</div>

<!-- Lightbox Modal Container for Video Preview -->
<div id="smlms-video-modal" class="smlms-video-modal">
    <div class="smlms-video-modal-backdrop" onclick="smlmsCloseCourseVideo()"></div>
    <div class="smlms-video-modal-dialog">
        <button type="button" class="smlms-modal-close-btn" onclick="smlmsCloseCourseVideo()">&times;</button>
        <div class="smlms-video-responsive-wrap" id="smlms-modal-player-container"></div>
    </div>
</div>

<script>
function smlmsOpenCourseVideo(element) {
    var rawEmbed = jQuery(element).attr('data-embed');
    if (!rawEmbed) return;

    var playerWrap = jQuery('#smlms-modal-player-container');
    var modal = jQuery('#smlms-video-modal');

    rawEmbed = rawEmbed.trim();
    var finalUrl = '';

    if (rawEmbed.indexOf('<iframe') !== -1) {
        var match = rawEmbed.match(/src=["']([^"']+)["']/);
        finalUrl = match ? match[1] : '';
    } else if (/^\d+$/.test(rawEmbed)) {
        finalUrl = 'https://player.vimeo.com/video/' + rawEmbed;
    } else if (rawEmbed.indexOf('vimeo.com') !== -1) {
        var matchVimeo = rawEmbed.match(/vimeo\.com\/(?:video\/)?([0-9]+)/);
        finalUrl = matchVimeo ? 'https://player.vimeo.com/video/' + matchVimeo[1] : rawEmbed;
    } else if (rawEmbed.indexOf('youtube.com') !== -1 || rawEmbed.indexOf('youtu.be') !== -1) {
        var matchYt = rawEmbed.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
        finalUrl = matchYt ? 'https://www.youtube.com/embed/' . matchYt[1] : rawEmbed;
    } else {
        finalUrl = rawEmbed;
    }

    if (finalUrl) {
        playerWrap.html('<iframe src="' + finalUrl + '" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>');
        modal.addClass('active');
    }
}

function smlmsCloseCourseVideo() {
    jQuery('#smlms-video-modal').removeClass('active');
    jQuery('#smlms-modal-player-container').html('');
}
</script>

<?php
get_footer();