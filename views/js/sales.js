$(document).ready(function () {
  const chartCanvas = document.getElementById('salesChart');
  const data = window.salesChartData || { labels: [], gross: [], expenses: [], net: [] };

  if (!chartCanvas || typeof Chart === 'undefined') {
    return;
  }

  if (!Array.isArray(data.labels) || data.labels.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'sales-empty';
    empty.textContent = 'No completed sales data available for the chart yet.';
    chartCanvas.parentNode.replaceChild(empty, chartCanvas);
    return;
  }

  new Chart(chartCanvas, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [
        {
          label: 'Gross Sales',
          data: data.gross,
          borderColor: '#28c76f',
          backgroundColor: 'rgba(40,199,111,0.12)',
          tension: 0.35,
          fill: true
        },
        {
          label: 'Expenses',
          data: data.expenses,
          borderColor: '#ff3e1d',
          backgroundColor: 'rgba(255,62,29,0.08)',
          tension: 0.35,
          fill: true
        },
        {
          label: 'Net Sales',
          data: data.net,
          borderColor: '#696cff',
          backgroundColor: 'rgba(105,108,255,0.10)',
          tension: 0.35,
          fill: true
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          position: 'bottom'
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              return context.dataset.label + ': PHP ' + Number(context.raw || 0).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              });
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function (value) {
              return 'PHP ' + Number(value).toLocaleString();
            }
          }
        }
      }
    }
  });
});
