<?php
session_start();
include '../config/database.php';

// Security check
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Include DOMPDF
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$type = isset($_GET['type']) ? $_GET['type'] : 'student';
$html = '';

// =====================
// ATTENDANCE REPORT
// =====================
if($type == 'attendance') {

    $result = $conn->query("
        SELECT s.name, a.date, a.status
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        ORDER BY a.date DESC
    ");

    $rows = '';
    while($row = $result->fetch_assoc()) {
        $color = ($row['status'] == 'Present') ? 'green' : 'red';
        $rows .= "<tr>
            <td>{$row['name']}</td>
            <td>{$row['date']}</td>
            <td style='color:{$color}; font-weight:bold;'>{$row['status']}</td>
        </tr>";
    }

    $html = "
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { color: #003366; text-align: center; }
            h3 { text-align: center; color: #555; }
            p  { text-align: center; color: #888; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #003366; color: white; padding: 10px; text-align: center; }
            td { padding: 8px; border: 1px solid #ccc; text-align: center; font-size: 13px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #aaa; }
        </style>
        <h2>MindMerge SmartCampus</h2>
        <h3>Attendance Report</h3>
        <p>Generated on: " . date('d-m-Y H:i:s') . "</p>
        <table>
            <thead>
                <tr><th>Student Name</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
        <div class='footer'>MindMerge SmartCampus &copy; " . date('Y') . "</div>
    ";
}

// =====================
// STUDENT REPORT
// =====================
elseif($type == 'student') {

    $result = $conn->query("SELECT * FROM students ORDER BY class, name");

    $rows = '';
    while($row = $result->fetch_assoc()) {
        $rows .= "<tr>
            <td>{$row['student_id']}</td>
            <td>{$row['name']}</td>
            <td>{$row['class']}</td>
            <td>{$row['section']}</td>
            <td>{$row['parent_contact']}</td>
            <td>{$row['dob']}</td>
        </tr>";
    }

    $html = "
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { color: #003366; text-align: center; }
            h3 { text-align: center; color: #555; }
            p  { text-align: center; color: #888; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #28a745; color: white; padding: 10px; text-align: center; }
            td { padding: 8px; border: 1px solid #ccc; text-align: center; font-size: 13px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #aaa; }
        </style>
        <h2>MindMerge SmartCampus</h2>
        <h3>Student Report</h3>
        <p>Generated on: " . date('d-m-Y H:i:s') . "</p>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th><th>Name</th><th>Class</th>
                    <th>Section</th><th>Parent Contact</th><th>DOB</th>
                </tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
        <div class='footer'>MindMerge SmartCampus &copy; " . date('Y') . "</div>
    ";
}

// =====================
// FEES REPORT
// =====================
elseif($type == 'fees') {

    $result = $conn->query("
        SELECT s.name, fp.amount_paid, fp.payment_date,
               fp.payment_method, fp.receipt_no, fp.status
        FROM fee_payments fp
        JOIN students s ON fp.student_id = s.id
        ORDER BY fp.payment_date DESC
    ");

    $rows = '';
    while($row = $result->fetch_assoc()) {
        if($row['status'] == 'Paid') $color = 'green';
        elseif($row['status'] == 'Partial') $color = 'orange';
        else $color = 'red';

        $rows .= "<tr>
            <td>{$row['name']}</td>
            <td>Rs. {$row['amount_paid']}</td>
            <td>{$row['payment_date']}</td>
            <td>{$row['payment_method']}</td>
            <td>{$row['receipt_no']}</td>
            <td style='color:{$color}; font-weight:bold;'>{$row['status']}</td>
        </tr>";
    }

    $html = "
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { color: #003366; text-align: center; }
            h3 { text-align: center; color: #555; }
            p  { text-align: center; color: #888; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #ffc107; color: #333; padding: 10px; text-align: center; }
            td { padding: 8px; border: 1px solid #ccc; text-align: center; font-size: 13px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #aaa; }
        </style>
        <h2>MindMerge SmartCampus</h2>
        <h3>Fees Report</h3>
        <p>Generated on: " . date('d-m-Y H:i:s') . "</p>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th><th>Amount Paid</th><th>Payment Date</th>
                    <th>Payment Method</th><th>Receipt No</th><th>Status</th>
                </tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
        <div class='footer'>MindMerge SmartCampus &copy; " . date('Y') . "</div>
    ";
}

// =====================
// EXAM REPORT
// =====================
elseif($type == 'exam') {

    $result = $conn->query("
        SELECT s.name, e.exam_name, e.subject,
               e.total_marks, m.marks_obtained, m.grade
        FROM marks m
        JOIN students s ON m.student_id = s.id
        JOIN exams e ON m.exam_id = e.id
        ORDER BY e.exam_name, m.marks_obtained DESC
    ");

    $rows = '';
    while($row = $result->fetch_assoc()) {
        $rows .= "<tr>
            <td>{$row['name']}</td>
            <td>{$row['exam_name']}</td>
            <td>{$row['subject']}</td>
            <td>{$row['total_marks']}</td>
            <td>{$row['marks_obtained']}</td>
            <td><b>{$row['grade']}</b></td>
        </tr>";
    }

    $html = "
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2 { color: #003366; text-align: center; }
            h3 { text-align: center; color: #555; }
            p  { text-align: center; color: #888; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #dc3545; color: white; padding: 10px; text-align: center; }
            td { padding: 8px; border: 1px solid #ccc; text-align: center; font-size: 13px; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { text-align: center; margin-top: 30px; font-size: 11px; color: #aaa; }
        </style>
        <h2>MindMerge SmartCampus</h2>
        <h3>Exam Report</h3>
        <p>Generated on: " . date('d-m-Y H:i:s') . "</p>
        <table>
            <thead>
                <tr>
                    <th>Student Name</th><th>Exam Name</th><th>Subject</th>
                    <th>Total Marks</th><th>Marks Obtained</th><th>Grade</th>
                </tr>
            </thead>
            <tbody>$rows</tbody>
        </table>
        <div class='footer'>MindMerge SmartCampus &copy; " . date('Y') . "</div>
    ";
}

// =====================
// GENERATE PDF
// =====================
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "MindMerge-" . ucfirst($type) . "-Report-" . date('d-m-Y') . ".pdf";
$dompdf->stream($filename, ["Attachment" => true]);

exit();
?>