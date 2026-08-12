/**
 * Sabin Mathew LMS - Admin Course Builder & Navigation Tabs Handler
 */
jQuery(document).ready(function($) {

    // 1. Admin Tab Switcher Handler
    $(document).on('click', '.smlms-admin-tab-btn', function(e) {
        e.preventDefault();
        var tab = $(this).attr('data-tab');

        $('.smlms-admin-tab-btn').removeClass('active');
        $(this).addClass('active');

        // Target WordPress Admin Containers & Meta Boxes
        var $editorWrap = $('#postdivrich, #edit-slug-box, #post-body-content .wp-editor-wrap');
        var $builder    = $('#smlms_course_builder_meta');
        var $settings   = $('#smlms_course_details_meta, #smlms_display_options_meta, #smlms_item_custom_meta');

        if (tab === 'course-page') {
            $editorWrap.show();
            $builder.hide();
            $settings.hide();
        } else if (tab === 'builder') {
            $editorWrap.hide();
            $builder.show();
            $settings.hide();
        } else if (tab === 'settings') {
            $editorWrap.hide();
            $builder.hide();
            $settings.show();
        }
    });

    // Automatically trigger active tab on page load
    if ($('.smlms-admin-tab-btn').length > 0) {
        var defaultTab = $('.smlms-admin-tab-btn.active').attr('data-tab') || 'course-page';
        $('.smlms-admin-tab-btn[data-tab="' + defaultTab + '"]').trigger('click');
    }

    // 2. Access Card Selection Highlight
    $(document).on('change', '.smlms-access-card input[type="radio"]', function() {
        $('.smlms-access-card').removeClass('active');
        $(this).closest('.smlms-access-card').addClass('active');
    });

    // 3. Select Target Lesson in Builder Tree
    $(document).on('click', '.smlms-builder-lesson', function(e) {
        if ($(e.target).is('button') || $(e.target).closest('button').length) {
            return;
        }
        $('.smlms-builder-lesson').removeClass('selected active-target');
        $(this).addClass('selected active-target');
    });

    // 4. Add Lessons to Builder
    $(document).on('click', '#smlms-add-lessons-btn', function(e) {
        e.preventDefault();
        var checkedBoxes = $('#smlms-lessons-picker-list input[type="checkbox"]:checked');

        if (checkedBoxes.length === 0) {
            alert('Please select at least one lesson from the list.');
            return;
        }

        checkedBoxes.each(function() {
            var lessonId    = $(this).val();
            var lessonTitle = $(this).closest('label').text().trim();

            if ($('.smlms-builder-lesson[data-id="' + lessonId + '"]').length > 0) {
                $(this).prop('checked', false);
                return;
            }

            var lessonHtml = `
                <div class="smlms-builder-lesson" data-id="${lessonId}">
                    <div class="smlms-lesson-bar">
                        <div class="smlms-lesson-bar-left">
                            <span class="dashicons dashicons-menu handle-icon"></span>
                            <span class="smlms-type-badge lesson-badge">L</span>
                            <strong class="smlms-lesson-name">${lessonTitle}</strong>
                        </div>
                        <button type="button" class="button smlms-remove-lesson-btn">Remove</button>
                    </div>
                    <div class="smlms-builder-topic-list"></div>
                </div>
            `;

            $('#smlms-course-builder-tree').append(lessonHtml);
            $(this).prop('checked', false);
        });

        $('.smlms-builder-lesson').removeClass('selected active-target');
        $('.smlms-builder-lesson').last().addClass('selected active-target');

        syncTreeJson();
    });

    // 5. Add Topics to Active Lesson
    $(document).on('click', '#smlms-add-topics-btn', function(e) {
        e.preventDefault();
        var checkedBoxes = $('#smlms-topics-picker-list input[type="checkbox"]:checked');

        if (checkedBoxes.length === 0) {
            alert('Please select at least one topic from the list.');
            return;
        }

        var targetLesson = $('.smlms-builder-lesson.selected').length ? $('.smlms-builder-lesson.selected') : $('.smlms-builder-lesson').last();

        if (targetLesson.length === 0) {
            alert('Please add or select a lesson in the builder tree first before adding topics.');
            return;
        }

        var topicContainer = targetLesson.find('.smlms-builder-topic-list');

        checkedBoxes.each(function() {
            var topicId    = $(this).val();
            var topicTitle = $(this).closest('label').text().trim();

            if (topicContainer.find('.smlms-builder-topic-item[data-id="' + topicId + '"]').length > 0) {
                $(this).prop('checked', false);
                return;
            }

            var topicHtml = `
                <div class="smlms-builder-topic-item" data-id="${topicId}">
                    <div class="smlms-topic-bar-left">
                        <span class="dashicons dashicons-menu handle-icon"></span>
                        <span class="smlms-type-badge topic-badge">T</span>
                        <span class="smlms-topic-name">${topicTitle}</span>
                    </div>
                    <button type="button" class="smlms-remove-topic-btn">&times; Remove</button>
                </div>
            `;

            topicContainer.append(topicHtml);
            $(this).prop('checked', false);
        });

        syncTreeJson();
    });

    // 6. Remove Lesson / Topic Handlers
    $(document).on('click', '.smlms-remove-lesson-btn', function(e) {
        e.preventDefault();
        $(this).closest('.smlms-builder-lesson').remove();
        syncTreeJson();
    });

    $(document).on('click', '.smlms-remove-topic-btn', function(e) {
        e.preventDefault();
        $(this).closest('.smlms-builder-topic-item').remove();
        syncTreeJson();
    });

    // 7. Serialize Hierarchy JSON
    function syncTreeJson() {
        var treeData = [];

        $('.smlms-builder-lesson').each(function(index) {
            var lessonId = $(this).attr('data-id');
            var topics   = [];

            $(this).find('.smlms-builder-topic-item').each(function() {
                topics.push({
                    id: $(this).attr('data-id')
                });
            });

            treeData.push({
                id: lessonId,
                topics: topics
            });
        });

        $('#_smlms_course_tree_json').val(JSON.stringify(treeData));
    }

    if ($('#_smlms_course_tree_json').length > 0) {
        syncTreeJson();
    }
});