const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const chartGridConfig = {
  x: {
    grid: { display: false },
    ticks: { color: '#94a3b8', font: { size: 11 } }
  },
  y: {
    min: 0,
    max: 80,
    border: { dash: [4, 4] },
    grid: { color: '#e2e8f0', drawBorder: false }, 
    ticks: { stepSize: 20, color: '#94a3b8', font: { size: 11 } }
  }
};

// 1. Soil Moisture Chart
new Chart(document.getElementById('soilChart'), {
  type: 'line',
  data: {
    labels: days,
    datasets: [{
      data: [61, 58, 62, 53, 46, 60, 63],
      borderColor: '#22c55e',
      borderWidth: 2,
      backgroundColor: 'rgba(34, 197, 94, 0.12)',
      fill: true,
      tension: 0.4,
      pointRadius: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: chartGridConfig
  }
});

// 2. Temperature Chart
new Chart(document.getElementById('tempChart'), {
  type: 'line',
  data: {
    labels: days,
    datasets: [
      {
        data: [75, 78, 72, 80, 79, 77, 80],
        borderColor: '#06b6d4',
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 0
      },
      {
        data: [21, 23, 22, 25, 27, 26, 24],
        borderColor: '#ef4444',
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 0
      }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: chartGridConfig
  }
});

// 3. Water Usage Chart
new Chart(document.getElementById('waterChart'), {
  type: 'bar',
  data: {
    labels: days,
    datasets: [{
      data: [11, 8, 14, 10, 17, 13, 9],
      backgroundColor: '#22c55e',
      borderRadius: 4,
      barThickness: 32
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
      y: { min: 0, max: 20, grid: { color: '#e2e8f0' }, ticks: { stepSize: 5, color: '#94a3b8' } }
    }
  }
});

// 4. Crop Health Chart
new Chart(document.getElementById('healthChart'), {
  type: 'doughnut',
  data: {
    labels: ['Healthy', 'Needs Attention', 'Critical'],
    datasets: [{
      data: [62, 28, 10],
      backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
      borderWidth: 0,
      cutout: '70%'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }
  }
});