<?php
/**
 * Pop-up Video Lightbox Modal Part
 */

if (!defined('ABSPATH')) exit;
?>

<div id="smlms-video-modal" class="smlms-video-modal">
    <div class="smlms-video-modal-backdrop" onclick="smlmsCloseCourseVideo()"></div>
    
    <div class="smlms-video-modal-dialog">
        <button type="button" class="smlms-modal-close-btn" onclick="smlmsCloseCourseVideo()" title="Close Video">&times;</button>
        <div class="smlms-video-responsive-wrap">
            <iframe id="smlms-lightbox-iframe" src="" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</div>

<script>
function smlmsOpenCourseVideo(el) {
    var embedUrl = '';
    if (el) {
        embedUrl = el.getAttribute('data-embed') || (el.closest ? el.closest('[data-embed]').getAttribute('data-embed') : '');
    }
    
    if (embedUrl && embedUrl !== '' && embedUrl !== '#') {
        var modal = document.getElementById('smlms-video-modal');
        var iframe = document.getElementById('smlms-lightbox-iframe');
        
        if (modal && iframe) {
            // Relocate modal directly to <body> to escape theme container overflow & z-index trapping
            if (modal.parentNode !== document.body) {
                document.body.appendChild(modal);
            }
            
            iframe.src = embedUrl;
            modal.classList.add('active');
            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.setProperty('overflow', 'hidden', 'important');
        }
    }
}

function smlmsCloseCourseVideo() {
    var modal = document.getElementById('smlms-video-modal');
    var iframe = document.getElementById('smlms-lightbox-iframe');
    
    if (modal && iframe) {
        modal.classList.remove('active');
        modal.style.removeProperty('display');
        iframe.src = '';
        document.body.style.removeProperty('overflow');
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === "Escape") {
        smlmsCloseCourseVideo();
    }
});
</script>