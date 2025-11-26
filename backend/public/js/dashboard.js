window.addEventListener('DOMContentLoaded', () => {
  console.log("Dashboard JS loaded"); // Debug marker

  // ⏰ Real-time clock
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

  // 🔒 Logout button
  const logoutBtn = document.querySelector(".logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      console.log("Logout button clicked"); // Debug marker
      if (confirm("Are you sure you want to logout?")) {
        // Adjust path depending on where your login.html is located
        window.location.href = "login.html"; 
      }
    });
  } else {
    console.error("Logout button not found!");
  }

  let inventory = [];

  // 📦 Render restock alerts
  function renderRestockAlerts() {
    const container = document.getElementById('alert-bubbles');
    if (!container) return;
    container.innerHTML = '';

    inventory.forEach(item => {
      const qty = parseInt(item.qty ?? 0);
      const threshold = parseInt(item.threshold ?? 0);
      if (qty < threshold) {
        const bubble = document.createElement('div');
        bubble.classList.add('alert-bubble');
        bubble.innerHTML = `
          <strong>Low Stock:</strong> ${item.name}<br>
          <em>Qty:</em> ${qty} / <em>Threshold:</em> ${threshold}
        `;
        container.appendChild(bubble);
      }
    });
  }

  // 🔄 Fetch inventory
  function fetchInventoryForDashboard() {
  fetch('../php/inventory.php')
    .then(res => res.json())
    .then(data => {
      console.log("Inventory data:", data); // Debug
      if (data.status === 'success') {
        inventory = Array.isArray(data.inventory) ? data.inventory : [];
        renderRestockAlerts();
      } else {
        console.error("Inventory fetch error:", data.message);
      }
    })
    .catch(err => {
      console.error('Failed to fetch inventory for dashboard:', err);
    });
}

  // 📊 Fetch sales overview
  function fetchSalesOverview() {
    fetch('../php/get-sales.php')
      .then(res => res.json())
      .then(data => {
        console.log("Sales data:", data); // Debug
        if (data.status !== 'success') return;
        document.getElementById('daily-sales').textContent = `₱${parseFloat(data.daily).toFixed(2)}`;
        document.getElementById('weekly-sales').textContent = `₱${parseFloat(data.weekly).toFixed(2)}`;
        document.getElementById('monthly-sales').textContent = `₱${parseFloat(data.monthly).toFixed(2)}`;
      })
      .catch(err => {
        console.error('Failed to fetch sales overview:', err);
      });
  }

  // 🚀 Initial load
  fetchSalesOverview();
  fetchInventoryForDashboard();

  // 🔁 Auto-refresh every 30 seconds
  setInterval(() => {
    fetchSalesOverview();
    fetchInventoryForDashboard();
  }, 30000);
});
