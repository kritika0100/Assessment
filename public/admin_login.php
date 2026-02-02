<?php
$title = 'Donora | Admin Login';
require_once __DIR__ . '/../includes/header.php';
$csrf = csrf_token();
?>
<section class="section">
  <div class="section__head">
    <div>
      <h2>Admin login</h2>
      <div class="muted">Only authorised administrators can manage campaigns.</div>
    </div>
  </div>

  <div class="authCenter">
    <div class="authCard card">
      <div class="authTabs" role="tablist" aria-label="Admin login">
        <button class="authTab is-active" type="button" aria-selected="true">Login</button>
      </div>

      <div>
        <h3 class="authTitle">Welcome back</h3>
        <p class="muted small" style="margin-top:-6px;">Sign in to manage events and review donations.</p>

        <form id="adminLoginForm" class="form">
          <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
          <div>
            <label>Admin email</label>
            <input name="email" type="email" placeholder="admin@charity.com" required/>
          </div>
          <div>
            <label>Password</label>
            <input name="password" type="password" placeholder="••••••••" required/>
          </div>

          <button class="btn btn--primary btn--full" type="submit">Login as Admin</button>
          <div id="adminLoginMsg" class="formMsg"></div>
        </form>

        <div class="muted small" style="margin-top:10px;">
          <a href="auth.php">Back to continue</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
