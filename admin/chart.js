document.addEventListener('DOMContentLoaded', function ()  {
  fetch('admin_chart.php')
    .then(response => response.json())
    .then(data => {

      // ========== Dashboard Charts ==========

      // 1. Monthly Recycling Log Chart
      if (document.getElementById('recyclingChart')) {
        const recyclingLabels = data.recycling_data.map(entry => entry.month);
        const recyclingWeights = data.recycling_data.map(entry => entry.total_weight);

        const ctx1 = document.getElementById('recyclingChart').getContext('2d');
        new Chart(ctx1, {
          type: 'bar',
          data: {
            labels: recyclingLabels,
            datasets: [{
              label: 'Total Recycling Logs (kg)',
              data: recyclingWeights,
              backgroundColor: '#3ba99c',
              borderColor: '#1e3a34',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: 'top' },
              title: {
                display: true,
                text: 'Monthly Recycling Log (Total Weight in KG)'
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Weight (kg)'
                }
              }
            }
          }
        });
      }

      // 2. Monthly Event Attendance Chart
      if (document.getElementById('eventAttendanceChart')) {
        const eventLabels = data.event_data.map(entry => entry.month);
        const attendanceCounts = data.event_data.map(entry => entry.attendance_count);

        const ctx2 = document.getElementById('eventAttendanceChart').getContext('2d');
        new Chart(ctx2, {
          type: 'bar',
          data: {
            labels: eventLabels,
            datasets: [{
              label: 'Event Attendance (Participants)',
              data: attendanceCounts,
              backgroundColor: '#c97c1a',
              borderColor: '#1e3a34',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { position: 'top' },
              title: {
                display: true,
                text: 'Monthly Event Attendance'
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Number of Attendees'
                }
              }
            }
          }
        });
      }

      
      /* =====================
         Logs by Material (BAR)
         ===================== */
      const materialCanvas = document.getElementById("barLogsByMaterial");
      if (materialCanvas && data.byMaterial) {
        new Chart(materialCanvas.getContext("2d"), {
          type: "bar",
          data: {
            labels: data.byMaterial.labels,
            datasets: theData = [{
              label: "Total Logs",
              data: data.byMaterial.values,
              backgroundColor: "#3ba99c"
            }]
          }
        });
      }

      /* =====================
         Logs Over Time (LINE)
         ===================== */
      const timeCanvas = document.getElementById("lineLogsOverTime");
      if (timeCanvas && data.overTime) {
        new Chart(timeCanvas.getContext("2d"), {
          type: "line",
          data: {
            labels: data.overTime.labels,
            datasets: [{
              label: "Logs",
              data: data.overTime.values,
              borderColor: "#1e3a34",
              fill: false,
              tension: 0.3
            }]
          }
        });
      }

    })
    .catch(error => {
      console.error('Error loading chart data:', error);
    });
});