document.addEventListener('DOMContentLoaded', () => {
  // 🔒 Hide overlays on load
  document.querySelectorAll('.modal, .stock-window').forEach(e => e.classList.add('hidden'));

  // 🕒 Real-Time Clock
  function updateDateTime() {
    const now = new Date();
    const formatted = now.toLocaleString('en-PH', {
      weekday: 'short',
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
    const el = document.getElementById('datetime');
    if (el) el.textContent = formatted;
  }
  setInterval(updateDateTime, 1000);
  updateDateTime();

  // 👤 Current cashier from localStorage
  const cashierId = localStorage.getItem('cashier_id');
  const cashierName = localStorage.getItem('cashier_username');
const cashierEl = document.getElementById('current-cashier');
if (cashierEl) {
  cashierEl.textContent = cashierName ? `Cashier: ${cashierName}` : 'Cashier: Unknown';
}

  // 🧾 Modal Logic
  const modal = document.getElementById('category-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalItems = document.getElementById('modal-items');
  const closeBtn = document.querySelector('.modal-close');

  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      modal.classList.add('hidden');
      modalItems.innerHTML = '';
    });
  }

  // 🧠 Full Item Mapping (14 Categories)
  const items = [
    { product_id: 1, category: 'Pork', name: 'Bagnet Kare-kare (10-12PAX)', price: 1450 },
    { product_id: 2, category: 'Pork', name: 'Bagnet Kare-kare (20PAX)', price: 2450 },
    { product_id: 3, category: 'Pork', name: 'Bagnet Dinakdakan (10-12PAX)', price: 1350 },
    { product_id: 4, category: 'Pork', name: 'Bagnet Dinakdakan (20PAX)', price: 2450 },
    { product_id: 5, category: 'Pork', name: 'Tofu Con Lechon (10-12PAX)', price: 1300 },
    { product_id: 6, category: 'Pork', name: 'Tofu Con Lechon (20PAX)', price: 2300 },
    { product_id: 7, category: 'Pork', name: 'Pork Sisig (10-12PAX)', price: 1350 },
    { product_id: 8, category: 'Pork', name: 'Pork Sisig (20PAX)', price: 2350 },
    { product_id: 9, category: 'Pork', name: 'Creamy Lengua (10-12PAX)', price: 1950 },
    { product_id: 10, category: 'Pork', name: 'Creamy Lengua (20PAX)', price: 2950 },
    { product_id: 11, category: 'Pork', name: 'Crispy Pata (4-5PAX)', price: 825 },
    { product_id: 12, category: 'Pork', name: 'Crispy Ulo (4-5PAX)', price: 825 },

    { product_id: 13, category: 'Seafood', name: 'Fish Fillet w/ Sour Cream Dip (10-12PAX)', price: 1099 },
    { product_id: 14, category: 'Seafood', name: 'Fish Fillet w/ Sour Cream Dip (20PAX)', price: 1899 },
    { product_id: 15, category: 'Seafood', name: 'Sweet & Sour Fish Fillet (10-12PAX)', price: 1099 },
    { product_id: 16, category: 'Seafood', name: 'Sweet & Sour Fish Fillet (20PAX)', price: 1899 },
    { product_id: 17, category: 'Seafood', name: 'Boneless Daing na Bangus (3-4PAX)', price: 450 },
    { product_id: 18, category: 'Seafood', name: 'Cheesy Baked Bangus (3-4PAX)', price: 650 },
    { product_id: 19, category: 'Seafood', name: 'Calamares (10-12PAX)', price: 1000 },
    { product_id: 20, category: 'Seafood', name: 'Calamares (20PAX)', price: 1780 },

    { product_id: 21, category: 'Pasta', name: 'Creamy Chicken Pesto (10-12PAX)', price: 1599 },
    { product_id: 22, category: 'Pasta', name: 'Cheesy & Beefy Baked Macaroni (10-12PAX)', price: 1399 },
    { product_id: 23, category: 'Pasta', name: 'Carbonara (10-12PAX)', price: 1499 },
    { product_id: 24, category: 'Pasta', name: 'Cheesy Spaghetti w/ Mushroom (10-12PAX)', price: 1499 },

    { product_id: 25, category: 'Bilao/Trays', name: 'Pancit Canton (10-12PAX)', price: 550 },
    { product_id: 26, category: 'Bilao/Trays', name: 'Pancit Canton (20PAX)', price: 650 },
    { product_id: 27, category: 'Bilao/Trays', name: 'Miki Bihon (10-12PAX)', price: 500 },
    { product_id: 28, category: 'Bilao/Trays', name: 'Miki Bihon (20PAX)', price: 600 },
    { product_id: 29, category: 'Bilao/Trays', name: 'Pancit Bihon Guisado (10-12PAX)', price: 495 },
    { product_id: 30, category: 'Bilao/Trays', name: 'Pancit Bihon Guisado (20PAX)', price: 595 },
    { product_id: 31, category: 'Bilao/Trays', name: 'Lumpiang Shanghai (100pcs)', price: 899 },
    { product_id: 32, category: 'Bilao/Trays', name: 'Puti/Kutsinta (100pcs)', price: 750 },
    { product_id: 33, category: 'Bilao/Trays', name: 'Sapin-sapin (10-12PAX)', price: 750 },
    { product_id: 34, category: 'Bilao/Trays', name: 'Sapin-sapin (20PAX)', price: 1450 },

    { product_id: 35, category: 'Beef', name: 'Beef Caldereta (10-12PAX)', price: 1750 },
    { product_id: 36, category: 'Beef', name: 'Beef Caldereta (20PAX)', price: 2750 },
    { product_id: 37, category: 'Beef', name: 'Beef Kare-kare (10-12PAX)', price: 1650 },
    { product_id: 38, category: 'Beef', name: 'Beef Kare-kare (20PAX)', price: 2750 },

    { product_id: 39, category: 'Chicken', name: 'Chicken Cordon Blue (10-12PAX)', price: 1300 },
    { product_id: 40, category: 'Chicken', name: 'Chicken Cordon Blue (20PAX)', price: 2300 },
    { product_id: 41, category: 'Chicken', name: 'Chicken Honey Glazed (10-12PAX)', price: 1099 },
    { product_id: 42, category: 'Chicken', name: 'Chicken Honey Glazed (20PAX)', price: 1899 },
    { product_id: 43, category: 'Chicken', name: 'Buffalo Wings (10-12PAX)', price: 1099 },
    { product_id: 44, category: 'Chicken', name: 'Buffalo Wings (20PAX)', price: 1899 },

    { product_id: 45, category: 'Vegetable', name: 'Chopsuey (10-12PAX)', price: 1300 },
    { product_id: 46, category: 'Vegetable', name: 'Chopsuey (20PAX)', price: 2300 },
    { product_id: 47, category: 'Vegetable', name: 'Ceasar Salad (10-12PAX)', price: 1100 },

    { product_id: 48, category: 'Dessert', name: 'Coffee Jelly (10-12PAX)', price: 800 },
    { product_id: 49, category: 'Dessert', name: 'Creamy Fruit Salad (10-12PAX)', price: 999 },
    { product_id: 50, category: 'Dessert', name: 'Buko Pandan (10-12PAX)', price: 899 },
    { product_id: 51, category: 'Dessert', name: 'Butchi (100pcs)', price: 999 },

    { product_id: 52, category: 'Rice', name: 'Plain Rice (10-12PAX)', price: 299 },
    { product_id: 53, category: 'Rice', name: 'Garlic Rice (10-12PAX)', price: 399 },
    { product_id: 54, category: 'Rice', name: 'Fried Rice (10-12PAX)', price: 599 },

    { product_id: 55, category: 'Sizzling/Platters', name: 'Sizzling Bagnet Kare-kare', price: 249 },
    { product_id: 56, category: 'Sizzling/Platters', name: 'Bagnet Dinakdakan', price: 239 },
    { product_id: 57, category: 'Sizzling/Platters', name: 'Sizzling Pork Sisig', price: 239 },
    { product_id: 58, category: 'Sizzling/Platters', name: 'Sizzling Tofu Con Lechon', price: 235 },
    { product_id: 59, category: 'Sizzling/Platters', name: 'Fish Fillet w/ Garlic Mayo', price: 209 },
    { product_id: 60, category: 'Sizzling/Platters', name: 'Sizzling Sweet & Sour Fish Fillet', price: 209 },
    { product_id: 61, category: 'Sizzling/Platters', name: 'Sizzling Spicy Chicken', price: 209 },
    { product_id: 62, category: 'Sizzling/Platters', name: 'Chicken Honey Glazed', price: 209 },
    { product_id: 63, category: 'Sizzling/Platters', name: 'Buffalo Wings', price: 209 },
    { product_id: 64, category: 'Sizzling/Platters', name: 'Calamares', price: 199 },
    { product_id: 65, category: 'Sizzling/Platters', name: 'Bagnet Pinakbet', price: 195 },
    { product_id: 66, category: 'Sizzling/Platters', name: 'Sizzling Mushroom Tofu', price: 190 },
    { product_id: 67, category: 'Sizzling/Platters', name: 'Sisig Tofu', price: 190 },
    { product_id: 68, category: 'Sizzling/Platters', name: 'Sizzling Buttered Corn', price: 139 },

    { product_id: 69, category: 'Sizzling w/ Rice', name: 'Sizzling Bagnet Kare-kare', price: 159 },
    { product_id: 70, category: 'Sizzling w/ Rice', name: 'Sizzling Liempo w/ Rice', price: 155 },
    { product_id: 71, category: 'Sizzling w/ Rice', name: 'Dinakdakan w/ Rice', price: 149 },
    { product_id: 72, category: 'Sizzling w/ Rice', name: 'Sizzling Pork Sisig', price: 149 },
    { product_id: 73, category: 'Sizzling w/ Rice', name: 'Sizzling Hungarian w/ Egg', price: 149 },
    { product_id: 74, category: 'Sizzling w/ Rice', name: 'Sizzling Spicy Beef', price: 145 },
    { product_id: 75, category: 'Sizzling w/ Rice', name: 'Sizzling Breaded Porkchop', price: 129 },
    { product_id: 76, category: 'Sizzling w/ Rice', name: 'Sizzling Chicken Fillet', price: 135 },
    { product_id: 77, category: 'Sizzling w/ Rice', name: 'Sizzling Spicy Squid', price: 115 },
    { product_id: 78, category: 'Sizzling w/ Rice', name: 'Sizzling Pork Teriyaki', price: 120 },
    { product_id: 79, category: 'Sizzling w/ Rice', name: 'Sizzling Longgadog w/ Egg', price: 99 },
    { product_id: 80, category: 'Sizzling w/ Rice', name: 'Sizzling Chicken Hotdog w/ Egg', price: 99 },
    { product_id: 81, category: 'Sizzling w/ Rice', name: 'Sizzling Burger Steak', price: 89 },

    { product_id: 82, category: 'Silog', name: 'Liemposilog', price: 159 },
    { product_id: 83, category: 'Silog', name: 'Bagnetsilog', price: 155 },
    { product_id: 84, category: 'Silog', name: 'Chicksilog', price: 135 },
    { product_id: 85, category: 'Silog', name: 'Porksilog', price: 135 },
    { product_id: 86, category: 'Silog', name: 'Bangusilog', price: 115 },
    { product_id: 87, category: 'Silog', name: 'Tosilog', price: 99 },
    { product_id: 88, category: 'Silog', name: 'Hamsilog', price: 79 },

    { product_id: 89, category: 'Short Order', name: 'Chefboy Pancit Canton', price: 199 },
    { product_id: 90, category: 'Short Order', name: 'Chefboy Mikibihon', price: 199 },
    { product_id: 91, category: 'Short Order', name: 'Chefboy Lomi', price: 199 },
    { product_id: 92, category: 'Short Order', name: 'Chefboy Bihon Guisado', price: 195 },
  ];

  // 🚫 Deny list (define to avoid ReferenceError and to block retired categories)
const DENY_CATEGORIES = new Set(['Bilao', 'Bilao/Trays']);
const DENY_NAME_KEYWORDS = ['Bilao'];

// 🛒 Order state
  const orderItems = [];
  let lineCounter = 1; // stable line IDs

  function createLineItem({ name, price, product_id, qty = 1 }) {
    return {
      id: lineCounter++,
      name,
      product_id,
      price: parseFloat(price),
      qty: parseInt(qty, 10),
      amount: parseFloat(price) * parseInt(qty, 10),
      voided: false
    };
  }

  function recalc(item) {
    item.amount = item.price * item.qty;
  }

  function renderOrder() {
    const tbody = document.getElementById('order-items');
    tbody.innerHTML = '';
    let total = 0;

    orderItems.forEach((item) => {
      const row = document.createElement('tr');
      row.dataset.id = item.id;

      const nameCell = item.voided ? `<s>${item.name}</s>` : item.name;

      row.innerHTML = `
        <td>${nameCell}</td>
        <td>
          <input type="number" min="1" class="qty-input" data-id="${item.id}" value="${item.qty}" ${item.voided ? 'disabled' : ''} />
        </td>
        <td>₱${item.price.toFixed(2)}</td>
        <td>₱${item.amount.toFixed(2)}</td>
        <td>
          <button class="void-toggle-btn" data-id="${item.id}">
            ${item.voided ? 'Unvoid' : 'Void'}
          </button>
        </td>
      `;
      tbody.appendChild(row);

      if (!item.voided) total += item.amount;
    });

    document.getElementById('total-amount').textContent = `₱${total.toFixed(2)}`;
    updateChange();
  }

  function updateChange() {
    const total = orderItems.reduce((sum, item) => sum + (item.voided ? 0 : item.amount), 0);
    const received = parseFloat(amountReceived) || 0;
    const change = received - total;
    document.getElementById('total-change').textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
  }

  // 🧾 Manager auth (for void toggle)
  function promptManager() {
    const password = prompt("Enter manager password:");
    return password === "123456"; // replace with your secure validation
  }

  // 🧾 Toggle void/unvoid
  document.getElementById('order-items')?.addEventListener('click', (e) => {
    if (e.target.classList.contains('void-toggle-btn')) {
      const id = parseInt(e.target.dataset.id, 10);
      const item = orderItems.find(i => i.id === id);
      if (!item) return;

      if (!promptManager()) {
        alert("Incorrect password. Action not allowed.");
        return;
      }

      item.voided = !item.voided;
      renderOrder();
    }
  });

  // ✏️ Quantity input change per line
  document.getElementById('order-items')?.addEventListener('input', (e) => {
    if (e.target.classList.contains('qty-input')) {
      const id = parseInt(e.target.dataset.id, 10);
      const item = orderItems.find(i => i.id === id);
      if (!item || item.voided) return;

      let qty = parseInt(e.target.value, 10);
      if (!Number.isFinite(qty) || qty < 1) qty = 1;

      // Check stock for new qty
      checkStock([{ name: item.name, product_id: item.product_id, qty }])
        .then(ok => {
          if (!ok) {
            e.target.value = item.qty; // revert
            return;
          }
          item.qty = qty;
          recalc(item);
          renderOrder();
        })
        .catch(() => {
          e.target.value = item.qty; // revert
        });
    }
  });

  // 📂 Category modal rendering
  function showCategory(categoryName) {
    if (DENY_CATEGORIES.has(categoryName)) {
      alert('This category is no longer available.');
      return;
    }

    const modal = document.getElementById('category-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalItems = document.getElementById('modal-items');

    const filtered = items
      .filter(item => item.category === categoryName)
      .filter(item => !DENY_CATEGORIES.has(item.category))
      .filter(item => !DENY_NAME_KEYWORDS.some(k => item.name.includes(k)));

    modalItems.innerHTML = '';
    modalTitle.textContent = categoryName;

    filtered.forEach(item => {
      const li = document.createElement('li');
      li.textContent = `${item.name} - ₱${item.price}`;
      li.dataset.productId = item.product_id;
      li.dataset.price = String(item.price);
      li.dataset.name = item.name;
      modalItems.appendChild(li);
    });

    if (filtered.length === 0) {
      const li = document.createElement('li');
      li.textContent = 'No items available.';
      modalItems.appendChild(li);
    }

    modal.classList.remove('hidden');
  }

  // ✅ Quantity modal state
  let pendingItem = null; // { name, price, product_id }
  const qtyModal = document.getElementById('qty-modal');
  const qtyInput = document.getElementById('qty-input');

  function openQtyModal(item) {
    pendingItem = item;
    if (qtyModal && qtyInput) {
      qtyInput.value = '1';
      qtyModal.classList.remove('hidden');
      qtyInput.focus();
      qtyInput.select();
    } else {
      // Fallback: prompt
      const input = prompt(`Enter quantity for ${item.name}:`, '1');
      const qty = parseInt(input, 10);
      if (Number.isFinite(qty) && qty > 0) {
        addWithStock(item, qty);
      } else {
        alert('Invalid quantity.');
      }
    }
  }

  function closeQtyModal() {
    if (qtyModal) {
      qtyModal.classList.add('hidden');
    }
    pendingItem = null;
  }

  document.getElementById('qty-confirm')?.addEventListener('click', () => {
    if (!pendingItem) return;
    const qty = parseInt(qtyInput.value, 10);
    if (!Number.isFinite(qty) || qty < 1) {
      alert('Please enter a valid quantity (1 or more).');
      return;
    }
    addWithStock(pendingItem, qty);
    closeQtyModal();
  });

  document.getElementById('qty-cancel')?.addEventListener('click', closeQtyModal);
  document.getElementById('qty-close')?.addEventListener('click', closeQtyModal);

  // Enter key to confirm in qty input
  qtyInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      document.getElementById('qty-confirm')?.click();
    }
  });

  // 🧾 Event delegation: click menu item -> open quantity modal
  document.getElementById('modal-items')?.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (!li) return;

    const name = li.dataset.name;
    const price = parseFloat(li.dataset.price);
    const product_id = parseInt(li.dataset.productId, 10);

    if (DENY_NAME_KEYWORDS.some(k => name.includes(k))) {
      alert('This item is no longer available.');
      return;
    }

    openQtyModal({ name, price, product_id });
  });

  // 🧾 Add item with stock check for requested qty
  function addWithStock(item, qty) {
  checkStock([{ name: item.name, product_id: item.product_id, qty }])
    .then(ok => {
      if (!ok) return;

      // Find existing non-voided line
      const existing = orderItems.find(i => i.name === item.name && !i.voided);

      if (existing) {
        existing.qty += qty;
        recalc(existing);
      } else {
        // Always create a new line if no active one exists
        const line = createLineItem({ name: item.name, price: item.price, product_id: item.product_id, qty });
        orderItems.push(line);
      }

      renderOrder();
    })
    .catch(err => {
      console.error("Stock check failed:", err);
      alert("Error checking stock for " + item.name);
    });
}

  // 🔍 Stock check helper
  function checkStock(orderItemsPayload) {
    return fetch('../php/check_stock.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ orderItems: orderItemsPayload })
    })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'out_of_stock') {
          showStockWindow(data.items);
          return false;
        }
        if (data.status === 'error') {
          alert(`Stock check error: ${data.message || 'Unknown'}`);
          return false;
        }
        return true;
      });
  }

  // 🧭 Category buttons
  document.querySelectorAll('.category-grid button')?.forEach(btn => {
    btn.addEventListener('click', () => {
      const category = btn.textContent.trim();
      showCategory(category);
    });
  });

  // 🧰 Modal close (category modal)
  document.querySelector('.modal-close')?.addEventListener('click', () => {
    const modal = document.getElementById('category-modal');
    const modalItems = document.getElementById('modal-items');
    modal.classList.add('hidden');
    modalItems.innerHTML = '';
  });

  // 💰 Keypad logic
  let amountReceived = '';
  document.querySelectorAll('.keypad button')?.forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.textContent;
      amountReceived = val === 'C' ? '' : amountReceived + val;

      const received = parseFloat(amountReceived) || 0;
      document.getElementById('amount-received').textContent = `₱${received.toFixed(2)}`;
      document.getElementById('amount-received-value').value = received;

      updateChange();
    });
  });

  // 💳 Payment method toggle
