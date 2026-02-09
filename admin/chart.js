
fetch("admin_chart.php")
  .then(res => res.json())
  .then(data => {

    // Monthly Recycling Log
    drawBarChart(
      "recyclingChart",
      data.recycling_data,
      "#3ba99c"
    );

    // Monthly Event Attendance
    drawBarChart(
      "eventAttendanceChart",
      data.event_data,
      "#1e3a34"
    );

    // Logs by Material
    drawBarChart(
      "barLogsByMaterial",
      data.byMaterial,
      "#3ba99c"
    );

  });


function drawBarChart(canvasId, dataset, color) {

  const canvas = document.getElementById(canvasId);
  if (!canvas || !dataset || dataset.length === 0) return;

  const ctx = canvas.getContext("2d");
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const maxValue = Math.max(...dataset.map(d => d.value));
  const barWidth = 40;
  const gap = 25;
  const baseY = canvas.height - 40;

  dataset.forEach((item, index) => {

    const x = index * (barWidth + gap) + 40;
    const height = (item.value / maxValue) * 180;
    const y = baseY - height;

    // Bar
    ctx.fillStyle = color;
    ctx.fillRect(x, y, barWidth, height);

    // Value label
    ctx.fillStyle = "#000";
    ctx.font = "12px Times New Roman";
    ctx.fillText(item.value, x + 5, y - 5);

    // X-axis label
    ctx.fillText(item.label, x - 10, baseY + 15);
  });
}
