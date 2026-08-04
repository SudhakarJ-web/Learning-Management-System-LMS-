jQuery(document).ready(function($) {

    // 1. Course Tab Switching
    $('.smlms-course-tab-btn').on('click', function() {
        $('.smlms-course-tab-btn').removeClass('active');
        $('.smlms-course-tab-pane').removeClass('active');

        $(this).addClass('active');
        $($(this).data('target')).addClass('active');
    });

    // 2. Lesson Accordion Expand/Collapse
    $(document).on('click', '.smlms-lesson-card-header', function() {
        const body = $(this).next('.smlms-lesson-card-body');
        body.slideToggle(150);
    });

    // 3. Expand All / Collapse All Button
    $('#smlms-frontend-expand-all').on('click', function() {
        const isExpanded = $(this).data('expanded');

        if (isExpanded) {
            $('.smlms-lesson-card-body').slideUp(150);
            $(this).text('Expand All').data('expanded', false);
        } else {
            $('.smlms-lesson-card-body').slideDown(150);
            $(this).text('Collapse All').data('expanded', true);
        }
    });
});