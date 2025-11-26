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

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("user-form");
  const messageBox = document.getElementById("settings-message");
  const userTableBody = document.querySelector("#user-table tbody");

  // Load existing users
  async function loadUsers() {
    try {
      const response = await fetch("/php/get-users.php");
      const users = await response.json();

      userTableBody.innerHTML = "";
      users.forEach(user => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${user.user_id}</td>
          <td>${user.email}</td>
          <td>${user.role}</td>
          <td>${user.created_at}</td>
          <td><button class="delete-btn" data-id="${user.user_id}">Delete</button></td>
        `;
        userTableBody.appendChild(row);
      });
    } catch (err) {
      console.error("Failed to load users:", err);
      messageBox.textContent = "❌ Error loading users";
      messageBox.className = "error";
    }
  }

  loadUsers();

  // Add user
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(form);
    try {
      const response = await fetch(form.action, {
        method: "POST",
        body: formData
      });
      const result = await response.json();
      if (result.status === "success") {
        messageBox.textContent = "✅ User added successfully";
        messageBox.className = "success";
        form.reset();
        loadUsers();
      } else {
        // Show backend error message (duplicate email, invalid role, etc.)
        messageBox.textContent = "❌ " + (result.message || "Failed to add user");
        messageBox.className = "error";
      }
    } catch (err) {
      console.error("Add user error:", err);
      messageBox.textContent = "❌ Server error";
      messageBox.className = "error";
    }
  });

  // Delete user
  userTableBody.addEventListener("click", async (e) => {
    if (e.target.classList.contains("delete-btn")) {
      const id = e.target.dataset.id;
      try {
        const response = await fetch("/php/delete-user.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id=" + encodeURIComponent(id)
        });
        const result = await response.json();
        if (result.status === "success") {
          loadUsers();
        } else {
          messageBox.textContent = "❌ " + (result.message || "Failed to delete user");
          messageBox.className = "error";
        }
      } catch (err) {
        console.error("Delete user error:", err);
        messageBox.textContent = "❌ Server error";
        messageBox.className = "error";
      }
    }
  });
});
