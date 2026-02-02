<?php
require_once __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$event = db_one($pdo, "SELECT e.*, (SELECT COALESCE(SUM(amount),0) FROM donations d WHERE d.event_id=e.id) AS raised
  FROM events e WHERE e.id=?", [$id]);

if (!$event) {
    http_response_code(404);
    echo '<div class="panel"><h2>Event not found</h2><p class="muted">The campaign you requested does not exist.</p></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$goal = (float)$event['goal_amount'];
$raised = (float)$event['raised'];
$pct = $goal > 0 ? min(100, (int)round(($raised/$goal)*100)) : 0;
$banner = $event['banner'] ?: '../assets/img/banner_default.svg';
$csrf = csrf_token();
$u = current_user();
?>

<section class="section">
  <a class="back" href="index.php#events">← Back to events</a>
  <div class="event">
    <div class="event__banner" style="background-image:url('<?= h($banner) ?>')">
      <div class="chipRow">
        <span class="chip"><?= h($event['category']) ?></span>
        <span class="chip chip--soft"><?= h($event['status']) ?></span>
      </div>
    </div>
    <div class="event__body">
      <h2><?= h($event['title']) ?></h2>
      <div class="meta">
        <span><?= h($event['location']) ?></span>
        <span><?= h($event['start_date']) ?> → <?= h($event['end_date']) ?></span>
      </div>

      <div class="bar" style="margin-top:14px">
        <div class="bar__fill" style="width: <?= $pct ?>%"></div>
      </div>
      <div class="row between" style="margin-top:8px">
        <div class="muted">Raised: <b><?= h(number_format($raised,2)) ?></b></div>
        <div class="muted">Goal: <b><?= h(number_format($goal,2)) ?></b></div>
      </div>

      <div class="panel" style="margin-top:16px">
        <h3>About this campaign</h3>
        <p class="muted" style="white-space:pre-wrap"><?= h($event['description']) ?></p>
      </div>

      <div class="twoCol" style="margin-top:16px">
        <div class="panel">
          <h3>Donate to this event</h3>
          <?php if (!$u): ?>
            <div class="alert alert--warn">Please login or create an account on the home page before donating.</div>
            <a class="btn" href="index.php#auth">Go to login</a>
          <?php elseif (($u['role'] ?? '') !== 'user'): ?>
            <div class="alert alert--warn">Admin accounts cannot donate. Please use a user account.</div>
          <?php else: ?>
            <form id="eventDonateForm" class="form">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
              <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>"/>
              <label>Amount
                <input name="amount" type="number" min="1" step="0.01" required placeholder="e.g. 10"/>
              </label>
              <label>Message (optional)
                <textarea name="message" rows="3" placeholder="A short note..."></textarea>
              </label>
              <button class="btn" type="submit">Donate</button>
              <div class="form__msg" id="eventDonateMsg"></div>
            </form>
          <?php endif; ?>
        </div>

        <div class="panel">
          <h3>Recent donations</h3>
          <?php $recent = db_all($pdo, "SELECT d.amount,d.message,d.donated_at,u.name
            FROM donations d JOIN users u ON u.id=d.user_id
            WHERE d.event_id=? ORDER BY d.donated_at DESC LIMIT 6", [$id]); ?>

          <div class="list">
            <?php foreach ($recent as $r): ?>
              <div class="list__item">
                <div class="row between">
                  <b><?= h($r['name']) ?></b>
                  <b><?= h(number_format((float)$r['amount'],2)) ?></b>
                </div>
                <div class="muted small"><?= h($r['donated_at']) ?></div>
                <?php if (!empty($r['message'])): ?>
                  <div class="muted" style="margin-top:6px"><?= h($r['message']) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
              <div class="muted">No donations yet. Be the first!</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
