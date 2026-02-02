<?php
$title = 'BlueHope | User Access';
require_once __DIR__ . '/../includes/header.php';
$csrf = csrf_token();
?>
<section class="section">
  <div class="section__head">
    <div>
      <h2>User access</h2>
      <div class="muted">Create an account or login to your existing account.</div>
    </div>
  </div>

  <div class="authCenter">
    <div class="authCard card">
      <div class="authTabs" role="tablist" aria-label="User access tabs">
        <button class="authTab is-active" type="button" data-tab="login" aria-selected="true">Login</button>
        <button class="authTab" type="button" data-tab="signup" aria-selected="false">Signup</button>
      </div>

      <div class="authPane" data-pane="login">
        <h3 class="authTitle">Welcome back</h3>
        <p class="muted small" style="margin-top:-6px;">Login to view your dashboard and donate to campaigns.</p>

        <form id="userLoginForm" class="form">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
          <div>
            <label>Email</label>
            <input name="email" type="email" placeholder="you@example.com" required/>
          </div>
          <div>
            <label>Password</label>
            <input name="password" type="password" placeholder="••••••••" required/>
          </div>

          <button class="btn btn--primary btn--full" type="submit">Login</button>
          <div id="userLoginMsg" class="formMsg"></div>
        </form>

        <div class="muted small" style="margin-top:10px;">
          Not an admin? <a href="auth.php">Back to continue</a>
        </div>
      </div>

      <div class="authPane is-hidden" data-pane="signup">
        <h3 class="authTitle">Create account</h3>
        <p class="muted small" style="margin-top:-6px;">It takes less than a minute.</p>

        <form id="registerForm" class="form">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
          <div>
            <label>Full name</label>
            <input name="name" type="text" placeholder="Your name" required/>
          </div>
          <div>
            <label>Email</label>
            <input id="regEmail" name="email" type="email" placeholder="you@example.com" required/>
            <div id="emailHint" class="muted small"></div>
          </div>
          <div>
            <label>Password</label>
            <input name="password" type="password" minlength="6" placeholder="Minimum 6 characters" required/>
          </div>

          <button class="btn btn--primary btn--full" type="submit">Sign up</button>
          <div id="registerMsg" class="formMsg"></div>
        </form>
      </div>
    </div>
  </div>
</section>

<script>

  // lightweight tabs 
  (function(){
    const tabs = document.querySelectorAll('.authTab');
    const panes = document.querySelectorAll('.authPane');
    function show(name){
      panes.forEach(p => {
        const on = p.dataset.pane === name;
        p.classList.toggle('is-hidden', !on);
      });
      tabs.forEach(t => {
        const on = t.dataset.tab === name;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
      });
    }
    tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.tab)));
  })();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
