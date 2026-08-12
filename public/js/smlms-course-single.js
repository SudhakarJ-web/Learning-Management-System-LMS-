/**
 * Single Course Landing Page Frontend Handlers
 */
jQuery(document).ready(function($) {

    // 1. Navigation Tab Switching
    $(document).on('click', '.smlms-course-tab-btn', function(e) {
        e.preventDefault();
        
        var targetSelector = $(this).attr('data-target');

        // Deactivate all tab buttons and hide all content panes
        $('.smlms-course-tab-btn').removeClass('active');
        $('.smlms-course-tab-pane').removeClass('active');

        // Activate clicked button and target pane
        $(this).addClass('active');

        if (targetSelector && $(targetSelector).length) {
            $(targetSelector).addClass('active');
        }
    });

    // 2. Accordion & Standalone Lesson Click Handler
    $(document).on('click', '.smlms-lesson-card-header', function(e) {
        if ($(e.target).is('a') || $(e.target).closest('a').length) {
            return;
        }

        const card = $(this).closest('.smlms-lesson-card');
        const body = card.find('.smlms-lesson-card-body');

        if (body.length > 0) {
            body.slideToggle(150);
        } else {
            const link = card.find('.smlms-lesson-link').attr('href');
            if (link) {
                window.location.href = link;
            }
        }
    });

    // 3. Global Expand / Collapse All Accordions Button
    $('#smlms-frontend-expand-all').on('click', function(e) {
        e.preventDefault();
        const isCollapsed = $(this).attr('data-collapsed') === 'true';

        if (isCollapsed) {
            $('.smlms-lesson-card-body').slideDown(150);
            $(this).html('&#9650; Collapse All').attr('data-collapsed', 'false');
        } else {
            $('.smlms-lesson-card-body').slideUp(150);
            $(this).html('&#9660; Expand All').attr('data-collapsed', 'true');
        }
    });

    // 4. Lightbox Video Modal Event Delegation
    $(document).on('click', '.smlms-open-modal, .smlms-play-overlay', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (typeof smlmsOpenCourseVideo === 'function') {
            smlmsOpenCourseVideo(this);
        }
    });
});