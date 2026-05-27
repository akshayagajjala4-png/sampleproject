<?php require_once '../config/database.php'; ?>

<!-- Stats Widgets -->
<div class="row g-4 mb-4">

    <!-- Students Widget -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">🎓</div>
            <h5 class="mt-2">Students</h5>
            <h2 class="text-primary"><?= $totalStudents ?></h2>
            <small class="text-muted">Total Students</small>
            <div class="mt-2">
                <a href="../students/student-list.php" 
                   class="btn btn-sm btn-outline-primary">
                   View All
                </a>
            </div>
        </div>
    </div>

    <!-- Teachers Widget -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">👨‍🏫</div>
            <h5 class="mt-2">Teachers</h5>
            <h2 class="text-success"><?= $totalTeachers ?></h2>
            <small class="text-muted">Total Teachers</small>
            <div class="mt-2">
                <a href="../teachers/teacher-list.php" 
                   class="btn btn-sm btn-outline-success">
                   View All
                </a>
            </div>
        </div>
    </div>

    <!-- Attendance Widget -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">📋</div>
            <h5 class="mt-2">Attendance</h5>
            <h2 class="text-warning"><?= $totalAttendance ?></h2>
            <small class="text-muted">Today's Attendance</small>
            <div class="mt-2">
                <a href="../attendance/mark-attendance.php" 
                   class="btn btn-sm btn-outline-warning">
                   Mark Now
                </a>
            </div>
        </div>
    </div>

    <!-- Notifications Widget -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">🔔</div>
            <h5 class="mt-2">Notifications</h5>
            <h2 class="text-danger"><?= $totalNotifications ?></h2>
            <small class="text-muted">Recent Alerts</small>
            <div class="mt-2">
                <a href="../notifications/notifications.php" 
                   class="btn btn-sm btn-outline-danger">
                   View All
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Results & Classes Row -->
<div class="row g-4 mb-4">

    <!-- Results Widget -->
    <div class="col-md-6">
        <div class="stat-card">
            <h6 class="fw-bold mb-3">📝 Recent Results</h6>
            <p class="text-muted">
                No results yet. 
                Results will appear after exams module is built.
            </p>
            <a href="../exams/generate-results.php" 
               class="btn btn-sm btn-dark">
               Go to Exams
            </a>
        </div>
    </div>

    <!-- Classes Widget -->
    <div class="col-md-6">
        <div class="stat-card">
            <h6 class="fw-bold mb-3">🏫 Classes Overview</h6>
            <?php
            $classes = mysqli_query($conn, "SELECT * FROM classes LIMIT 6");
            while($class = mysqli_fetch_assoc($classes)):
            ?>
            <span class="badge bg-dark me-2 mb-2 p-2">
                <?= $class['class_name'] ?> - <?= $class['section'] ?>
            </span>
            <?php endwhile; ?>
            <div class="mt-3">
                <small class="text-muted">
                    Total Classes: <strong><?= $totalClasses ?></strong>
                </small>
            </div>
        </div>
    </div>

</div>
