jQuery(document).ready(function($) {

    let courseTree = [];
    let selectedLessonId = null;
    const hiddenJsonField = $('#smlms_course_tree_json');

    // Load existing hierarchy JSON
    if (hiddenJsonField.length && hiddenJsonField.val()) {
        try {
            courseTree = JSON.parse(hiddenJsonField.val());
            if (!Array.isArray(courseTree)) courseTree = [];
        } catch(e) {
            courseTree = [];
        }
    }

    renderCanvas();

    // 1. Admin Top Tab Switcher
    $(document).on('click', '.smlms-header-tabs .smlms-tab', function(e) {
        e.preventDefault();
        const tabType = $(this).data('tab');

        $('.smlms-header-tabs .smlms-tab').removeClass('active');
        $(this).addClass('active');

        // Hide all panels by default
        $('#postdivrich').hide();
        $('#smlms_course_custom_meta_box').hide();
        $('#smlms_course_builder_box').hide();
        $('#smlms_sidebar_lessons_box').hide();
        $('#smlms_sidebar_topics_box').hide();
        $('#smlms_course_enrollment_box').hide();
        $('#smlms_course_students_box').hide();
        $('#smlms_course_settings').hide();

        if (tabType === 'course_page' || tabType === 'lesson_page' || tabType === 'topic_page') {
            $('#postdivrich').show();
            $('#smlms_course_custom_meta_box').show();
            $('#smlms_sidebar_lessons_box').show();
            $('#smlms_sidebar_topics_box').show();
            $('#smlms_course_settings').show();
        } else if (tabType === 'builder') {
            $('#smlms_course_builder_box').show();   // Show Sabin Mathew Builder
            $('#smlms_sidebar_lessons_box').show();  // Show Lessons Sidebar Picker
            $('#smlms_sidebar_topics_box').show();   // Show Topics Sidebar Picker
        } else if (tabType === 'settings') {
            $('#smlms_course_enrollment_box').show();
            $('#smlms_course_students_box').show();
            $('#smlms_course_settings').show();
        }
    });

    // Automatically trigger current active tab
    $('.smlms-header-tabs .smlms-tab.active').trigger('click');

    // 2. Add Lessons to Builder Canvas
    $(document).on('click', '.smlms-add-lessons-btn', function(e) {
        e.preventDefault();
        const pickerBox = $(this).closest('.smlms-sidebar-picker-box');
        const checkedItems = pickerBox.find('.smlms-picker-cb:checked');

        if (checkedItems.length === 0) {
            alert('Please select at least one lesson from the list.');
            return;
        }

        checkedItems.each(function() {
            const lessonId = $(this).val();
            const lessonTitle = $(this).data('title');

            if (!courseTree.some(item => item.id == lessonId)) {
                courseTree.push({
                    id: lessonId,
                    title: lessonTitle,
                    topics: []
                });
            }
            $(this).prop('checked', false);
        });

        if (courseTree.length > 0) {
            selectedLessonId = courseTree[courseTree.length - 1].id;
        }
        syncAndRender();
    });

    // 3. Add Topics to Selected Lesson
    $(document).on('click', '.smlms-add-topics-btn', function(e) {
        e.preventDefault();

        if (courseTree.length === 0) {
            alert('Please add a Lesson to the Sabin Mathew Builder first.');
            return;
        }

        const pickerBox = $(this).closest('.smlms-sidebar-picker-box');
        const checkedItems = pickerBox.find('.smlms-picker-cb:checked');

        if (checkedItems.length === 0) {
            alert('Please select at least one topic from the list.');
            return;
        }

        let targetLesson = courseTree.find(l => l.id == selectedLessonId);
        if (!targetLesson) {
            targetLesson = courseTree[courseTree.length - 1];
            selectedLessonId = targetLesson.id;
        }

        checkedItems.each(function() {
            const topicId = $(this).val();
            const topicTitle = $(this).data('title');

            if (!targetLesson.topics.some(t => t.id == topicId)) {
                targetLesson.topics.push({
                    id: topicId,
                    title: topicTitle
                });
            }
            $(this).prop('checked', false);
        });

        syncAndRender();
    });

    // 4. Select Lesson Target on Row Click
    $(document).on('click', '.smlms-builder-lesson-row', function(e) {
        if ($(e.target).hasClass('smlms-remove-step') || $(e.target).hasClass('smlms-accordion-arrow')) return;

        selectedLessonId = $(this).data('lesson-id');
        $('.smlms-builder-lesson-row').removeClass('smlms-active-lesson-row');
        $(this).addClass('smlms-active-lesson-row');
    });

    // 5. Delete Lesson / Topic Step
    $(document).on('click', '.smlms-remove-step', function(e) {
        e.stopPropagation();
        const type = $(this).data('type');
        const id = $(this).data('id');

        if (type === 'lesson') {
            courseTree = courseTree.filter(l => l.id != id);
            if (selectedLessonId == id) selectedLessonId = null;
        } else if (type === 'topic') {
            const parentId = $(this).data('parent-id');
            const lesson = courseTree.find(l => l.id == parentId);
            if (lesson) {
                lesson.topics = lesson.topics.filter(t => t.id != id);
            }
        }

        syncAndRender();
    });

    // 6. Accordion Toggle
    $(document).on('click', '.smlms-accordion-arrow', function(e) {
        e.stopPropagation();
        $(this).closest('.smlms-builder-lesson-row').find('.smlms-builder-topics-body').slideToggle(150);
    });

    // 7. Expand / Collapse All
    $('#smlms-toggle-all-steps').on('click', function(e) {
        e.preventDefault();
        const isExpanded = $(this).data('expanded');

        if (isExpanded) {
            $('.smlms-builder-topics-body').slideUp(150);
            $(this).html('Expand All &#9660;').data('expanded', false);
        } else {
            $('.smlms-builder-topics-body').slideDown(150);
            $(this).html('Collapse All &#9650;').data('expanded', true);
        }
    });

    // Filter Search in Sidebar Boxes
    $(document).on('keyup', '.smlms-picker-search', function() {
        const term = $(this).val().toLowerCase();
        const pickerBox = $(this).closest('.smlms-sidebar-picker-box');

        pickerBox.find('.smlms-picker-row').each(function() {
            const title = $(this).text().toLowerCase();
            $(this).toggle(title.includes(term));
        });
    });

    function syncAndRender() {
        hiddenJsonField.val(JSON.stringify(courseTree));
        renderCanvas();
    }

    function renderCanvas() {
        const canvas = $('#smlms-builder-canvas');
        if (!canvas.length) return;

        canvas.empty();

        if (courseTree.length === 0) {
            canvas.html(`
                <div class="smlms-builder-empty-notice">
                    <p>No steps in this course yet. Select lessons from the right sidebar and click <strong>Add to Builder</strong>.</p>
                </div>
            `);
            $('#smlms-steps-counter').text('0 steps in this Course');
            return;
        }

        let totalSteps = 0;

        courseTree.forEach((lesson, lIndex) => {
            totalSteps += 1;
            const topicCount = lesson.topics ? lesson.topics.length : 0;
            totalSteps += topicCount;

            const isSelected = (selectedLessonId == lesson.id) ? 'smlms-active-lesson-row' : '';

            let topicsHtml = '';
            if (topicCount > 0) {
                lesson.topics.forEach((topic, tIndex) => {
                    topicsHtml += `
                        <div class="smlms-builder-topic-row" data-topic-id="${topic.id}">
                            <span class="smlms-drag-handle">&#9776;</span>
                            <span class="smlms-badge smlms-badge-topic">T</span>
                            <span>${lIndex + 1}.${tIndex + 1} ${escapeHtml(topic.title)}</span>
                            <button type="button" class="smlms-remove-step" data-type="topic" data-id="${topic.id}" data-parent-id="${lesson.id}" title="Remove Topic">&times;</button>
                        </div>
                    `;
                });
            } else {
                topicsHtml = '<p class="smlms-no-topics-notice">No topics added to this lesson yet.</p>';
            }

            const lessonHtml = `
                <div class="smlms-builder-lesson-row ${isSelected}" data-lesson-id="${lesson.id}">
                    <div class="smlms-builder-row-header">
                        <span class="smlms-drag-handle">&#9776;</span>
                        <span class="smlms-badge smlms-badge-lesson">L</span>
                        <strong class="smlms-step-title">${lIndex + 1}. ${escapeHtml(lesson.title)}</strong>
                        <span class="smlms-sub-count">(${topicCount})</span>
                        <button type="button" class="smlms-remove-step" data-type="lesson" data-id="${lesson.id}" title="Remove Lesson">&times;</button>
                        <span class="smlms-accordion-arrow">&#9660;</span>
                    </div>
                    <div class="smlms-builder-topics-body">
                        ${topicsHtml}
                    </div>
                </div>
            `;

            canvas.append(lessonHtml);
        });

        $('#smlms-steps-counter').text(`${totalSteps} steps in this Course`);
    }

    function escapeHtml(text) {
        return $('<div>').text(text).html();
    }
});