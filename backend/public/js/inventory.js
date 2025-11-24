// Wait for DOM to load
window.addEventListener('DOMContentLoaded', () => {
  let inventory = [];

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

  // 🔄 Fetch inventory from backend
  function fetchInventory() {
    fetch('../php/inventory.php')
      .then(res => res.json())
      .then(data => {
        console.log('Fetched inventory:', data); // ✅ Debug line
        inventory = data;
        renderInventory();
      })
      .catch(err => {
        console.error('Inventory fetch failed:', err);
      });
  }

  // 🧾 Render inventory table
function renderInventory(searchFilter = '', categoryFilter = '') {
  const tbody = document.getElementById('inventory-body');
  tbody.innerHTML = '';

  const lowerSearch = searchFilter.toLowerCase();
  const lowerCategory = categoryFilter.toLowerCase();

  // sort descending by qty
  const sorted = [...inventory].sort((a, b) => b.qty - a.qty);

  sorted.forEach(item => {
    const matchSearch =
      item.name.toLowerCase().includes(lowerSearch);

    const matchCategory =
      lowerCategory === '' || item.category.toLowerCase() === lowerCategory;

    if (!matchSearch || !matchCategory) return;

    const row = document.createElement('tr');
    if (item.qty <= item.threshold) row.style.backgroundColor = '#da3d3dff';

    row.innerHTML = `
      <td>${item.product_id}</td>
      <td>${item.name}</td>
      <td>${item.category}</td>
      <td>₱${item.price}</td>
      <td>${item.qty}</td>
      <td>
        <button class="action-btn" data-action="add" data-name="${item.name}">Add</button>
        <button class="action-btn" data-action="remove" data-name="${item.name}">Remove</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// 🔍 Search filter
document.getElementById('inventory-search').addEventListener('input', (e) => {
  const category = document.getElementById('category-filter').value;
  renderInventory(e.target.value, category);
});

// 📂 Category filter
document.getElementById('category-filter').addEventListener('change', (e) => {
  const search = document.getElementById('inventory-search').value;
  renderInventory(search, e.target.value);
});

  // 🧩 Modal logic
  const stockInModal = document.getElementById('stock-in-modal');
  const stockOutModal = document.getElementById('stock-out-modal');

  document.addEventListener('click', (e) => {
    if (e.target.matches('.action-btn')) {
      const action = e.target.dataset.action;
      const name = e.target.dataset.name;

      if (action === 'add') {
        document.getElementById('in-name').value = name;
        stockInModal.classList.remove('hidden');
      } else if (action === 'remove') {
        document.getElementById('out-name').value = name;
        stockOutModal.classList.remove('hidden');
      }
    }

    if (e.target.matches('.modal-close')) {
      e.target.closest('.modal').classList.add('hidden');
    }
  });

  // ✅ Submit Stock In
  window.submitStockIn = function () {
    const name = document.getElementById('in-name').value;
    const qty = parseInt(document.getElementById('in-qty').value);
    if (!qty || qty <= 0) return alert('Enter a valid quantity.');

    fetch('../php/inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, qty, action: 'add' })
    })
      .then(res => res.json())
      .then(() => {
        stockInModal.classList.add('hidden');
        fetchInventory();
      });
  };

  // ✅ Submit Stock Out
  window.submitStockOut = function () {
    const name = document.getElementById('out-name').value;
    const qty = parseInt(document.getElementById('out-qty').value);
    if (!qty || qty <= 0) return alert('Enter a valid quantity.');

    fetch('../php/inventory.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, qty, action: 'remove' })
    })
      .then(res => res.json())
      .then(() => {
        stockOutModal.classList.add('hidden');
        fetchInventory();
      });
  };

  // 🚀 Initial load
  fetchInventory();
});
