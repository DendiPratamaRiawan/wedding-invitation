(function () {
  'use strict';
  var form = document.getElementById('login-form');
  var err = document.getElementById('login-error');
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = form.querySelector('button');
    if (!form.username.value.trim() || !form.password.value) {
      err.textContent = 'Isi username dan password.';
      err.hidden = false;
      return;
    }
    btn.disabled = true;
    err.hidden = true;
    fetch('../api/auth.php?action=login', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: form.username.value.trim(), password: form.password.value })
    }).then(function (res) {
      return res.json().then(function (json) {
        if (!res.ok) throw new Error(json.error || 'Gagal masuk.');
        location.href = 'index.php';
      });
    }).catch(function (e2) {
      err.textContent = e2.message;
      err.hidden = false;
      form.password.value = '';
      btn.disabled = false;
    });
  });
})();
