document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  const email = formData.get('email');
  const password = formData.get('password');

  const errorMsg = document.getElementById('errorMsg');

  // ✅ Check minimum length
  if (password.length < 8) {
    if (errorMsg) {
      errorMsg.textContent = "Password must be at least 8 characters.";
      errorMsg.classList.remove('hide');
    }
    return; // stop here, don't send request
  }

  const data = { email, password };

  // ✅ Use absolute path to PHP
  fetch('/php/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
    .then(res => res.json())
    .then(response => {
  if (response.status === 'success' && response.user) {
    // ✅ Store cashier info in localStorage
    localStorage.setItem('cashier_id', response.user.user_id);
    localStorage.setItem('cashier_email', response.user.email);
    localStorage.setItem('cashier_username', response.user.username); // 👈 NEW

    // Redirect to POS
    window.location.href = response.redirect;
  } else {
    if (errorMsg) {
      errorMsg.textContent = response.message || 'Invalid credentials.';
      errorMsg.classList.remove('hide');

      setTimeout(() => {
        errorMsg.classList.add('hide');
        setTimeout(() => {
          errorMsg.textContent = '';
        }, 500);
      }, 5000);
    }
  }
})

    .catch(error => {
      if (errorMsg) {
        errorMsg.textContent = 'Server error. Please try again.';
        errorMsg.classList.remove('hide');

        setTimeout(() => {
          errorMsg.classList.add('hide');
          setTimeout(() => {
            errorMsg.textContent = '';
          }, 500);
        }, 5000);
      }
      console.error('Login error:', error);
    });
});
