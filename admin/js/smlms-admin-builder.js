/**
 * Sabin Mathew LMS - Admin Course Builder & Tab Navigation Handler
 */
jQuery(document).ready(function($) {

    // 1. Admin Tab Switcher Handler
    $(document).on('click', '.smlms-admin-tab-btn', function(e) {
        e.preventDefault();
        var tab = $(this).attr('data-tab');

        $('.smlms-admin-tab-btn').removeClass('active');
        $(this).addClass('active');

        // Target Specific Meta Boxes and Layout Containers
        var $editorWrap        = $('#postdivrich, #edit-slug-box, #post-body-content .wp-editor-wrap');
        var $courseCustomMeta  = $('#smlms_course_custom_meta'); // Exclusively on Course Page tab
        var $builderMain       = $('#smlms_course_builder_meta');
        var $builderSide       = $('#smlms_lessons_picker_meta, #smlms_topics_picker_meta');
        var $settingsMain      = $('#smlms_course_details_meta, #smlms_course_students_meta, #smlms_display_options_meta'); // Exclusively on Settings tab
        var $standardSide      = $('#categorydiv, #smlms_course_categorydiv, #postimagediv, #tagsdiv-smlms_course_category');

        if (tab === 'course-page') {
            $editorWrap.show();
            $courseCustomMeta.show();
            $standardSide.show();
            $builderMain.hide();
            $builderSide.hide();
            $settingsMain.hide();
        } else if (tab === 'builder') {
            $editorWrap.hide();
            $courseCustomMeta.hide();
            $standardSide.hide();
            $settingsMain.hide();
            $builderMain.show();
            $builderSide.show();
        } else if (tab === 'settings') {
            $editorWrap.hide();
            $courseCustomMeta.hide();
            $builderMain.hide();
            $builderSide.hide();
            $standardSide.show();
            $settingsMain.show();
        }
    });

    // Trigger active tab on page load
    if ($('.smlms-admin-tab-btn').length > 0) {
        var defaultTab = $('.smlms-admin-tab-btn.active').attr('data-tab') || 'course-page';
        $('.smlms-admin-tab-btn[data-tab="' + defaultTab + '"]').trigger('click');
    }

    // 2. Search Filter for Sidebar Pickers
    $(document).on('keyup', '.smlms-picker-search', function() {
        var term = $(this).val().toLowerCase();
        $(this).closest('.smlms-picker-wrapper').find('.smlms-picker-box label').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(term) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // 3. Access Card Selection Styling
    $(document).on('change', '.smlms-access-card input[type="radio"]', function() {
        $('.smlms-access-card').removeClass('active');
        $(this).closest('.smlms-access-card').addClass('active');
    });

    // 4. Select Target Lesson in Builder
    $(document).on('click', '.smlms-builder-lesson', function(e) {
        if ($(e.target).is('button') || $(e.target).closest('button').length) {
            return;
        }
        $('.smlms-builder-lesson').removeClass('selected active-target');
        $(this).addClass('selected active-target');
    });

    // 5. Add Selected Lessons to Builder Tree
    $(document).on('click', '#smlms-add-lessons-btn', function(e) {
        e.preventDefault();
        var checkedBoxes = $('#smlms-lessons-picker-list input[type="checkbox"]:checked');

        if (checkedBoxes.length === 0) {
            alert('Please select at least one lesson from the list.');
            return;
        }

        checkedBoxes.each(function() {
            var lessonId    = $(this).val();
            var lessonTitle = $(this).closest('label').find('span:last-child').text().trim();

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

    // 6. Add Selected Topics to Highlighted Lesson
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
            var topicTitle = $(this).closest('label').find('span:last-child').text().trim();

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

    // 7. Remove Handlers
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

    // 8. Sync Tree JSON State
    function syncTreeJson() {
        var treeData   = [];
        var totalSteps = 0;

        $('.smlms-builder-lesson').each(function() {
            var lessonId = $(this).attr('data-id');
            var topics   = [];
            totalSteps++;

            $(this).find('.smlms-builder-topic-item').each(function() {
                totalSteps++;
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
        $('#smlms-builder-step-count').text(totalSteps);
    }

    if ($('#_smlms_course_tree_json').length > 0) {
        syncTreeJson();
    }
});