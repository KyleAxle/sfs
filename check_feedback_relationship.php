<?php
/**
 * Check if feedback is properly linked to appointments and offices
 */

$pdo = require __DIR__ . '/config/db.php';

echo "<h2>Feedback Relationship Check</h2>";

// Check 1: See all feedback records
echo "<h3>1. All Feedback Records</h3>";
$allFeedback = $pdo->query("SELECT feedback_id, appointment_id, rating, comment, submitted_at FROM public.feedback ORDER BY submitted_at DESC LIMIT 10");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Feedback ID</th><th>Appointment ID</th><th>Rating</th><th>Comment</th><th>Submitted At</th></tr>";
foreach ($allFeedback as $fb) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($fb['feedback_id']) . "</td>";
    echo "<td>" . htmlspecialchars($fb['appointment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($fb['rating']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($fb['comment'] ?? '', 0, 50)) . "</td>";
    echo "<td>" . htmlspecialchars($fb['submitted_at']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check 2: Verify appointment_id exists in appointments table
echo "<h3>2. Verify Feedback -> Appointments Relationship</h3>";
$checkRel = $pdo->query("
    SELECT 
        f.feedback_id,
        f.appointment_id as feedback_appt_id,
        a.appointment_id as appointment_appt_id,
        a.status
    FROM public.feedback f
    LEFT JOIN public.appointments a ON a.appointment_id = f.appointment_id
    LIMIT 10
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Feedback ID</th><th>Feedback Appt ID</th><th>Appointment Appt ID</th><th>Status</th><th>Match?</th></tr>";
foreach ($checkRel as $row) {
    $match = $row['feedback_appt_id'] == $row['appointment_appt_id'] ? 'YES' : 'NO';
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['feedback_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['feedback_appt_id']) . "</td>";
    echo "<td>" . ($row['appointment_appt_id'] ? htmlspecialchars($row['appointment_appt_id']) : 'NULL') . "</td>";
    echo "<td>" . ($row['status'] ? htmlspecialchars($row['status']) : 'NULL') . "</td>";
    echo "<td><strong>" . $match . "</strong></td>";
    echo "</tr>";
}
echo "</table>";

// Check 3: Check appointments -> appointment_offices -> offices relationship
echo "<h3>3. Verify Appointments -> Offices Relationship</h3>";
$checkOfficeRel = $pdo->query("
    SELECT 
        a.appointment_id,
        ao.office_id,
        o.office_name,
        a.status
    FROM public.appointments a
    INNER JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
    INNER JOIN public.offices o ON o.office_id = ao.office_id
    WHERE a.appointment_id IN (SELECT DISTINCT appointment_id FROM public.feedback LIMIT 5)
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Appointment ID</th><th>Office ID</th><th>Office Name</th><th>Status</th></tr>";
foreach ($checkOfficeRel as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['appointment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['office_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['office_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check 4: Full chain test - feedback -> appointments -> offices
echo "<h3>4. Full Chain: Feedback -> Appointments -> Offices</h3>";
$fullChain = $pdo->query("
    SELECT 
        f.feedback_id,
        f.appointment_id,
        f.rating,
        f.comment,
        a.status as appointment_status,
        o.office_name
    FROM public.feedback f
    INNER JOIN public.appointments a ON a.appointment_id = f.appointment_id
    INNER JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
    INNER JOIN public.offices o ON o.office_id = ao.office_id
    ORDER BY f.submitted_at DESC
    LIMIT 10
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Feedback ID</th><th>Appointment ID</th><th>Rating</th><th>Comment</th><th>Appointment Status</th><th>Office Name</th></tr>";
foreach ($fullChain as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['feedback_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['appointment_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['rating']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($row['comment'] ?? '', 0, 50)) . "</td>";
    echo "<td>" . htmlspecialchars($row['appointment_status']) . "</td>";
    echo "<td>" . htmlspecialchars($row['office_name']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>If you see data in section 4, the relationships are working correctly!</strong></p>";
?>

