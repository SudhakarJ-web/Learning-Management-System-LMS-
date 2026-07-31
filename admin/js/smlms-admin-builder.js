jQuery(document).ready(function($) {
    // Admin meta box field validation & helper logic
    const videoInput = $('input[name="smlms_video_id"]');
    
    videoInput.on('change', function() {
        const val = $(this).val().trim();
        if (val.length > 0 && val.length < 10) {
            alert('Warning: Cloudflare/Vimeo IDs are typically longer. Double-check your ID format.');
        }
    });
});