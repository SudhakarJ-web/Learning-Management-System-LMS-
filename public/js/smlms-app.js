/**
 * Sabin Mathew LMS - Focus Mode Frontend Application Script
 */
jQuery(document).ready(function($) {

    // 1. Sidebar Collapse/Expand Toggle
    $(document).on('click', '#smlms-sidebar-toggle', function(e) {
        e.preventDefault();
        $('#smlms-focus-sidebar, .smlms-focus-sidebar').toggleClass('collapsed');
    });

    // 2. Sidebar Accordion Expand/Collapse (LearnDash Style Tree)
    $(document).on('click', '.smlms-ld-toggle-btn, .smlms-expand-btn', function(e) {
        e.preventDefault();
        
        const lessonCard = $(this).closest('.smlms-ld-lesson-card, .smlms-lesson-item');
        const topicWrapper = lessonCard.find('.smlms-ld-topics-wrapper, .smlms-topic-list-wrapper');

        topicWrapper.slideToggle(150);
        $(this).toggleClass('active expanded');
    });

    // 3. Stage Tabs Switcher (Topic vs Materials)
    $(document).on('click', '.smlms-stage-tab-btn, .smlms-tab-btn', function(e) {
        e.preventDefault();

        $('.smlms-stage-tab-btn, .smlms-tab-btn').removeClass('active');
        $('.smlms-stage-tab-pane, .smlms-tab-pane').removeClass('active');

        $(this).addClass('active');
        const target = $(this).data('target');
        if (target && $(target).length) {
            $(target).addClass('active');
        }
    });

    // 4. Animate Anti-Piracy Watermark
    const watermark = document.getElementById('smlms-watermark');
    if (watermark) {
        setInterval(() => {
            const top = Math.floor(Math.random() * 70) + 10;
            const left = Math.floor(Math.random() * 70) + 10;
            watermark.style.top = `${top}%`;
            watermark.style.left = `${left}%`;
        }, 10000);
    }

    // 5. Telemetry Pulse to REST API
    if (typeof smlmsSettings !== 'undefined' && smlmsSettings.root && smlmsSettings.current_id) {
        setInterval(async () => {
            const topicId = smlmsSettings.current_id;
            if (!topicId) return;

            try {
                const response = await fetch(`${smlmsSettings.root}progress/heartbeat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': smlmsSettings.nonce
                    },
                    body: JSON.stringify({
                        topic_id: topicId,
                        watched_seconds: 5,
                        total_duration: 300
                    })
                });

                if (response.ok) {
                    const res = await response.json();
                    if (res.can_complete) {
                        $('#smlms-mark-complete-btn').prop('disabled', false).removeAttr('disabled');
                    }
                }
            } catch(e) {
                console.error('Telemetry error:', e);
            }
        }, 5000);
    }
});