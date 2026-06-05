<?php
// ============================================================
// Module 10: Fees Management – fees.php
// MindMerge SmartCampus School Management System
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include('../config/database.php');

function generateReceiptNo($conn) {
    $prefix = 'RCPT-' . date('Ymd') . '-';
    $result = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM fee_payments WHERE receipt_no LIKE '$prefix%'");
    $row    = mysqli_fetch_assoc($result);
    $serial = str_pad($row['cnt'] + 1, 4, '0', STR_PAD_LEFT);
    return $prefix . $serial;
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['collect_fee'])) {
    $student_id       = intval($_POST['student_id']);
    $fee_structure_id = intval($_POST['fee_structure_id']);
    $amount_paid      = floatval($_POST['amount_paid']);
    $payment_date     = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_method   = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $transaction_id   = mysqli_real_escape_string($conn, $_POST['transaction_id']);
    $remarks          = mysqli_real_escape_string($conn, $_POST['remarks']);
    $receipt_no       = generateReceiptNo($conn);

    $struct = mysqli_fetch_assoc(mysqli_query($conn, "SELECT amount FROM fee_structure WHERE id = $fee_structure_id"));
    $total  = $struct ? $struct['amount'] : $amount_paid;
    $status = ($amount_paid >= $total) ? 'Paid' : 'Partial';

    $sql = "INSERT INTO fee_payments
            (student_id, fee_structure_id, amount_paid, payment_date,
             payment_method, transaction_id, receipt_no, remarks, status)
            VALUES
            ($student_id, $fee_structure_id, $amount_paid, '$payment_date',
             '$payment_method', '$transaction_id', '$receipt_no', '$remarks', '$status')";

    if (mysqli_query($conn, $sql)) {
        $success = "Fee collected successfully! Receipt No: <strong>$receipt_no</strong>";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

$students       = mysqli_query($conn, "SELECT id, name, class FROM students ORDER BY class, name");
$fee_structures = mysqli_query($conn, "SELECT * FROM fee_structure ORDER BY class, fee_type");

$pending_sql = "
    SELECT s.id AS student_id, s.name AS student_name, s.class,
           fs.id AS fs_id, fs.fee_type, fs.amount AS total_amount, fs.due_date,
           COALESCE(SUM(fp.amount_paid), 0) AS paid_amount,
           (fs.amount - COALESCE(SUM(fp.amount_paid), 0)) AS pending_amount
    FROM fee_structure fs
    JOIN students s ON s.class = fs.class
    LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.fee_structure_id = fs.id AND fp.status != 'Pending'
    GROUP BY s.id, fs.id
    HAVING pending_amount > 0
    ORDER BY fs.due_date ASC, s.class, s.name
";
$pending_result = mysqli_query($conn, $pending_sql);

$total_collected        = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount_paid),0) AS total FROM fee_payments WHERE status='Paid'"))['total'];
$total_pending          = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(fs.amount - COALESCE(p.paid,0)),0) AS total FROM fee_structure fs JOIN students s ON s.class = fs.class LEFT JOIN (SELECT student_id, fee_structure_id, SUM(amount_paid) AS paid FROM fee_payments GROUP BY student_id, fee_structure_id) p ON p.student_id = s.id AND p.fee_structure_id = fs.id WHERE (fs.amount - COALESCE(p.paid,0)) > 0"))['total'];
$total_students_pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT s.id) AS cnt FROM fee_structure fs JOIN students s ON s.class = fs.class LEFT JOIN (SELECT student_id, fee_structure_id, SUM(amount_paid) AS paid FROM fee_payments GROUP BY student_id, fee_structure_id) p ON p.student_id = s.id AND p.fee_structure_id = fs.id WHERE (fs.amount - COALESCE(p.paid,0)) > 0"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management – MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* ═══════════════════════════════════
           SIDEBAR — matches your dashboard
        ═══════════════════════════════════ */
        .sidebar {
            width: 240px;
            background: #1a1f3c;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 200;
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-brand span { font-size: 1.4rem; }
        .sidebar-section {
            padding: 18px 20px 4px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.18s;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .sidebar a.active {
            color: #fff;
            background: rgba(255,255,255,0.1);
            border-left: 3px solid #6c63ff;
        }
        .sidebar a .icon { font-size: 1rem; width: 20px; text-align: center; }

        /* ═══════════════════════════════════
           TOP BAR
        ═══════════════════════════════════ */
        .topbar {
            position: fixed;
            top: 0; left: 240px; right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 1px solid #e8eaf0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 100;
        }
        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1f3c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .topbar-right .user-info { font-size: .85rem; color: #555; }
        .badge-admin {
            background: #f59e0b;
            color: #fff;
            font-size: .72rem;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .btn-logout {
            background: #ef4444;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: .83rem;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-logout:hover { background: #dc2626; color: #fff; }

        /* ═══════════════════════════════════
           MAIN CONTENT
        ═══════════════════════════════════ */
        .main-content {
            margin-left: 240px;
            padding-top: 60px;
            flex: 1;
        }
        .page-body { padding: 28px; }

        /* ═══════════════════════════════════
           PAGE HEADER
        ═══════════════════════════════════ */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-header h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1a1f3c;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ═══════════════════════════════════
           STAT CARDS
        ═══════════════════════════════════ */
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            border: 1px solid #eaecf0;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        .stat-label { font-size: .8rem; color: #888; margin-bottom: 2px; }
        .stat-value { font-size: 1.3rem; font-weight: 700; color: #1a1f3c; }

        /* ═══════════════════════════════════
           CARDS
        ═══════════════════════════════════ */
        .card-wrap {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #eaecf0;
            overflow: hidden;
        }
        .card-head {
            padding: 16px 20px;
            border-bottom: 1px solid #eaecf0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-head h6 {
            font-weight: 700;
            font-size: .95rem;
            color: #1a1f3c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-body-p { padding: 20px; }

        /* ═══════════════════════════════════
           FORM
        ═══════════════════════════════════ */
        .form-label { font-size: .82rem; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-control, .form-select {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: .88rem;
            padding: 8px 12px;
            color: #1a1f3c;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
            outline: none;
        }
        .btn-collect {
            background: #1a1f3c;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 0;
            font-weight: 600;
            font-size: .9rem;
            width: 100%;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-collect:hover { background: #6c63ff; }

        /* ═══════════════════════════════════
           TABLE
        ═══════════════════════════════════ */
        .table { margin: 0; }
        .table thead th {
            background: #f8f9fb;
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            border-bottom: 1px solid #eaecf0;
            padding: 12px 14px;
        }
        .table td {
            font-size: .87rem;
            color: #374151;
            vertical-align: middle;
            padding: 11px 14px;
            border-bottom: 1px solid #f3f4f6;
        }
        .table tbody tr:hover { background: #fafbff; }

        /* badges */
        .tag { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: .74rem; font-weight: 600; }
        .tag-overdue  { background: #fee2e2; color: #dc2626; }
        .tag-soon     { background: #fef3c7; color: #d97706; }
        .tag-active   { background: #dcfce7; color: #16a34a; }

        /* progress bar */
        .prog-wrap { height: 5px; background: #e5e7eb; border-radius: 99px; margin-top: 4px; }
        .prog-fill  { height: 5px; background: #6c63ff; border-radius: 99px; }

        /* action btns */
        .btn-act {
            border: 1.5px solid #e5e7eb;
            background: #fff;
            border-radius: 7px;
            padding: 4px 10px;
            font-size: .8rem;
            cursor: pointer;
            color: #374151;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all .15s;
        }
        .btn-act:hover { border-color: #6c63ff; color: #6c63ff; }
        .btn-act-primary { background: #6c63ff; color: #fff; border-color: #6c63ff; }
        .btn-act-primary:hover { background: #5a52e0; color: #fff; }

        /* search in table header */
        .search-input {
            border: 1.5px solid #e5e7eb;
            border-radius: 7px;
            padding: 5px 12px;
            font-size: .83rem;
            color: #374151;
            background: #f8f9fb;
        }
        .search-input:focus { outline: none; border-color: #6c63ff; }

        /* alerts */
        .alert-box {
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 18px;
            font-size: .88rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .topbar { left: 0; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════════════════ -->
<div class="sidebar">
    <div class="sidebar-brand">
        <span>🧠</span> MindMerge
    </div>

    <div class="sidebar-section">Main Menu</div>
    <a href="../dashboard/dashboard.php"><span class="icon">🏠</span> Dashboard</a>

    <div class="sidebar-section">Academic</div>
    <a href="../students/student-list.php"><span class="icon">👥</span> Students</a>
    <a href="../teachers/teacher-list.php"><span class="icon">🖥️</span> Teachers</a>
    <a href="../attendance/mark-attendance.php"><span class="icon">🕐</span> Attendance</a>
    <a href="../timetable/timetable.php"><span class="icon">🕐</span> Timetable</a>
    <a href="../exams/results.php"><span class="icon">✏️</span> Exams</a>

    <div class="sidebar-section">Finance</div>
    <a href="../fees/fees.php" class="active"><span class="icon">💳</span> Fees</a>

    <div class="sidebar-section">Communication</div>
    <a href="../notifications/notifications.php"><span class="icon">🔔</span> Notifications</a>
    <a href="../reports/reports.php"><span class="icon">📊</span> Reports</a>

    <div class="sidebar-section">Admin</div>
    <a href="../admin/manage-users.php"><span class="icon">⚙️</span> Manage Users</a>
</div>

<!-- ═══════════════════════════════════════════════════════
     TOPBAR
═══════════════════════════════════════════════════════ -->
<div class="topbar">
    <div class="topbar-title">
        💳 Fees Management
    </div>
    <div class="topbar-right">
        <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Admin User') ?></span>
        <span class="badge-admin"><?= htmlspecialchars($_SESSION['role'] ?? 'Admin') ?></span>
        <a href="../auth/logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════════════ -->
<div class="main-content">
<div class="page-body">

    <!-- Page Header -->
    <div class="page-header">
        <h4>💳 Fees Management</h4>
        <a href="payment-history.php" class="btn-act">
            <i class="fas fa-history"></i> Payment History
        </a>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert-box alert-success">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-box alert-error">❌ <?= $error ?></div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#ede9fe; font-size:1.6rem;">💰</div>
                <div>
                    <div class="stat-label">Total Collected</div>
                    <div class="stat-value">₹<?= number_format($total_collected, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7; font-size:1.6rem;">⏳</div>
                <div>
                    <div class="stat-label">Total Pending</div>
                    <div class="stat-value" style="color:#d97706;">₹<?= number_format($total_pending, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2; font-size:1.6rem;">👤</div>
                <div>
                    <div class="stat-label">Students with Dues</div>
                    <div class="stat-value" style="color:#dc2626;"><?= $total_students_pending ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Two column layout -->
    <div class="row g-4">

        <!-- LEFT: Collect Fee Form -->
        <div class="col-lg-4">
            <div class="card-wrap">
                <div class="card-head">
                    <h6>➕ Collect Fee</h6>
                </div>
                <div class="card-body-p">
                    <form method="POST" id="feeForm">

                        <div class="mb-3">
                            <label class="form-label">Select Student <span style="color:red">*</span></label>
                            <select name="student_id" class="form-select" required id="studentSelect">
                                <option value="">-- Choose Student --</option>
                                <?php mysqli_data_seek($students, 0); while ($s = mysqli_fetch_assoc($students)): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['class']) ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fee Type <span style="color:red">*</span></label>
                            <select name="fee_structure_id" class="form-select" required id="feeTypeSelect">
                                <option value="">-- Select Fee Type --</option>
                                <?php mysqli_data_seek($fee_structures, 0); while ($fs = mysqli_fetch_assoc($fee_structures)): ?>
                                    <option value="<?= $fs['id'] ?>" data-amount="<?= $fs['amount'] ?>">
                                        <?= htmlspecialchars($fs['class']) ?> – <?= htmlspecialchars($fs['fee_type']) ?> (₹<?= number_format($fs['amount'],2) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Total Fee Amount</label>
                            <input type="text" class="form-control" id="totalAmount" readonly placeholder="Auto-filled on selection" style="background:#f8f9fb;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount Paid (₹) <span style="color:red">*</span></label>
                            <input type="number" name="amount_paid" class="form-control" step="0.01" min="1" required placeholder="Enter amount">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Date <span style="color:red">*</span></label>
                            <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="Cash">💵 Cash</option>
                                <option value="Online">🌐 Online</option>
                                <option value="Cheque">📄 Cheque</option>
                                <option value="DD">🏦 Demand Draft</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Transaction ID <small style="color:#888">(optional)</small></label>
                            <input type="text" name="transaction_id" class="form-control" placeholder="For online/cheque payments">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Remarks <small style="color:#888">(optional)</small></label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Any notes..."></textarea>
                        </div>

                        <button type="submit" name="collect_fee" class="btn-collect">
                            ✅ Collect & Generate Receipt
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: Pending Fees -->
        <div class="col-lg-8">
            <div class="card-wrap">
                <div class="card-head">
                    <h6>⏳ Pending Fees</h6>
                    <input type="text" id="searchPending" class="search-input" placeholder="🔍 Search student..." oninput="filterTable()">
                </div>
                <div class="table-responsive">
                    <table class="table" id="pendingTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Fee Type</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            $i = 1;
                            if ($pending_result && mysqli_num_rows($pending_result) > 0):
                                while ($row = mysqli_fetch_assoc($pending_result)):
                                    $due   = $row['due_date'];
                                    $pct   = $row['total_amount'] > 0 ? min(100, ($row['paid_amount'] / $row['total_amount']) * 100) : 0;
                                    $tag   = 'tag-active'; $label = 'Active';
                                    if ($due && $due < $today)                              { $tag = 'tag-overdue'; $label = 'Overdue'; }
                                    elseif ($due && $due <= date('Y-m-d', strtotime('+7 days'))) { $tag = 'tag-soon';    $label = 'Due Soon'; }
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><strong><?= htmlspecialchars($row['student_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['class']) ?></td>
                                <td><?= htmlspecialchars($row['fee_type']) ?></td>
                                <td>
                                    <?= $due ? date('d M Y', strtotime($due)) : '—' ?>
                                    <?php if ($label): ?><br><span class="tag <?= $tag ?>"><?= $label ?></span><?php endif; ?>
                                </td>
                                <td>₹<?= number_format($row['total_amount'], 2) ?></td>
                                <td>
                                    ₹<?= number_format($row['paid_amount'], 2) ?>
                                    <div class="prog-wrap"><div class="prog-fill" style="width:<?= $pct ?>%"></div></div>
                                </td>
                                <td style="color:#dc2626;font-weight:700;">₹<?= number_format($row['pending_amount'], 2) ?></td>
                                <td>
                                    <button class="btn-act btn-act-primary"
                                        onclick="prefillForm(<?= $row['student_id'] ?>, <?= $row['fs_id'] ?>, <?= $row['pending_amount'] ?>)"
                                        title="Collect Now">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <a href="payment-history.php?student_id=<?= $row['student_id'] ?>" class="btn-act" title="History">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="9" style="text-align:center;padding:40px;color:#888;">
                                    ✅ No pending fees! All students are up to date.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- end row -->
</div>
</div><!-- end main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('feeTypeSelect').addEventListener('change', function () {
    const amount = this.options[this.selectedIndex].getAttribute('data-amount');
    document.getElementById('totalAmount').value = amount ? '₹' + parseFloat(amount).toFixed(2) : '';
});

function prefillForm(studentId, fsId, amount) {
    document.getElementById('studentSelect').value = studentId;
    document.getElementById('feeTypeSelect').value = fsId;
    document.getElementById('feeTypeSelect').dispatchEvent(new Event('change'));
    document.querySelector('[name="amount_paid"]').value = parseFloat(amount).toFixed(2);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function filterTable() {
    const val  = document.getElementById('searchPending').value.toLowerCase();
    document.querySelectorAll('#pendingTable tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
}
</script>
</body>
</html>