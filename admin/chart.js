document.addEventListener("DOMContentLoaded", function () {
  fetch('admin_chart.php')
    .then(response => response.json())
    .then(data => {
      const labels = data.map(entry => entry.month);
      const counts = data.map(entry => entry.total_logs);

      const ctx = document.getElementById('recyclingChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Total Recycling Logs',
            data: counts,
            backgroundColor: '#3ba99c',
            borderColor: '#1e3a34',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                stepSize: 1
              }
            }
          }
        }
      });
    })
    .catch(error => console.error('Error fetching chart data:', error));
});
