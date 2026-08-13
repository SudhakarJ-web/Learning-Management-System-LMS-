<?php
/**
 * LMS Setup Dashboard Page View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Fetch Real-time Database Counts
$total_students = SMLMS_DB::get_total_students_count();
$total_courses  = SMLMS_DB::get_total_courses_count();
$total_lessons  = SMLMS_DB::get_total_lessons_count();
$total_topics   = SMLMS_DB::get_total_topics_count();
?>

<div class="wrap smlms-setup-dashboard-wrap">
    
    <div class="smlms-dashboard-header">
        <h1 class="smlms-dashboard-title">Dashboard Overview</h1>
        <p class="smlms-dashboard-subtitle">Real-time metrics and system overview for Sabin Mathew LMS.</p>
    </div>

    <!-- Stat Cards Grid (Reference Layout) -->
    <div class="smlms-stats-grid">
        
        <!-- Card 1: Total Students Enrolled -->
        <div class="smlms-stat-card">
            <div class="smlms-stat-icon-badge bg-teal">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="smlms-stat-details">
                <span class="smlms-stat-label">Students Enrolled</span>
                <strong class="smlms-stat-value"><?php echo number_format_i18n($total_students); ?></strong>
            </div>
        </div>

        <!-- Card 2: Total Courses -->
        <div class="smlms-stat-card">
            <div class="smlms-stat-icon-badge bg-orange">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="smlms-stat-details">
                <span class="smlms-stat-label">Total Courses</span>
                <strong class="smlms-stat-value"><?php echo number_format_i18n($total_courses); ?></strong>
            </div>
        </div>

        <!-- Card 3: Total Lessons -->
        <div class="smlms-stat-card">
            <div class="smlms-stat-icon-badge bg-pink">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="smlms-stat-details">
                <span class="smlms-stat-label">Total Lessons</span>
                <strong class="smlms-stat-value"><?php echo number_format_i18n($total_lessons); ?></strong>
            </div>
        </div>

        <!-- Card 4: Total Topics -->
        <div class="smlms-stat-card">
            <div class="smlms-stat-icon-badge bg-purple">
                <span class="dashicons dashicons-media-document"></span>
            </div>
            <div class="smlms-stat-details">
                <span class="smlms-stat-label">Total Topics</span>
                <strong class="smlms-stat-value"><?php echo number_format_i18n($total_topics); ?></strong>
            </div>
        </div>

    </div>

</div>