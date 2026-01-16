<?php
include '../connect.php';

$query = "
  SELECT DATE(submitted_at) as date, SUM(weight_kg) as total_weight
  FROM recycling_log
  WHERE status = 'VALID'
  GROUP BY DATE(submitted_at)
  ORDER BY DATE(submitted_at) ASC
";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'date' => $row['date'],
        'total_weight' => floatval($row['total_weight']),
    ];
}

echo json_encode($data);
?>

