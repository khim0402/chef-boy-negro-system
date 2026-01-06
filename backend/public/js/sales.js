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
  const el = document.getElementById('datetime');
  if (el) el.textContent = now.toLocaleString('en-PH', options);
}
setInterval(updateDateTime, 1000);
updateDateTime();

// Populate cashier dropdown
async function loadCashiers() {
  const select = document.getElementById('cashier-filter');
  if (!select) return;

  select.innerHTML = '<option value="">All Cashiers</option>';

  try {
    const res = await fetch('/php/get-users.php');
    const data = await res.json();

    // Support both {status:'success', users:[...]} and raw array formats
    const users = Array.isArray(data) ? data : (Array.isArray(data.users) ? data.users : []);

    users
      .filter(u => String(u.role).toLowerCase() === 'cashier') // adjust if your role text differs
      .forEach(u => {
        const opt = document.createElement('option');
        opt.value = u.user_id;
        opt.textContent = `${u.email}`;
        select.appendChild(opt);
      });
  } catch (err) {
    console.error('Failed to load cashiers:', err);
  }
}

// 🔄 Load sales from backend with optional filters
function fetchSales(start = null, end = null, cashier = null) {
  let url = '/php/get-sales.php';
  const params = [];
  if (start && end) {
    params.push(`start=${encodeURIComponent(start)}`, `end=${encodeURIComponent(end)}`);
  }
  if (cashier) {
    params.push(`cashier=${encodeURIComponent(cashier)}`);
  }
  if (params.length) {
    url += `?${params.join('&')}`;
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
      <td>${sale.payment_method}</td>
      <td>${sale.qty}</td>
      <td>₱${parseFloat(sale.amount).toFixed(2)}</td>
      <td>${sale.cashier || '-'}</td>
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
  const cashier = document.getElementById('cashier-filter')?.value || '';

  if (!start || !end) {
    alert('Please select both start and end dates.');
    return;
  }

  fetchSales(start, end, cashier);
}

// 🔚 Logout logic
document.querySelector('.logout-btn')?.addEventListener('click', () => {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = '/html/login.html';
  }
});

// Cashier filter change
document.getElementById('cashier-filter')?.addEventListener('change', (e) => {
  const cashier = e.target.value || '';
  const start = document.getElementById('start-date').value || null;
  const end = document.getElementById('end-date').value || null;
  fetchSales(start, end, cashier);
});

// 🚀 Initial load
document.addEventListener('DOMContentLoaded', () => {
  loadCashiers();
  fetchSales();
});
