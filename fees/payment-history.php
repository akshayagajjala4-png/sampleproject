<?php
// ============================================================
// Module 10: Fees Management – payment-history.php
// MindMerge SmartCampus School Management System
// ============================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
include('../config/database.php');

$filter_student   = isset($_GET['student_id']) ? intval($_GET['student_id'])                          : 0;
$filter_class     = isset($_GET['class'])      ? mysqli_real_escape_string($conn, $_GET['class'])     : '';
$filter_status    = isset($_GET['status'])     ? mysqli_real_escape_string($conn, $_GET['status'])    : '';
$filter_method    = isset($_GET['method'])     ? mysqli_real_escape_string($conn, $_GET['method'])    : '';
$filter_date_from = isset($_GET['date_from'])  ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$filter_date_to   = isset($_GET['date_to'])    ? mysqli_real_escape_string($conn, $_GET['date_to'])   : '';

$where = " WHERE 1=1 ";
if ($filter_student)   $where .= " AND fp.student_id = $filter_student ";
if ($filter_class)     $where .= " AND s.class = '$filter_class' ";
if ($filter_status)    $where .= " AND fp.status = '$filter_status' ";
if ($filter_method)    $where .= " AND fp.payment_method = '$filter_method' ";
if ($filter_date_from) $where .= " AND fp.payment_date >= '$filter_date_from' ";
if ($filter_date_to)   $where .= " AND fp.payment_date <= '$filter_date_to' ";

