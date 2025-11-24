function updateDateTime() {
  const now = new Date();
  const options = { hour: 'numeric', minute: 'numeric', hour12: true, year: 'numeric', month: 'short', day: 'numeric' };
  const el = document.getElementById('datetime');
  if (el) el.textContent = now.toLocaleString('en-PH', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

let forecastChart;

async function fetchForecastAll() {
  try {
    const res = await fetch('/php/get-forecast.php', { cache: 'no-cache' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    if (data.status !== 'success') throw new Error('Invalid response');

    renderForecastChart(data.forecasts, data.actuals);
    renderMetrics(data);
    renderInventorySummary(data.inventory_summary);
  } catch (err) {
    console.error('Forecast fetch error:', err);
    alert('Error loading forecast data. Check PHP path and run Python script.');
  }
}

function renderForecastChart(forecasts, actuals) {
  const ctx = document.getElementById('forecast-chart').getContext('2d');

  const labels = forecasts.map(f => f.date);
  const forecastValues = forecasts.map(f => f.forecast_amount);
  const actualMap = new Map(actuals.map(a => [a.date, a.actual_sales]));
  const actualValues = labels.map(d => actualMap.get(d) ?? null);

  if (forecastChart) forecastChart.destroy();

  forecastChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Forecasted Sales (₱)',
          data: forecastValues,
          borderColor: '#b30000',
          backgroundColor: 'rgba(179,0,0,0.12)',
          borderWidth: 2,
          fill: true,
          tension: 0.3
        },
        {
          label: 'Actual Sales (₱)',
          data: actualValues,
          borderColor: '#0077cc',
          backgroundColor: 'rgba(0,119,204,0.10)',
          borderWidth: 2,
          fill: false,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: (ctx) => {
              const v = ctx.parsed.y;
              return `${ctx.dataset.label}: ₱${v?.toLocaleString('en-PH', { maximumFractionDigits: 2 })}`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: (v) => `₱${Number(v).toLocaleString('en-PH')}` }
        }
      }
    }
  });
}

function renderMetrics(data) {
  const m = data.metrics || {};
  document.getElementById('model-name').textContent = data.model || 'N/A';
  document.getElementById('mape-value').textContent = (m.mape ?? 0).toFixed(2);
  document.getElementById('rmse-value').textContent = (m.rmse ?? 0).toFixed(2);
  document.getElementById('mae-value').textContent = (m.mae ?? 0).toFixed(2);
  document.getElementById('trained-date').textContent = data.trained_on || '';
  document.getElementById('horizon-days').textContent = data.horizon ?? 0;
}

function renderInventorySummary(items = []) {
  const tbody = document.querySelector('#inventory-table tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  items.forEach(it => {
    const statusClass =
      it.status.startsWith('⚠️ Restock') ? 'status-restock' :
      it.status.startsWith('⚠️ Stock low') ? 'status-low' : 'status-ok';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${it.product_id}</td>
      <td>${escapeHtml(it.name)}</td>
      <td>${it.avg_forecast_qty}</td>
      <td>${it.current_qty}</td>
      <td>${it.threshold}</td>
      <td class="${statusClass}">${escapeHtml(it.status)}</td>
    `;
    tbody.appendChild(tr);
  });
}

function escapeHtml(str) {
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
  fetchForecastAll();
});

document.querySelector('.logout-btn')?.addEventListener('click', () => {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = '../html/login.html';
  }
  
});
