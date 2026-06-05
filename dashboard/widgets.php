<?php
// All $total* variables available from stats.php
?>

<!-- ══ 4 STAT CARDS ════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Students -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">🎓</div>
            <div class="stat-value text-primary counter" data-target="<?= $totalStudents ?>">0</div>
            <div class="stat-label">Total Students</div>
            <div class="stat-bar"><div class="stat-bar-fill bg-primary" style="width:72%"></div></div>
            <div class="mb-2">
                <span class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Active</span>
            </div>
            <a href="../students/student-list.php" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-right"></i> View All
            </a>
        </div>
    </div>

    <!-- Teachers -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-value text-success counter" data-target="<?= $totalTeachers ?>">0</div>
            <div class="stat-label">Total Teachers</div>
            <div class="stat-bar"><div class="stat-bar-fill bg-success" style="width:58%"></div></div>
            <div class="mb-2">
                <span class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> Active</span>
            </div>
            <a href="../teachers/teacher-list.php" class="btn btn-sm btn-outline-success">
                <i class="bi bi-arrow-right"></i> View All
            </a>
        </div>
    </div>

    <!-- Attendance -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">📋</div>
            <div class="stat-value text-warning counter" data-target="<?= $totalAttendance ?>">0</div>
            <div class="stat-label">Today's Attendance</div>
            <div class="stat-bar"><div class="stat-bar-fill bg-warning" style="width:<?= $attendancePct ?>%"></div></div>
            <div class="mb-2">
                <span class="stat-trend trend-new"><?= $attendancePct ?>% Present</span>
            </div>
            <a href="../attendance/mark-attendance.php" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-pencil"></i> Mark Now
            </a>
        </div>
    </div>

    <!-- Notifications -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">🔔</div>
            <div class="stat-value text-danger counter" data-target="<?= $totalNotifications ?>">0</div>
            <div class="stat-label">Notifications</div>
            <div class="stat-bar"><div class="stat-bar-fill bg-danger" style="width:40%"></div></div>
            <div class="mb-2">
                <?php if($unreadNotifications > 0): ?>
                <span class="stat-trend" style="background:#fee2e2;color:#dc2626;">
                    <?= $unreadNotifications ?> Unread
                </span>
                <?php else: ?>
                <span class="stat-trend trend-up">All Read</span>
                <?php endif; ?>
            </div>
            <a href="../notifications/notifications.php" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-arrow-right"></i> View All
            </a>
        </div>
    </div>

</div>

<!-- ══ SECONDARY STAT CARDS ═══════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Classes -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">🏫</div>
            <div class="stat-value counter" style="color:#7c3aed;" data-target="<?= $totalClasses ?>">0</div>
            <div class="stat-label">Total Classes</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:65%;background:#7c3aed;"></div></div>
            <div class="mb-2">
                <span class="stat-trend" style="background:#ede9fe;color:#7c3aed;">Active</span>
            </div>
            <a href="../timetable/timetable.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> Timetable
            </a>
        </div>
    </div>

    <!-- Fees -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">💰</div>
            <div class="stat-value" style="color:#0891b2;font-size:24px;margin-top:8px;">
                ₹<?= number_format($totalFees) ?>
            </div>
            <div class="stat-label">Fees Collected</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:80%;background:#0891b2;"></div></div>
            <div class="mb-2">
                <?php if($pendingFees > 0): ?>
                <span class="stat-trend" style="background:#fef3c7;color:#d97706;"><?= $pendingFees ?> Pending</span>
                <?php else: ?>
                <span class="stat-trend trend-up">All Cleared</span>
                <?php endif; ?>
            </div>
            <a href="../fees/fees.php" class="btn btn-sm btn-outline-info">
                <i class="bi bi-arrow-right"></i> Manage
            </a>
        </div>
    </div>

    <!-- Results -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">📝</div>
            <div class="stat-value counter" style="color:#db2777;" data-target="<?= $totalResults ?>">0</div>
            <div class="stat-label">Exam Results</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:50%;background:#db2777;"></div></div>
            <div class="mb-2">
                <span class="stat-trend" style="background:#fce7f3;color:#db2777;">Records</span>
            </div>
            <a href="../exams/generate-results.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> View Results
            </a>
        </div>
    </div>

    <!-- Users -->
    <div class="col-md-3">
        <div class="stat-card text-center">
            <div class="stat-icon">👥</div>
            <div class="stat-value counter" style="color:#ea580c;" data-target="<?= $totalUsers ?>">0</div>
            <div class="stat-label">System Users</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:35%;background:#ea580c;"></div></div>
            <div class="mb-2">
                <span class="stat-trend" style="background:#ffedd5;color:#ea580c;">Registered</span>
            </div>
            <a href="../admin/manage-users.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-right"></i> Manage
            </a>
        </div>
    </div>

