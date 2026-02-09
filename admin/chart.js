/* =========================================
   FETCH ALL CHART DATA
========================================= */
fetch("admin_chart.php")
  .then(res => res.json())
  .then(data => {

    /* Monthly Recycling Log */
    drawBarChart(
      "recyclingChart",
      data.recycling_data,
      "total_weight",
      "#3ba99c"
    );

    /* Monthly Event Attendance */
    drawBarChart(
      "eventAttendanceChart",
      data.event_data,
      "attendance_count",
      "#1e3a34"
    );

    /* Logs by Material */
    drawBarChartByLabel(
      "barLogsByMaterial",
      data.byMaterial.labels,
      data.byMaterial.values,
      "#3ba99c"
    );

  });

/* =========================================
   BAR CHART (Month-based data)
========================================= */
function drawBarChart(canvasId, dataset, valueKey, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || dataset.length === 0) return;

  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const maxValue = Math.max(...dataset.map(d => d[valueKey]));
  const barWidth = 40;
  const gap = 20;
  const baseY = canvas.height - 40;

  dataset.forEach((item, index) => {
    const x = index * (barWidth + gap) + 40;
    const height = (item[valueKey] / maxValue) * 180;
    const y = baseY - height;

    // Bar
    ctx.fillStyle = color;
    ctx.fillRect(x, y, barWidth, height);

    // Value label
    ctx.fillStyle = "#000";
    ctx.font = "12px Times New Roman";
    ctx.fillText(item[valueKey], x + 5, y - 5);

    // Month label
    ctx.fillText(item.month, x - 5, baseY + 15);
  });
}

/* =========================================
   BAR CHART (Label-based data)
========================================= */
function drawBarChartByLabel(canvasId, labels, values, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || values.length === 0) return;

  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const maxValue = Math.max(...values);
  const barWidth = 40;
  const gap = 25;
  const baseY = canvas.height - 40;

  values.forEach((value, index) => {
    const x = index * (barWidth + gap) + 40;
    const height = (value / maxValue) * 160;
    const y = baseY - height;

    // Bar
    ctx.fillStyle = color;
    ctx.fillRect(x, y, barWidth, height);

    // Value label
    ctx.fillStyle = "#000";
    ctx.font = "12px Times New Roman";
    ctx.fillText(value, x + 5, y - 5);

    // Label
    ctx.fillText(labels[index], x - 10, baseY + 15);
  });
}

/* =========================================
   LINE CHART (Time-based data)
========================================= */
function drawLineChart(canvasId, labels, values, color) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || values.length === 0) return;

  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const maxValue = Math.max(...values);
  const baseY = canvas.height - 40;
  const gap = (canvas.width - 80) / (values.length - 1);

  ctx.beginPath();
  ctx.strokeStyle = color;
  ctx.lineWidth = 2;

  values.forEach((value, index) => {
    const x = index * gap + 40;
    const y = baseY - (value / maxValue) * 160;

    if (index === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }

    // Point
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.arc(x, y, 3, 0, Math.PI * 2);
    ctx.fill();

    // Date label
    ctx.fillStyle = "#000";
    ctx.font = "11px Times New Roman";
    ctx.fillText(labels[index], x - 12, baseY + 15);
  });

  ctx.stroke();
}
