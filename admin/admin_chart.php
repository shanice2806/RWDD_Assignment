<?php
include "../connect.php";

$recyclingData = [];

$recyclingSql = "
  SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month,
         SUM(weight_kg) AS total_weight
  FROM recycling_log
  WHERE status = 'VALID'
  GROUP BY month
  ORDER BY month ASC
";

$res = $conn->query($recyclingSql);
while ($row = $res->fetch_assoc()) {
  $recyclingData[] = [
    'label' => $row['month'],
    'value' => round((float)$row['total_weight'], 2)
  ];
}



$eventData = [];

$eventSql = "
  SELECT DATE_FORMAT(e.event_date_time, '%Y-%m') AS event_month,
         COUNT(DISTINCT a.user_id) AS attendance_count
  FROM attendance a
  JOIN events e ON a.event_id = e.event_id
  GROUP BY event_month
  ORDER BY event_month ASC
";

$res = $conn->query($eventSql);
while ($row = $res->fetch_assoc()) {
  $eventData[] = [
    'label' => $row['event_month'],
    'value' => (int)$row['attendance_count']
  ];
}



$materialData = [];

$materialSql = "
  SELECT m.materials_name AS material,
         COUNT(*) AS total
  FROM recycling_log rl
  JOIN materials m ON rl.material_id = m.material_id
  GROUP BY rl.material_id
  ORDER BY total DESC
";

$res = $conn->query($materialSql);
while ($row = $res->fetch_assoc()) {
  $materialData[] = [
    'label' => $row['material'],
    'value' => (int)$row['total']
  ];
}



header('Content-Type: application/json');
echo json_encode([
  'recycling_data' => $recyclingData,
  'event_data'     => $eventData,
  'byMaterial'     => $materialData
]);
