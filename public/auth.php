<?php
$title = 'BlueHope | Continue';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="section authPage">
  <div class="section__head">
    <div>
      <h2>Continue</h2>
      <div class="muted">Choose how you want to continue.</div>
    </div>
  </div>

  <div class="choiceGrid">
    <a class="choiceCard" href="user_auth.php">
      <div class="choiceTitle">Continue as User</div>
      <div class="muted">Create an account or login to donate and track your contributions.</div>
    </a>

    <a class="choiceCard choiceCard--admin" href="admin_login.php">
      <div class="choiceTitle">Continue as Admin</div>
      <div class="muted">Login to manage campaigns and review collections.</div>
    </a>
  </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
