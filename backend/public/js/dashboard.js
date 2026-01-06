window.addEventListener('DOMContentLoaded', () => {
  // ⏰ Real-time clock
  function updateDateTime() {
    const now = new Date();
    const options = {
      hour: 'numeric', minute: 'numeric', hour12: true,
      year: 'numeric', month: 'short', day: 'numeric'
    };
    document.getElementById('datetime').textContent = now.toLocaleString('en-PH', options);
  }
  setInterval(updateDateTime, 1000);
  updateDateTime();

  // 🔒 Logout
  document.querySelector(".logout-btn")?.addEventListener("click", () => {
    if (confirm("Are you sure you want to logout?")) {
      window.location.href = "login.html"; 
    }
  });

  let inventory = [];

  // 📦 Restock alerts
  function renderRestockAlerts() {
    const container = document.getElementById('alert-bubbles');
    container.innerHTML = '';
    inventory.forEach(item => {
      if (parseInt(item.qty) < parseInt(item.threshold)) {
        const bubble = document.createElement('div');
        bubble.classList.add('alert-bubble');
        bubble.innerHTML = `<strong>Low Stock:</strong> ${item.name}<br>
                            <em>Qty:</em> ${item.qty} / <em>Threshold:</em> ${item.threshold}`;
        container.appendChild(bubble);
      }
    });
  }

  function fetchInventory() {
    fetch('../php/inventory.php')
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          inventory = data.inventory || [];
          renderRestockAlerts();
        }
      });
  }

  // 📊 Sales overview
  async function fetchSalesOverview(cashierId = '') {
    const res = await fetch(`../php/get-sales.php${cashierId ? '?cashier='+cashierId : ''}`);
    const data = await res.json();
    if (data.status !== 'success') return;

    function findTotal(arr) {
      if (!cashierId) return arr.reduce((sum, r) => sum + parseFloat(r.total), 0);
      const row = arr.find(r => r.cashier_id == cashierId);
      return row ? parseFloat(row.total) : 0;
    }

    document.getElementById('daily-sales').textContent   = `₱${findTotal(data.daily).toFixed(2)}`;
    document.getElementById('weekly-sales').textContent  = `₱${findTotal(data.weekly).toFixed(2)}`;
    document.getElementById('monthly-sales').textContent = `₱${findTotal(data.monthly).toFixed(2)}`;
  }

  // Populate cashier dropdown
  async function loadCashiers() {
    const select = document.getElementById('cashier-filter');
    select.innerHTML = '<option value="">All Cashiers</option>';
    const res = await fetch('../php/get-users.php');
    const data = await res.json();
    const users = Array.isArray(data) ? data : (data.users || []);
    users.forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.user_id;
      opt.textContent = u.username || u.email;
      select.appendChild(opt);
    });
  }

  document.getElementById('cashier-filter')?.addEventListener('change', e => {
    fetchSalesOverview(e.target.value);
  });

  // 🚀 Initial load
  loadCashiers();
  fetchSalesOverview();
  fetchInventory();

  setInterval(() => {
    fetchSalesOverview(document.getElementById('cashier-filter').value || '');
    fetchInventory();
  }, 30000);
});
