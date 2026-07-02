<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-list-check"></i></div>
        <div class="stat-info">
            <h3><?= esc($activePrograms) ?></h3>
            <p>Active Programs</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-calendar-week"></i></div>
        <div class="stat-info">
            <h3><?= esc($upcomingActivities) ?></h3>
            <p>Upcoming Activities</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= esc($assignedVolunteers) ?></h3>
            <p>Assigned Volunteers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= esc($totalHours) ?></h3>
            <p>Total Hours This Semester</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="fas fa-people-carry-box"></i> Outreach Programs Overview
    </div>
    <div class="card-body">
        <p class="muted-text">
            Program management, volunteer scheduling, and AI conflict detection will appear here once the PASIMEO module is active.
        </p>
    </div>
</div>
<?= $this->endSection() ?>