document.querySelectorAll('.btn-method')?.forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.btn-method').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Show GCash reference input if GCash selected
    const gcashSection = document.getElementById('gcash-section');
    if (btn.textContent.trim().toLowerCase() === 'gcash') {
      gcashSection.classList.remove('hidden');
    } else {
      gcashSection.classList.add('hidden');
    }
  });
});

  // ✅ Proceed transaction
const proceedButton = document.querySelector('.btn-proceed');
if (proceedButton) {
  proceedButton.addEventListener('click', () => {
    const total = orderItems.reduce((sum, item) => sum + (item.voided ? 0 : item.amount), 0);
    const received = parseFloat(document.getElementById('amount-received-value')?.value) || 0;
    const activeMethodBtn = document.querySelector('.btn-method.active');

    if (!activeMethodBtn) return alert('Please select a payment method.');
    if (!orderItems.some(i => !i.voided)) return alert('No active items in order.');
    if (!cashierId) return alert('No cashier identified. Please log in again.');

    const method = activeMethodBtn.textContent.trim();

    // ✅ Allow GCash without requiring reference number
    // ✅ Allow GCash to proceed even if received < total (like Cash)
    if (method.toLowerCase() === 'cash') {
      if (received < total) return alert('Insufficient payment.');
    }

    // Final stock check for all active lines
    const toCheck = orderItems
      .filter(i => !i.voided)
      .map(i => ({ name: i.name, product_id: i.product_id, qty: i.qty }));

    checkStock(toCheck)
      .then(ok => {
        if (!ok) return null;

        // Submit only non-voided lines
        const payloadItems = orderItems
          .filter(i => !i.voided)
          .map(i => ({
            name: i.name,
            product_id: i.product_id,
            qty: i.qty,
            price: i.price,
            amount: i.amount,
            voided: false
          }));

        return fetch('../php/submit_transaction.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            items: payloadItems,
            payment_method: method,
            cashier_id: cashierId
            // 👈 gcash_ref removed entirely
          })
        });
      })
      .then(async res => {
        if (!res) return; // stock error handled
        const text = await res.text();
        let data;
        try { data = JSON.parse(text); } catch {
          console.error("Invalid JSON from submit_transaction.php:", text);
          alert("Something went wrong: Invalid response from server.");
          return;
        }

        if (data.status === 'success') {
          alert('Transaction complete! ID: ' + (data.transaction_id || 'N/A'));

          // Reset order
          orderItems.length = 0;
          lineCounter = 1;
          amountReceived = '';
          renderOrder();
          document.getElementById('amount-received-value').value = '';
          document.getElementById('amount-received').textContent = '₱0.00';
          document.getElementById('total-change').textContent = '₱0.00';
        } else {
          alert('Transaction failed: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error("Proceed button error:", err);
        alert("Something went wrong: " + err.message);
      });
  });
}

  // 🔚 Logout
  document.querySelector('.logout-btn')?.addEventListener('click', () => {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = '../html/login.html';
    }
  });

function checkStock(orderItemsPayload) {
  return fetch('../php/check_stock.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ orderItems: orderItemsPayload })
  })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'stock_issue') {
        if (data.out_of_stock?.length) {
          showStockWindow(data.out_of_stock, "Out of stock");
        }
        if (data.insufficient?.length) {
          showStockWindow(data.insufficient, "Insufficient quantity of order");
        }
        return false;
      }
      if (data.status === 'error') {
        alert(`Stock check error: ${data.message || 'Unknown'}`);
        return false;
      }
      return true;
    });
}

// Adjust showStockWindow to accept type
function showStockWindow(items, type = "Out of stock") {
  const stockWindow = document.getElementById('stock-window');
  const message = document.getElementById('stock-window-message');
  message.textContent = `${type}: ${items.join(', ')}`;
  stockWindow.classList.remove('hidden');
  stockWindow.style.display = 'block';
}

  window.closeStockWindow = function () {
    const stockWindow = document.getElementById('stock-window');
    stockWindow.classList.add('hidden');
    stockWindow.style.display = 'none';
  };
});
