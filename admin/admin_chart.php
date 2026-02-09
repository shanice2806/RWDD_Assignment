<?php
include "../connect.php";

// 1. Monthly Recycling Log (VALID only)
$recyclingSql = "
  SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, 
         SUM(weight_kg) AS total_weight
  FROM recycling_log
  WHERE status = 'VALID'
  GROUP BY month
  ORDER BY month ASC
";

$recyclingResult = $conn->query($recyclingSql);
$recyclingData = [];

if ($recyclingResult && $recyclingResult->num_rows > 0) {
  while ($row = $recyclingResult->fetch_assoc()) {
    $recyclingData[] = [
      'month' => $row['month'],
      'total_weight' => round((float)$row['total_weight'], 2)
    ];
  }
}

// 2. Monthly Event Attendance (Join attendance + events table)
$eventSql = "
  SELECT DATE_FORMAT(e.event_date_time, '%Y-%m') AS event_month,
         COUNT(DISTINCT a.user_id) AS attendance_count
  FROM attendance a
  JOIN events e ON a.event_id = e.event_id
  GROUP BY event_month
  ORDER BY event_month ASC
";

$eventResult = $conn->query($eventSql);
$eventData = [];

if ($eventResult && $eventResult->num_rows > 0) {
  while ($row = $eventResult->fetch_assoc()) {
    $eventData[] = [
      'month' => $row['event_month'],
      'attendance_count' => (int)$row['attendance_count']
    ];
  }
}


/* Logs by Material */
$materialData = [];
$res = $conn->query("
    SELECT m.materials_name, COUNT(*) AS total
    FROM recycling_log rl
    JOIN materials m ON rl.material_id = m.material_id
    GROUP BY rl.material_id
");

while ($row = $res->fetch_assoc()) {
    $materialData['labels'][] = $row['materials_name'];
    $materialData['values'][] = $row['total'];
}


// Final JSON output
header('Content-Type: application/json');
echo json_encode([
  'recycling_data' => $recyclingData,
  'event_data' => $eventData,
  'byMaterial' => $materialData,
    'overTime' => $timeData
]);
?>

