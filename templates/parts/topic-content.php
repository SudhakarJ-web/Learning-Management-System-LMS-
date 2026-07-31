<?php
if (!defined('ABSPATH')) exit;

// Expects $topic_id, $video_id, $materials, $current_user passed from parent scope
?>

<div id="smlms-stage-content">
    <header class="smlms-top-bar">
        <h1 id="smlms-topic-heading"><?php the_title(); ?></h1>
        <button id="smlms-mark-complete-btn" class="smlms-btn smlms-btn-success" disabled>
            Mark Complete
        </button>
    </header>

    <!-- Secure Video Area -->
    <div class="smlms-video-stage">
        <div id="smlms-player-container" data-video-id="<?php echo esc_attr($video_id); ?>">
            <iframe 
                id="smlms-iframe-player"
                src="https://iframe.videodelivery.net/<?php echo esc_attr($video_id); ?>?preload=true" 
                allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;" 
                allowfullscreen="true">
            </iframe>
        </div>
        <!-- Anti-Piracy Floating Watermark -->
        <div class="smlms-watermark-overlay" id="smlms-watermark">
            <span><?php echo esc_html($current_user->user_email); ?> | IP: <?php echo esc_html($_SERVER['REMOTE_ADDR']); ?></span>
        </div>
    </div>

    <!-- Details / Materials Tabs -->
    <div class="smlms-tabs-wrapper">
        <div class="smlms-tab-buttons">
            <button class="smlms-tab-btn active" data-target="#smlms-tab-overview">Overview</button>
            <button class="smlms-tab-btn" data-target="#smlms-tab-materials">Materials & Links</button>
        </div>

        <div class="smlms-tab-body">
            <div id="smlms-tab-overview" class="smlms-tab-pane active">
                <?php the_content(); ?>
            </div>
            <div id="smlms-tab-materials" class="smlms-tab-pane">
                <div class="smlms-materials-content">
                    <?php echo !empty($materials) ? wp_kses_post($materials) : '<p>No supplementary materials for this topic.</p>'; ?>
                </div>
            </div>
        </div>
    </div>
</div>