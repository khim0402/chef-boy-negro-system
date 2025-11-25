function updateDateTime() {
  const now = new Date();
  const options = {
    hour: 'numeric',
    minute: 'numeric',
    hour12: true,
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  };
  document.getElementById('datetime').textContent = now.toLocaleString('en-PH', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

// 🔄 Load sales from backend (absolute path)
function fetchSales(start = null, end = null) {
  let url = '/php/get-sales.php';
  if (start && end) {
    url += `?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`;
  }

  fetch(url)
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'success') {
        alert(data.message || 'Failed to load sales data.');
        return;
      }

      renderSales(data.sales);
      updateTotals(data.daily, data.weekly, data.monthly);
    })
    .catch(err => {
      console.error('Sales fetch error:', err);
      alert('Error fetching sales data.');
    });
}

// 🧾 Render sales table
function renderSales(sales) {
  const tbody = document.getElementById('sales-body');
  tbody.innerHTML = '';

  sales.forEach(sale => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${sale.date}</td>
      <td>${sale.product}</td>
      <td>${sale.method}</td>
      <td>${sale.qty}</td>
      <td>₱${parseFloat(sale.amount).toFixed(2)}</td>
    `;
    tbody.appendChild(row);
  });
}

// 📊 Update overview cards
function updateTotals(daily = 0, weekly = 0, monthly = 0) {
  document.getElementById('daily-sales').textContent = `₱${parseFloat(daily).toFixed(2)}`;
  document.getElementById('weekly-sales').textContent = `₱${parseFloat(weekly).toFixed(2)}`;
  document.getElementById('monthly-sales').textContent = `₱${parseFloat(monthly).toFixed(2)}`;
}

// 📅 Filter sales by date range
function filterSales() {
  const start = document.getElementById('start-date').value;
  const end = document.getElementById('end-date').value;

  if (!start || !end) {
    alert('Please select both start and end dates.');
    return;
  }

  fetchSales(start, end);
}

// 🔚 Logout logic
document.querySelector('.logout-btn').addEventListener('click', () => {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = '/html/login.html';
  }
});

// 🚀 Initial load
document.addEventListener('DOMContentLoaded', () => {
  fetchSales();
});
