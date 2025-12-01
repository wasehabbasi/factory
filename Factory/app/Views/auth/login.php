<!-- app/Views/auth/login.php -->

<?= \Config\Services::session()->start(); // ensure session started ?>



<!doctype html>

<html lang="en">

<head>

  <meta charset="utf-8" />

  <meta name="viewport" content="width=device-width,initial-scale=1" />

  <title>Inventory System — Login</title>



  <!-- Bootstrap (optional) -->

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">



  <!-- If you prefer external CSS, move inline CSS to public/assets/css/login.css -->

  <style>

    /* (copy the CSS you already provided) */

    :root{ --bg:#0b1220; --card:#0f1724; --muted:#9aa6b2; --accent:#dc3545; --glass: rgba(255,255,255,0.04); }

    *{box-sizing:border-box}

    html,body{height:100%}

    body{ margin:0; font-family:Inter, "Segoe UI", Roboto, system-ui, -apple-system, Arial; background: radial-gradient(1200px 600px at 10% 10%, rgba(34, 197, 94, .06), transparent), linear-gradient(180deg,#071226 0%, #071226 40%, #051021 100%); color:#e6eef6; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; display:flex; align-items:center; justify-content:center; padding:24px; }

    .login-wrap{ width:100%; max-width:980px; display:grid; grid-template-columns: 1fr 420px; gap:28px; align-items:center; }

    .info { padding:36px; border-radius:16px; background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border:1px solid rgba(255,255,255,0.03); box-shadow: 0 8px 30px rgba(2,6,23,0.6); }

    .info h2{margin:0 0 8px 0; font-size:28px; color:#fff}

    .info p{color:var(--muted); line-height:1.6}

    .features{display:flex; gap:12px; margin-top:18px; flex-wrap:wrap}

    .feature{ background:var(--glass); padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.02); font-size:14px; color:var(--muted); display:flex; gap:10px; align-items:center; }

    .feature i{color:var(--accent); font-size:16px}

    .card-login{ background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border-radius:14px; padding:26px; border:1px solid rgba(255,255,255,0.04); box-shadow: 0 10px 40px rgba(2,6,23,0.6); }

    .brand { display:flex; gap:12px; align-items:center; margin-bottom:12px; }

    .logo{ width:46px;height:46px;border-radius:10px; background:linear-gradient(135deg,var(--accent), #a11d2f); display:flex;align-items:center;justify-content:center; font-weight:700;color:white;font-size:18px;box-shadow:0 6px 18px rgba(0,0,0,0.3); }

    .brand h3{margin:0;font-size:18px}

    .brand .sub{color:var(--muted);font-size:13px}

    .form-control, .form-check-input, .form-select { background:transparent; border:1px solid rgba(255,255,255,0.06); color:#e6eef6; border-radius:10px; padding:12px 14px; box-shadow:none; }

    .form-control:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 4px rgba(220,53,69,0.06)}

    label{font-size:13px;color:var(--muted);margin-bottom:6px}

    .input-group .input-group-text{ background:transparent;border-left:0;border-radius:0 10px 10px 0;border:0;color:var(--muted) }

    .btn-primary{ background:linear-gradient(90deg,var(--accent), #b93c47); border:0;border-radius:10px;padding:10px 14px;font-weight:600; box-shadow: 0 8px 18px rgba(193,33,44,0.18); }

    .btn-outline{ border:1px solid rgba(255,255,255,0.04); color:var(--muted);background:transparent;border-radius:10px;padding:10px 14px; }

    .small-muted{color:var(--muted);font-size:13px;margin-top:10px}

    .show-pass-btn{ background:transparent;border:0;color:var(--muted);cursor:pointer;padding:6px 8px;border-radius:8px; }

    @media (max-width: 1000px){ .login-wrap{grid-template-columns: 1fr; padding:12px} .info{order:2} .card-login{order:1} }

  </style>



</head>

<body>



  <div class="login-wrap">

    <!-- LEFT: Info / marketing -->

    <div class="info">

      <h2>Welcome back to Inventory System</h2>

      <p>Sign in to manage vendors, warehouses, purchases, invoices, and view balance sheets — all from a tidy and mobile-friendly dashboard.</p>



      <div class="features" aria-hidden="true">

        <div class="feature"><i class="fa-solid fa-shield-halved"></i> Secure</div>

        <div class="feature"><i class="fa-solid fa-boxes-stacked"></i> Inventory</div>

        <div class="feature"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</div>

        <div class="feature"><i class="fa-solid fa-chart-line"></i> Reports</div>

      </div>



      <p class="small-muted">If you don't have an account talk to your system administrator.</p>

    </div>



    <!-- RIGHT: Login Card -->

    <div class="card-login">

      <div class="brand">

        <div class="logo"><i class="fa-solid fa-box"></i></div>

        <div>

          <h3>Inventory System</h3>

          <div class="sub">Admin Portal</div>

        </div>

      </div>



      <form action="<?= base_url('auth/process') ?>" id="loginForm" method="post" novalidate>

        <!-- CSRF token (CI4) -->

        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" />



        <div class="mb-3">

          <label for="username">Username or Email</label>

          <input id="username" name="username" type="text" class="form-control" placeholder="you@example.com" required>

        </div>



        <div class="mb-3">

          <label for="password">Password</label>

          <div class="input-group">

            <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required aria-describedby="showPassBtn">

            <button type="button" id="showPassBtn" class="input-group-text show-pass-btn" title="Show / hide password" aria-label="Show password">

              <i id="showIcon" class="fa-regular fa-eye"></i>

            </button>

          </div>

        </div>



        <div class="d-flex align-items-center justify-content-between mb-3">

          <div class="form-check">

            <input class="form-check-input" type="checkbox" value="" id="rememberMe">

            <label class="form-check-label" for="rememberMe" style="color:var(--muted);font-size:13px">Remember me</label>

          </div>

          <a href="#" style="color:var(--muted);font-size:13px;text-decoration:none">Forgot password?</a>

        </div>



        <div class="d-grid mb-2">

          <button class="btn btn-primary" type="submit">Sign In</button>

        </div>



        <div class="d-flex gap-2">

          <button type="button" class="btn btn-outline w-100" onclick="demoFill('admin@example.com','password')">

            <i class="fa-solid fa-flask"></i> Demo

          </button>

          <button type="button" class="btn btn-outline w-100" onclick="document.getElementById('username').value='';document.getElementById('password').value='';">

            <i class="fa-solid fa-eraser"></i> Clear

          </button>

        </div>



        <div id="msg" class="small-muted" role="status" style="margin-top:12px"></div>

      </form>

    </div>

  </div>



  <!-- Scripts (keeps JS inline for simplicity) -->

  <script>

    // show/hide password

    const pwd = document.getElementById('password');

    const showBtn = document.getElementById('showPassBtn');

    const showIcon = document.getElementById('showIcon');

    showBtn.addEventListener('click', ()=> {

      if (pwd.type === 'password') { pwd.type = 'text'; showIcon.className = 'fa-regular fa-eye-slash'; }

      else { pwd.type = 'password'; showIcon.className = 'fa-regular fa-eye'; }

    });



    function demoFill(u,p){ document.getElementById('username').value = u; document.getElementById('password').value = p; flashMessage('Demo credentials filled — press Sign In', 'info'); }

    function flashMessage(text, level='info'){ const el = document.getElementById('msg'); el.textContent = text; el.style.color = (level === 'error') ? '#ffb4b4' : '#a9d6ff'; setTimeout(()=> { if (el.textContent === text) el.textContent = ''; }, 4500); }



    // AJAX submit to CI controller

    document.getElementById('loginForm').addEventListener('submit', async function(e){

      e.preventDefault();

      const u = document.getElementById('username').value.trim();

      const p = document.getElementById('password').value.trim();

      if (!u || !p) { flashMessage('Please enter username and password', 'error'); return; }



      const btn = this.querySelector('button[type="submit"]');

      const old = btn.textContent;

      btn.disabled = true; btn.textContent = 'Signing in...';



      const formData = new FormData(this);



      try {

        const res = await fetch("<?= site_url('auth/process') ?>", {

          method: 'POST',

          body: formData,

          headers: { 'X-Requested-With': 'XMLHttpRequest' } // helps server detect AJAX

        });

        const data = await res.json();

        if (data.status === 'success') {

          flashMessage(data.message, 'info');

          setTimeout(()=> window.location.href = data.redirect ?? '<?= base_url('/') ?>', 700);

        } else {

          flashMessage(data.message || 'Invalid credentials', 'error');

          btn.disabled = false; btn.textContent = old;

        }

      } catch (err) {

        flashMessage('Server error!', 'error');

        btn.disabled = false; btn.textContent = old;

      }

    });

  </script>

</body>

</html>

