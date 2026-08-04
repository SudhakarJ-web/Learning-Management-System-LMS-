jQuery(document).ready(function($) {

    // 1. Left Sidebar Toggle
    $('#smlms-sidebar-toggle').on('click', function() {
        $('#smlms-focus-sidebar').toggleClass('collapsed');
    });

    // 2. Sidebar Accordion Expand/Collapse
    $(document).on('click', '.smlms-expand-btn', function(e) {
        e.preventDefault();
        const wrapper = $(this).closest('.smlms-lesson-item').find('.smlms-topic-list-wrapper');
        wrapper.slideToggle(150);
        $(this).toggleClass('expanded');
    });

    // 3. Stage Tabs Switcher (Topic vs Materials)
    $('.smlms-stage-tab-btn').on('click', function() {
        $('.smlms-stage-tab-btn').removeClass('active');
        $('.smlms-stage-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
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

            const res = await response.json();
            if (res.can_complete) {
                $('#smlms-mark-complete-btn').removeAttr('disabled');
            }
        } catch(e) {
            console.error('Telemetry error:', e);
        }
    }, 5000);
});