</div>

<!-- ══ CHART + ATTENDANCE DONUT ═══════════════════ -->
<div class="row g-4 mb-4">

    <!-- Enrollment Chart -->
    <div class="col-md-8">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">📈 Student Enrollment Trend</div>
                    <div class="panel-sub">Monthly new enrollments — last 6 months</div>
                </div>
                <a href="../students/student-list.php" class="panel-link">View All</a>
            </div>
            <div class="chart-area">
                <canvas id="enrollChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Attendance Donut -->
    <div class="col-md-4">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">📊 Today's Attendance</div>
                    <div class="panel-sub"><?= date('d M Y') ?></div>
                </div>
                <a href="../attendance/mark-attendance.php" class="panel-link">Mark</a>
            </div>
            <div class="donut-wrap">
                <canvas id="attendChart" width="160" height="160"></canvas>
                <div class="donut-center">
                    <div class="donut-pct"><?= $attendancePct ?>%</div>
                    <div class="donut-sub">Present</div>
                </div>
            </div>
            <div class="legend-row">
                <div class="legend-item">
                    <span class="dot" style="background:#22c55e"></span>
                    Present <strong><?= $totalAttendance ?></strong>
                </div>
                <div class="legend-item">
                    <span class="dot" style="background:#ef4444"></span>
                    Absent <strong><?= max(0, $totalAttendanceRecords - $totalAttendance) ?></strong>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══ CLASSES + RECENT ACTIVITY ══════════════════ -->
<div class="row g-4 mb-4">

    <!-- Classes Overview -->
    <div class="col-md-12">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">🏫 Classes Overview</div>
                    <div class="panel-sub">Total Classes: <strong><?= $totalClasses ?></strong></div>
                </div>
                <a href="../timetable/timetable.php" class="panel-link">Timetable</a>
            </div>
            <div>
            <?php
            $classes = mysqli_query($conn, "SELECT * FROM classes ORDER BY class_name, section LIMIT 18");
            if ($classes && mysqli_num_rows($classes) > 0):
                $ci = 0;
                while($class = mysqli_fetch_assoc($classes)):
                    $mod = $ci % 3;
                    $cls = $mod === 0 ? 'chip-1' : ($mod === 1 ? 'chip-2' : 'chip-3');
                    $ci++;
            ?>
            <span class="class-chip <?= $cls ?>">
                <?= htmlspecialchars($class['class_name']) ?> – <?= htmlspecialchars($class['section']) ?>
            </span>
            <?php endwhile; else: ?>
            <p class="no-data">No classes configured yet.</p>
            <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ══ RECENT STUDENTS + TEACHERS ══════════════════ -->