$payments = mysqli_query($conn, "
    SELECT fp.*, s.name AS student_name, s.class,
           fs.fee_type, fs.amount AS total_fee
    FROM fee_payments fp
    JOIN students s ON s.id = fp.student_id
    LEFT JOIN fee_structure fs ON fs.id = fp.fee_structure_id
    $where
    ORDER BY fp.payment_date DESC, fp.id DESC
");

$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS total_records,
           COALESCE(SUM(fp.amount_paid),0) AS grand_total,
           COUNT(CASE WHEN fp.status='Paid'    THEN 1 END) AS paid_count,
           COUNT(CASE WHEN fp.status='Partial' THEN 1 END) AS partial_count
    FROM fee_payments fp
    JOIN students s ON s.id = fp.student_id
    LEFT JOIN fee_structure fs ON fs.id = fp.fee_structure_id
    $where
"));

$students_list = mysqli_query($conn, "SELECT id, name, class FROM students ORDER BY class, name");
$classes_list  = mysqli_query($conn, "SELECT DISTINCT class FROM students ORDER BY class");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History – MindMerge SmartCampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 240px; background: #1a1f3c; min-height: 100vh;
            position: fixed; top: 0; left: 0; display: flex; flex-direction: column; z-index: 200;
        }
        .sidebar-brand {
            padding: 22px 20px 18px; color: #fff; font-size: 1.15rem; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .sidebar-brand span { font-size: 1.4rem; }
        .sidebar-section {
            padding: 18px 20px 4px; font-size: .68rem; font-weight: 700;
            letter-spacing: .1em; color: rgba(255,255,255,.35); text-transform: uppercase;
        }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; padding: 10px 20px;
            color: rgba(255,255,255,.65); text-decoration: none; font-size: .9rem;
            transition: all .18s; border-left: 3px solid transparent;
        }
        .sidebar a:hover { color: #fff; background: rgba(255,255,255,.06); }
        .sidebar a.active { color: #fff; background: rgba(255,255,255,.1); border-left: 3px solid #6c63ff; }
        .sidebar a .icon { font-size: 1rem; width: 20px; text-align: center; }

        /* TOPBAR */
        .topbar {
            position: fixed; top: 0; left: 240px; right: 0; height: 60px;
            background: #fff; border-bottom: 1px solid #e8eaf0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; z-index: 100;
        }
        .topbar-title { font-size: 1.1rem; font-weight: 700; color: #1a1f3c; display: flex; align-items: center; gap: 10px; }
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .topbar-right .user-info { font-size: .85rem; color: #555; }
        .badge-admin { background: #f59e0b; color: #fff; font-size: .72rem; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .btn-logout { background: #ef4444; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; font-size: .83rem; font-weight: 600; text-decoration: none; }
        .btn-logout:hover { background: #dc2626; color: #fff; }

        /* MAIN */
        .main-content { margin-left: 240px; padding-top: 60px; flex: 1; }
        .page-body { padding: 28px; }

        /* PAGE HEADER */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h4 { font-size: 1.4rem; font-weight: 700; color: #1a1f3c; display: flex; align-items: center; gap: 10px; }

        /* STAT PILLS */
        .stat-card { background: #fff; border-radius: 14px; padding: 18px 20px; border: 1px solid #eaecf0; }
        .stat-label { font-size: .78rem; color: #888; margin-bottom: 2px; }
        .stat-value { font-size: 1.25rem; font-weight: 700; color: #1a1f3c; }

        /* FILTER BAR */
        .filter-bar { background: #fff; border-radius: 12px; border: 1px solid #eaecf0; padding: 18px 20px; margin-bottom: 20px; }
        .filter-bar .filter-title { font-weight: 700; font-size: .9rem; color: #1a1f3c; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
        .form-label { font-size: .78rem; font-weight: 600; color: #6b7280; margin-bottom: 4px; }
        .form-control, .form-select { border: 1.5px solid #e5e7eb; border-radius: 7px; font-size: .85rem; padding: 7px 11px; color: #1a1f3c; }
        .form-control:focus, .form-select:focus { border-color: #6c63ff; box-shadow: 0 0 0 2px rgba(108,99,255,.12); outline: none; }

        /* BUTTONS */
        .btn-filter { background: #1a1f3c; color: #fff; border: none; border-radius: 7px; padding: 8px 18px; font-size: .85rem; font-weight: 600; cursor: pointer; }
        .btn-filter:hover { background: #6c63ff; }
        .btn-clear { background: #fff; color: #374151; border: 1.5px solid #e5e7eb; border-radius: 7px; padding: 8px 18px; font-size: .85rem; font-weight: 600; text-decoration: none; }
        .btn-clear:hover { border-color: #6c63ff; color: #6c63ff; }
        .btn-print { background: #fff; color: #374151; border: 1.5px solid #e5e7eb; border-radius: 7px; padding: 8px 18px; font-size: .85rem; font-weight: 600; cursor: pointer; }
        .btn-print:hover { border-color: #10b981; color: #10b981; }
        .btn-act { border: 1.5px solid #e5e7eb; background: #fff; border-radius: 7px; padding: 4px 10px; font-size: .8rem; cursor: pointer; color: #374151; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all .15s; }
        .btn-act:hover { border-color: #6c63ff; color: #6c63ff; }

        /* CARD */
        .card-wrap { background: #fff; border-radius: 14px; border: 1px solid #eaecf0; overflow: hidden; }
        .card-head { padding: 16px 20px; border-bottom: 1px solid #eaecf0; display: flex; align-items: center; justify-content: space-between; }
        .card-head h6 { font-weight: 700; font-size: .95rem; color: #1a1f3c; margin: 0; display: flex; align-items: center; gap: 8px; }

        /* TABLE */
        .table { margin: 0; }
        .table thead th { background: #f8f9fb; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #eaecf0; padding: 12px 14px; }
        .table td { font-size: .87rem; color: #374151; vertical-align: middle; padding: 11px 14px; border-bottom: 1px solid #f3f4f6; }
        .table tbody tr:hover { background: #fafbff; }

        /* status tags */
        .tag { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .74rem; font-weight: 600; }
        .tag-paid    { background: #dcfce7; color: #16a34a; }
        .tag-partial { background: #fef3c7; color: #d97706; }
        .tag-pending { background: #fee2e2; color: #dc2626; }

        /* RECEIPT MODAL */
        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .receipt-box { border: 2px solid #e5e7eb; border-radius: 12px; padding: 28px; font-family: 'Segoe UI', sans-serif; }
        .receipt-header { text-align: center; border-bottom: 2px dashed #e5e7eb; padding-bottom: 14px; margin-bottom: 16px; }
        .receipt-header h5 { color: #1a1f3c; font-weight: 800; font-size: 1.1rem; }
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 9px; font-size: .88rem; }
        .receipt-row .lbl { color: #888; }
        .receipt-row .val { font-weight: 600; color: #1a1f3c; }
        .receipt-total { background: #f0f2f5; border-radius: 8px; padding: 12px 16px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center; }
        .receipt-footer { text-align: center; margin-top: 16px; font-size: .75rem; color: #aaa; border-top: 1px dashed #e5e7eb; padding-top: 10px; }

        @media print {
            body * { visibility: hidden; }
            #receiptPrintArea, #receiptPrintArea * { visibility: visible; }
            #receiptPrintArea { position: fixed; top: 0; left: 0; width: 100%; padding: 20px; }
            .no-print { display: none !important; }
        }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
            .topbar { left: 0; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand"><span>🧠</span> MindMerge</div>

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

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-title">📋 Payment History</div>
    <div class="topbar-right">
        <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Admin User') ?></span>
        <span class="badge-admin"><?= htmlspecialchars($_SESSION['role'] ?? 'Admin') ?></span>
        <a href="../auth/logout.php" class="btn-logout">Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main-content">
<div class="page-body">

    <!-- Page Header -->
    <div class="page-header">
        <h4>📋 Payment History</h4>
        <a href="fees.php" class="btn-act"><i class="fas fa-plus"></i> Collect Fee</a>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Records</div>
                <div class="stat-value"><?= $summary['total_records'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Amount Collected</div>
                <div class="stat-value" style="color:#16a34a;">₹<?= number_format($summary['grand_total'], 2) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Fully Paid</div>
                <div class="stat-value" style="color:#6c63ff;"><?= $summary['paid_count'] ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Partial Payments</div>
                <div class="stat-value" style="color:#d97706;"><?= $summary['partial_count'] ?></div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar no-print">
        <div class="filter-title">🔍 Filter Payments</div>
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        <?php while ($s = mysqli_fetch_assoc($students_list)): ?>
                            <option value="<?= $s['id'] ?>" <?= $filter_student==$s['id']?'selected':'' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Class</label>
                    <select name="class" class="form-select">
                        <option value="">All Classes</option>
                        <?php while ($c = mysqli_fetch_assoc($classes_list)): ?>
                            <option value="<?= $c['class'] ?>" <?= $filter_class==$c['class']?'selected':'' ?>>
                                <?= htmlspecialchars($c['class']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="Paid"    <?= $filter_status=='Paid'?'selected':'' ?>>Paid</option>
                        <option value="Partial" <?= $filter_status=='Partial'?'selected':'' ?>>Partial</option>
                        <option value="Pending" <?= $filter_status=='Pending'?'selected':'' ?>>Pending</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Method</label>
                    <select name="method" class="form-select">
                        <option value="">All</option>
                        <option value="Cash"   <?= $filter_method=='Cash'?'selected':'' ?>>Cash</option>
                        <option value="Online" <?= $filter_method=='Online'?'selected':'' ?>>Online</option>
                        <option value="Cheque" <?= $filter_method=='Cheque'?'selected':'' ?>>Cheque</option>
                        <option value="DD"     <?= $filter_method=='DD'?'selected':'' ?>>DD</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $filter_date_from ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $filter_date_to ?>">
                </div>
                <div class="col-12 d-flex gap-2 mt-1">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter me-1"></i> Filter</button>
                    <a href="payment-history.php" class="btn-clear">✕ Clear</a>
                    <button type="button" class="btn-print ms-auto" onclick="window.print()"><i class="fas fa-print me-1"></i> Print</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="card-wrap">
        <div class="card-head">
            <h6>📄 Payment Records</h6>
            <span style="font-size:.82rem;color:#888;"><?= $summary['total_records'] ?> records found</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Receipt No</th>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Fee Type</th>
                        <th>Total Fee</th>
                        <th>Paid</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    if ($payments && mysqli_num_rows($payments) > 0):
                        while ($p = mysqli_fetch_assoc($payments)):
                            $tag_class = match($p['status']) { 'Paid' => 'tag-paid', 'Partial' => 'tag-partial', default => 'tag-pending' };
                            $method_icon = match($p['payment_method']) { 'Cash'=>'💵','Online'=>'🌐','Cheque'=>'📄','DD'=>'🏦', default=>'💳' };
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><code style="color:#6c63ff;font-size:.82rem;"><?= htmlspecialchars($p['receipt_no']) ?></code></td>
                        <td><strong><?= htmlspecialchars($p['student_name']) ?></strong></td>
                        <td><?= htmlspecialchars($p['class']) ?></td>
                        <td><?= htmlspecialchars($p['fee_type'] ?? '—') ?></td>
                        <td>₹<?= number_format($p['total_fee'] ?? 0, 2) ?></td>
                        <td style="color:#16a34a;font-weight:700;">₹<?= number_format($p['amount_paid'], 2) ?></td>
                        <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                        <td><?= $method_icon ?> <?= $p['payment_method'] ?></td>
                        <td><span class="tag <?= $tag_class ?>"><?= $p['status'] ?></span></td>
                        <td>
                            <button class="btn-act" onclick="openReceipt(<?= htmlspecialchars(json_encode($p)) ?>)" title="View Receipt">
                                🧾
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="11" style="text-align:center;padding:44px;color:#888;">
                            🔍 No payment records found. Adjust filters or collect a fee first.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<!-- RECEIPT MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0 no-print">
                <h6 class="modal-title fw-bold" style="color:#1a1f3c;">🧾 Fee Receipt</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="receiptPrintArea">
                <div class="receipt-box" id="receiptBox"></div>
            </div>
            <div class="modal-footer border-0 pt-0 no-print">
                <button class="btn-clear" data-bs-dismiss="modal">Close</button>
                <button class="btn-filter" onclick="window.print()"><i class="fas fa-print me-1"></i> Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openReceipt(data) {
    const statusColor = { Paid: '#16a34a', Partial: '#d97706', Pending: '#dc2626' };
    const color = statusColor[data.status] || '#374151';
    const dateStr = new Date(data.payment_date).toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });

    document.getElementById('receiptBox').innerHTML = `
        <div class="receipt-header">
            <div style="font-size:2rem;margin-bottom:6px;">🧠</div>
            <h5>MindMerge SmartCampus</h5>
            <div style="color:#888;font-size:.82rem;">School Management System</div>
            <div style="margin-top:8px;font-weight:800;font-size:1rem;color:#1a1f3c;letter-spacing:.05em;">FEE RECEIPT</div>
        </div>
        <div class="receipt-row"><span class="lbl">Receipt No</span><span class="val" style="color:#6c63ff;">${data.receipt_no}</span></div>
        <div class="receipt-row"><span class="lbl">Student Name</span><span class="val">${data.student_name}</span></div>
        <div class="receipt-row"><span class="lbl">Class</span><span class="val">${data.class}</span></div>
        <div class="receipt-row"><span class="lbl">Fee Type</span><span class="val">${data.fee_type || '—'}</span></div>
        <div class="receipt-row"><span class="lbl">Payment Date</span><span class="val">${dateStr}</span></div>
        <div class="receipt-row"><span class="lbl">Payment Method</span><span class="val">${data.payment_method}</span></div>
        ${data.transaction_id ? `<div class="receipt-row"><span class="lbl">Transaction ID</span><span class="val">${data.transaction_id}</span></div>` : ''}
        ${data.remarks ? `<div class="receipt-row"><span class="lbl">Remarks</span><span class="val">${data.remarks}</span></div>` : ''}
        <div class="receipt-total">
            <span style="font-weight:700;font-size:.95rem;">Amount Paid</span>
            <span style="font-weight:800;font-size:1.2rem;color:#16a34a;">₹${parseFloat(data.amount_paid).toLocaleString('en-IN',{minimumFractionDigits:2})}</span>
        </div>
        <div class="receipt-row" style="margin-top:12px;">
            <span class="lbl">Status</span>
            <span class="val" style="color:${color};">${data.status}</span>
        </div>
        <div class="receipt-footer">
            This is a computer-generated receipt. No signature required.<br>
            MindMerge SmartCampus &copy; ${new Date().getFullYear()}
        </div>
    `;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}
</script>
</body>
</html>