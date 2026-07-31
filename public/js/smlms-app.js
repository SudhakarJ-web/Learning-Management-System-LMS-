document.addEventListener('DOMContentLoaded', () => {
    let currentWatchedSeconds = 0;
    let totalVideoDuration = 0;

    // 1. Tab Switching Logic
    const tabButtons = document.querySelectorAll('.smlms-tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.smlms-tab-pane').forEach(pane => pane.classList.remove('active'));

            button.classList.add('active');
            const targetPane = document.querySelector(button.dataset.target);
            if (targetPane) targetPane.classList.add('active');
        });
    });

    // 2. Animate Anti-Piracy Watermark across screen
    const watermark = document.getElementById('smlms-watermark');
    if (watermark) {
        setInterval(() => {
            const top = Math.floor(Math.random() * 70) + 10;
            const left = Math.floor(Math.random() * 70) + 10;
            watermark.style.top = `${top}%`;
            watermark.style.left = `${left}%`;
        }, 12000);
    }

    // 3. Telemetry Pulse to REST API
    setInterval(async () => {
        const topicLink = document.querySelector('.smlms-topic-active .smlms-topic-link');
        if (!topicLink) return;

        const topicId = topicLink.dataset.topicId;
        currentWatchedSeconds += 5; // Simulating local playback timer

        try {
            const response = await fetch('/wp-json/smlms/v1/progress/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': smlmsSettings.nonce
                },
                body: JSON.stringify({
                    topic_id: topicId,
                    watched_seconds: currentWatchedSeconds,
                    total_duration: 300 // Set dynamically or via player API
                })
            });

            const result = await response.json();
            if (result.can_complete) {
                const completeBtn = document.getElementById('smlms-mark-complete-btn');
                if (completeBtn) completeBtn.removeAttribute('disabled');
            }
        } catch (err) {
            console.error('Telemetry error:', err);
        }
    }, 5000);
});