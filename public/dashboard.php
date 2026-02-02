<?php
require_once __DIR__ . '/../includes/header.php';
require_login('user');

$u = current_user();
$uid = (int)$u['id'];

$donations = db_all($pdo, "SELECT d.id,d.amount,d.message,d.donated_at,e.title AS event_title,e.id AS event_id
  FROM donations d
  JOIN events e ON e.id=d.event_id
  WHERE d.user_id=?
  ORDER BY d.donated_at DESC", [$uid]);

$events = db_all($pdo, "SELECT id,title,status FROM events ORDER BY status='ongoing' DESC, start_date DESC LIMIT 50");
$csrf = csrf_token();
$title = 'My Dashboard';
?>

<section class="section">
  <div class="section__head">
    <div>
      <h2>Hello, <?= h($u['name']) ?></h2>
      <div class="muted">Manage your donations (Create, Update, Delete) and search your history.</div>
    </div>
    <a class="btn btn--ghost btn--small" href="index.php#events">Explore events</a>
  </div>

  <div class="twoCol">
    <div class="panel">
      <h3>Make a donation</h3>
      <form id="donateForm" class="form">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
        <label>Choose event
          <select name="event_id" required>
            <?php foreach ($events as $e): ?>
              <option value="<?= (int)$e['id'] ?>"><?= h($e['title']) ?> (<?= h($e['status']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Amount
          <input name="amount" type="number" min="1" step="0.01" required placeholder="e.g. 25"/>
        </label>
        <label>Message (optional)
          <textarea name="message" rows="3" placeholder="A short note..."></textarea>
        </label>
        <button class="btn" type="submit">Donate</button>
        <div class="form__msg" id="donateMsg"></div>
      </form>
    </div>

    <div class="panel">
      <h3>Your donation history</h3>
      <div class="searchRow">
        <input id="donationFilter" type="text" placeholder="Search by event or message..."/>
        <button class="btn btn--small btn--ghost" id="refreshDonations" type="button">Refresh</button>
      </div>
      <div class="tableWrap">
        <table class="table" id="donationsTable">
          <thead>
            <tr>
              <th>Event</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($donations as $d): ?>
              <tr data-id="<?= (int)$d['id'] ?>" data-amount="<?= h((string)$d['amount']) ?>" data-message="<?= h((string)($d['message'] ?? '')) ?>">
                <td><a href="event.php?id=<?= (int)$d['event_id'] ?>"><?= h($d['event_title']) ?></a><div class="muted small"><?= h($d['message'] ?? '') ?></div></td>
                <td><b><?= h(number_format((float)$d['amount'], 2)) ?></b></td>
                <td><?= h($d['donated_at']) ?></td>
                <td class="actions">
                  <button class="btn btn--small" data-action="editDonation">Edit</button>
                  <button class="btn btn--small btn--danger" data-action="deleteDonation">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="form__msg" id="donationsMsg"></div>
    </div>
  </div>
</section>

<!-- Modal -->
<div class="modal" id="editModal" aria-hidden="true">
  <div class="modal__backdrop" data-close="1"></div>
  <div class="modal__card">
    <div class="modal__head">
      <h3>Edit donation</h3>
      <button class="x" data-close="1">×</button>
    </div>
    <form id="editDonationForm" class="form">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
      <input type="hidden" name="id" id="editId"/>
      <label>Amount
        <input name="amount" id="editAmount" type="number" min="1" step="0.01" required/>
      </label>
      <label>Message
        <textarea name="message" id="editMessage" rows="3"></textarea>
      </label>
      <button class="btn" type="submit">Save changes</button>
      <div class="form__msg" id="editMsg"></div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
