document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  const email = formData.get('email');
  const password = formData.get('password');

  const errorMsg = document.getElementById('errorMsg');

  // ✅ Check minimum length
  if (password.length < 8) {
    errorMsg.textContent = "Password must be at least 8 characters.";
    errorMsg.classList.remove('hide');
    return; // stop here, don't send request
  }

  const data = { email, password };
  
  fetch('../../php/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
    .then(res => res.json())
    .then(response => {
      const errorMsg = document.getElementById('errorMsg');

      if (response.status === 'success') {
        window.location.href = response.redirect;
      } else {
        if (errorMsg) {
          errorMsg.textContent = response.message;
          errorMsg.classList.remove('hide'); // show it

          setTimeout(() => {
            errorMsg.classList.add('hide'); // fade out
            setTimeout(() => {
              errorMsg.textContent = ''; // clear message
            }, 500); // match transition duration
          }, 5000);
        }
      }
    })
    .catch(error => {
      const errorMsg = document.getElementById('errorMsg');
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
