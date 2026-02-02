<?php
require_once __DIR__ . '/../includes/header.php';
require_login('admin');

$csrf = csrf_token();

$events = db_all($pdo, "SELECT id,title,category,location,start_date,end_date,goal_amount,status,banner,description,
  (SELECT COALESCE(SUM(amount),0) FROM donations d WHERE d.event_id=events.id) AS raised
  FROM events ORDER BY created_at DESC");

$donations = db_all($pdo, "SELECT d.id,d.amount,d.message,d.donated_at,u.name u_name,u.email u_email,e.title e_title
  FROM donations d
  JOIN users u ON u.id=d.user_id
  JOIN events e ON e.id=d.event_id
  ORDER BY d.donated_at DESC
  LIMIT 50");

$title = 'Admin Dashboard';
?>

<section class="section">
  <div class="section__head">
    <div>
      <h2>Admin Dashboard</h2>
      <div class="muted">Manage events (Create, Read, Update, Delete) and review collections and donors.</div>
    </div>
  </div>

  <div class="twoCol">
    <div class="panel">
      <h3>Create / edit event</h3>
      <form id="eventForm" class="form" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>"/>
        <input type="hidden" name="id" id="eventId"/>

        <label>Title
          <input name="title" id="eventTitle" required placeholder="e.g. Food Relief for Families"/>
        </label>
        <div class="row">
          <label class="grow">Category
            <input name="category" id="eventCategory" required placeholder="Education / Health / Food"/>
          </label>
          <label class="grow">Location
            <input name="location" id="eventLocation" required placeholder="City, Country"/>
          </label>
        </div>
        <div class="row">
          <label class="grow">Start date
            <input name="start_date" id="eventStart" type="date" required/>
          </label>
          <label class="grow">End date
            <input name="end_date" id="eventEnd" type="date" required/>
          </label>
        </div>
        <div class="row">
          <label class="grow">Goal amount
            <input name="goal_amount" id="eventGoal" type="number" min="0" step="0.01" required/>
          </label>
          <label class="grow">Status
            <select name="status" id="eventStatus" required>
              <option value="ongoing">ongoing</option>
              <option value="completed">completed</option>
              <option value="planned">planned</option>
            </select>
          </label>
        </div>
        <label>Banner image (optional)
          <input type="file" name="banner_file" id="eventBannerFile" accept="image/*"/>
          <input type="hidden" name="banner_existing" id="eventBannerExisting" value=""/>
          <div class="hint">Upload from your device (PNG/JPG/WebP). If you leave this empty while editing, the previous banner stays.</div>
          <div id="bannerPreview" class="bannerPreview" style="display:none"></div>
        </label>
        <label>Description
          <textarea name="description" id="eventDesc" rows="4" required placeholder="Explain the purpose, how funds are used, and updates..."></textarea>
        </label>

        <div class="row between">
          <button class="btn" type="submit">Save event</button>
          <button class="btn btn--ghost" type="button" id="eventFormReset">Clear</button>
        </div>
        <div class="form__msg" id="eventMsg"></div>
      </form>
    </div>

    <div class="panel">
      <h3>Events & budget collection</h3>
      <div class="searchRow">
        <input id="eventFilter" type="text" placeholder="Search events by title, category, location, status..."/>
        <button class="btn btn--small btn--ghost" type="button" id="refreshEvents">Refresh</button>
      </div>

      <div class="tableWrap">
        <table class="table" id="eventsTable">
          <thead>
            <tr>
              <th>Event</th>
              <th>Goal</th>
              <th>Raised</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <tr data-id="<?= (int)$e['id'] ?>"
                  data-title="<?= h($e['title']) ?>"
                  data-category="<?= h($e['category']) ?>"
                  data-location="<?= h($e['location']) ?>"
                  data-start="<?= h($e['start_date']) ?>"
                  data-end="<?= h($e['end_date']) ?>"
                  data-goal="<?= h((string)$e['goal_amount']) ?>"
                  data-status="<?= h($e['status']) ?>"
                  data-banner="<?= h($e['banner'] ?? '') ?>"
                  data-desc="<?= h($e['description'] ?? '') ?>">
                <td>
                  <b><?= h($e['title']) ?></b>
                  <div class="muted small"><?= h($e['category']) ?> • <?= h($e['location']) ?></div>
                </td>
                <td><?= h(number_format((float)$e['goal_amount'],2)) ?></td>
                <td><b><?= h(number_format((float)$e['raised'],2)) ?></b></td>
                <td><span class="chip chip--soft"><?= h($e['status']) ?></span></td>
                <td class="actions">
                  <button class="btn btn--small" data-action="editEvent">Edit</button>
                  <button class="btn btn--small btn--danger" data-action="deleteEvent">Delete</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="form__msg" id="eventsMsg"></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="section__head">
    <div>
      <h2>Latest donations</h2>
      <div class="muted">Search by donor name/email or event title. </div>
    </div>
  </div>

  <div class="panel">
    <div class="searchRow">
      <input id="adminDonationFilter" type="text" placeholder="Search donors/events/messages..."/>
      <button class="btn btn--small btn--ghost" type="button" id="refreshAdminDonations">Refresh</button>
    </div>

    <div class="tableWrap">
      <table class="table" id="adminDonationsTable">
        <thead>
          <tr>
            <th>Donor</th>
            <th>Event</th>
            <th>Amount</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($donations as $d): ?>
            <tr>
              <td><b><?= h($d['u_name']) ?></b><div class="muted small"><?= h($d['u_email']) ?></div></td>
              <td><?= h($d['e_title']) ?><div class="muted small"><?= h($d['message'] ?? '') ?></div></td>
              <td><b><?= h(number_format((float)$d['amount'],2)) ?></b></td>
              <td><?= h($d['donated_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="form__msg" id="adminDonationsMsg"></div>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
