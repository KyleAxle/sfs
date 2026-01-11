<?php
/**
 * Diagnostic script to test feedback query
 * This helps verify if feedback data exists and is being fetched correctly
 */

session_start();
$pdo = require __DIR__ . '/config/db.php';

// Get office ID from query parameter or use a test office
$officeParam = isset($_GET['office']) ? strtolower(trim($_GET['office'])) : 'assessment office';

$stmtOffice = $pdo->prepare("select office_id, office_name from public.offices where lower(office_name) = :name");
$stmtOffice->execute([':name' => $officeParam]);
$office = $stmtOffice->fetch();

if (!$office) {
    die("Office not found: " . htmlspecialchars($officeParam));
}

$officeId = (int)$office['office_id'];
$officeName = $office['office_name'];

echo "<h2>Testing Feedback Query for: " . htmlspecialchars($officeName) . "</h2>";

// Test 1: Check if feedback table exists and has data
echo "<h3>Test 1: Check feedback table</h3>";
$feedbackCheck = $pdo->query("SELECT COUNT(*) as count FROM public.feedback");
$feedbackCount = $feedbackCheck->fetch(PDO::FETCH_ASSOC);
echo "Total feedback records: " . $feedbackCount['count'] . "<br>";

// Test 2: Check appointments with feedback for this office
echo "<h3>Test 2: Appointments with feedback for this office</h3>";
$sql = "
    SELECT 
        a.appointment_id,
        a.status,
        f.rating,
        f.comment,
        f.submitted_at
    FROM public.appointments a
    JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
    LEFT JOIN public.feedback f ON f.appointment_id = a.appointment_id
    WHERE ao.office_id = ?
    ORDER BY a.appointment_date DESC
    LIMIT 10
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$officeId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Appointment ID</th><th>Status</th><th>Rating</th><th>Comment</th><th>Submitted At</th></tr>";
foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['appointment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . ($row['rating'] !== null ? htmlspecialchars($row['rating']) : 'NULL') . "</td>";
    echo "<td>" . ($row['comment'] !== null ? htmlspecialchars(substr($row['comment'], 0, 50)) : 'NULL') . "</td>";
    echo "<td>" . ($row['submitted_at'] !== null ? htmlspecialchars($row['submitted_at']) : 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Full query as used in office_dashboard.php
echo "<h3>Test 3: Full query (as in office_dashboard.php)</h3>";
$sql = "
    SELECT
        a.appointment_id,
        o.office_name,
        u.first_name,
        u.last_name,
        u.email,
        a.appointment_date,
        a.appointment_time,
        a.concern,
        a.status,
        f.rating,
        f.comment as feedback_comment,
        f.submitted_at as feedback_submitted_at
    FROM public.appointments a
    JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
    JOIN public.users u ON a.user_id = u.user_id
    JOIN public.offices o ON ao.office_id = o.office_id
    LEFT JOIN public.feedback f ON f.appointment_id = a.appointment_id
    WHERE o.office_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$officeId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Appointment ID</th><th>Name</th><th>Status</th><th>Rating</th><th>Comment</th></tr>";
foreach ($appointments as $row) {
    $hasFeedback = isset($row['rating']) && $row['rating'] !== null && $row['rating'] !== '';
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['appointment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "<td>" . ($hasFeedback ? htmlspecialchars($row['rating']) : 'NO FEEDBACK') . "</td>";
    echo "<td>" . ($row['feedback_comment'] ? htmlspecialchars(substr($row['feedback_comment'], 0, 50)) : 'NO COMMENT') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='office_dashboard.php?office=" . urlencode($officeParam) . "'>Back to Office Dashboard</a></p>";
?>