<div class="row g-4 mb-4">

    <!-- Recent Students -->
    <div class="col-md-6">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">🎓 Recent Students</div>
                    <div class="panel-sub">Last 5 enrolled</div>
                </div>
                <a href="../students/student-list.php" class="panel-link">View All</a>
            </div>
            <?php
            $recentStudents = mysqli_query($conn,
                "SELECT * FROM students ORDER BY id DESC LIMIT 5");
            $avatarColors = [
                ['#dbeafe','#2563eb'],['#dcfce7','#16a34a'],
                ['#fef3c7','#d97706'],['#ede9fe','#7c3aed'],['#fce7f3','#db2777']
            ];
            $ai = 0;
            if ($recentStudents && mysqli_num_rows($recentStudents) > 0):
                while($s = mysqli_fetch_assoc($recentStudents)):
                    $col = $avatarColors[$ai % count($avatarColors)];
                    $initials = strtoupper(substr($s['name'],0,1));
                    $ai++;
            ?>
            <div class="activity-item">
                <div class="act-avatar"
                     style="background:<?= $col[0] ?>;color:<?= $col[1] ?>;">
                    <?= $initials ?>
                </div>
                <div>
                    <div class="act-name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="act-meta">Class <?= htmlspecialchars($s['class']) ?> · <?= htmlspecialchars($s['section']) ?></div>
                </div>
                <span class="act-pill pill-blue">Student</span>
            </div>
            <?php endwhile; else: ?>
            <p class="no-data">No students yet.</p>
            <?php endif; ?>
            <a href="../students/student-list.php" class="btn btn-sm btn-dark mt-3">
                View All Students
            </a>
        </div>
    </div>

    <!-- Recent Teachers -->
    <div class="col-md-6">
        <div class="panel-card">
            <div class="panel-header">
                <div>
                    <div class="panel-title">👨‍🏫 Recent Teachers</div>
                    <div class="panel-sub">Last 5 added</div>
                </div>
                <a href="../teachers/teacher-list.php" class="panel-link">View All</a>
            </div>
            <?php
            $recentTeachers = mysqli_query($conn,
                "SELECT * FROM teachers ORDER BY id DESC LIMIT 5");
            $tColors = [
                ['#dcfce7','#16a34a'],['#d1fae5','#059669'],
                ['#ede9fe','#7c3aed'],['#fef3c7','#d97706'],['#dbeafe','#2563eb']
            ];
            $ti = 0;
            if ($recentTeachers && mysqli_num_rows($recentTeachers) > 0):
                while($t = mysqli_fetch_assoc($recentTeachers)):
                    $col = $tColors[$ti % count($tColors)];
                    $initials = strtoupper(substr($t['name'],0,1));
                    $ti++;
            ?>
            <div class="activity-item">
                <div class="act-avatar"
                     style="background:<?= $col[0] ?>;color:<?= $col[1] ?>;">
                    <?= $initials ?>
                </div>
                <div>
                    <div class="act-name"><?= htmlspecialchars($t['name']) ?></div>
                    <div class="act-meta"><?= htmlspecialchars($t['subject']) ?></div>
                </div>
                <span class="act-pill pill-green">Teacher</span>
            </div>
            <?php endwhile; else: ?>
            <p class="no-data">No teachers yet.</p>
            <?php endif; ?>
            <a href="../teachers/teacher-list.php" class="btn btn-sm btn-dark mt-3">
                View All Teachers
            </a>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Enrollment line chart
var enrollCtx = document.getElementById('enrollChart');
if (enrollCtx) {
    new Chart(enrollCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($monthlyData,'label')) ?>,
            datasets: [{
                label: 'New Students',
                data: <?= json_encode(array_column($monthlyData,'count')) ?>,
                borderColor: '#1a1a2e',
                backgroundColor: 'rgba(26,26,46,0.08)',
                tension: 0.4, fill: true,
                pointBackgroundColor: '#1a1a2e',
                pointRadius: 5, pointHoverRadius: 7,
                borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1a2e', titleColor: '#fff',
                    bodyColor: '#ffffffaa', padding: 10,
                }
            },
            scales: {
                x: { grid: { color: '#f0f4f8' }, ticks: { color: '#9ca3af', font:{size:12} } },
                y: { beginAtZero: true, grid: { color: '#f0f4f8' }, ticks: { color: '#9ca3af', font:{size:12}, precision:0 } }
            }
        }
    });
}

// Attendance donut
var attendCtx = document.getElementById('attendChart');
if (attendCtx) {
    var present = <?= (int)$totalAttendance ?>;
    var absent  = <?= max(0, (int)$totalAttendanceRecords - (int)$totalAttendance) ?>;
    new Chart(attendCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: present + absent > 0 ? [present, absent] : [1, 0],
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 0, hoverOffset: 4,
            }]
        },
        options: {
            cutout: '72%', responsive: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } }
        }
    });
}

// Counter animation
document.querySelectorAll('.counter').forEach(function(el) {
    var target = parseInt(el.dataset.target) || 0;
    var start = null;
    function step(ts) {
        if (!start) start = ts;
        var p = Math.min((ts - start) / 1200, 1);
        var ease = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.floor(ease * target).toLocaleString();
        if (p < 1) requestAnimationFrame(step);
        else el.textContent = target.toLocaleString();
    }
    requestAnimationFrame(step);
});
</script>