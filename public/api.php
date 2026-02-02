<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

// Accept JSON requests (Ajax) and multipart/form-data (file uploads)
$input = [];
if (!empty($_POST)) {
  $input = $_POST;
} else {
  $decoded = json_decode(file_get_contents('php://input') ?: '[]', true);
  if (is_array($decoded)) $input = $decoded;
}
$action = (string)($input['action'] ?? '');

// CSRF required for all actions that change state (and login/register for simplicity)
$csrf = $input['csrf'] ?? null;
$csrf_required_actions = [
  'login_user','login_admin','register_user',
  'donate_create','donate_update','donate_delete',
  'event_save','event_delete'
];
if (in_array($action, $csrf_required_actions, true) && !csrf_verify(is_string($csrf)?$csrf:null)) {
  json_out(['ok'=>false,'message'=>'Invalid token. Please refresh the page.'], 400);
}

switch ($action) {
  case 'stats':
    $raised = db_one($pdo, "SELECT COALESCE(SUM(amount),0) AS v FROM donations")['v'] ?? 0;
    $donations = db_one($pdo, "SELECT COUNT(*) AS v FROM donations")['v'] ?? 0;
    $events = db_one($pdo, "SELECT COUNT(*) AS v FROM events")['v'] ?? 0;
    json_out(['ok'=>true,'raised'=>(float)$raised,'donations'=>(int)$donations,'events'=>(int)$events]);

  case 'events_suggest':
    $q = trim((string)($input['q'] ?? ''));
    $q2 = '%' . $q . '%';
    $rows = db_all($pdo, "SELECT id,title,category,location,status FROM events
      WHERE title LIKE ? OR category LIKE ? OR location LIKE ? OR status LIKE ?
      ORDER BY status='ongoing' DESC, start_date DESC LIMIT 8", [$q2,$q2,$q2,$q2]);
    json_out(['ok'=>true,'items'=>$rows]);

  case 'check_email':
    $email = strtolower(trim((string)($input['email'] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      json_out(['ok'=>true,'exists'=>false,'valid'=>false]);
    }
    $exists = (bool)db_one($pdo, "SELECT id FROM users WHERE email=? LIMIT 1", [$email]);
    json_out(['ok'=>true,'exists'=>$exists,'valid'=>true]);

  case 'login_user':
  case 'login_admin': {
    $role = ($action === 'login_admin') ? 'admin' : 'user';
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
      json_out(['ok'=>false,'message'=>'Please enter valid credentials.'], 400);
    }
    $u = db_one($pdo, "SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1", [$email]);
    if (!$u || ($u['role'] ?? '') !== $role || !password_verify_str($password, (string)$u['password_hash'])) {
      json_out(['ok'=>false,'message'=>'Invalid email/password for this account type.'], 401);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>(int)$u['id'], 'name'=>(string)$u['name'], 'email'=>(string)$u['email'], 'role'=>(string)$u['role']];
    json_out(['ok'=>true,'redirect'=> $role==='admin' ? 'admin.php' : 'dashboard.php']);
  }

  case 'register_user': {
    $name = trim((string)($input['name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $password = (string)($input['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
      json_out(['ok'=>false,'message'=>'Please fill all fields (password min 6 characters).'], 400);
    }
    $exists = db_one($pdo, "SELECT id FROM users WHERE email=? LIMIT 1", [$email]);
    if ($exists) {
      json_out(['ok'=>false,'message'=>'Email already exists. Please login instead.'], 409);
    }

    db_exec($pdo, "INSERT INTO users (name,email,password_hash,role,created_at) VALUES (?,?,?,?,NOW())",
      [$name,$email,password_hash_str($password),'user']);

    $id = (int)$pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'user'];
    json_out(['ok'=>true,'redirect'=>'dashboard.php']);
  }

  case 'donate_create': {
    require_login('user');
    $uid = (int)current_user()['id'];
    $event_id = (int)($input['event_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    $message = trim((string)($input['message'] ?? ''));

    if ($event_id <= 0 || $amount <= 0) {
      json_out(['ok'=>false,'message'=>'Please provide a valid event and amount.'], 400);
    }

    $event = db_one($pdo, "SELECT id,status FROM events WHERE id=?", [$event_id]);
    if (!$event) json_out(['ok'=>false,'message'=>'Event not found.'], 404);

    db_exec($pdo, "INSERT INTO donations (user_id,event_id,amount,message,donated_at) VALUES (?,?,?,?,NOW())",
      [$uid,$event_id,$amount,$message]);
    json_out(['ok'=>true,'message'=>'Donation added successfully.']);
  }

  case 'donate_list_user': {
    require_login('user');
    $uid = (int)current_user()['id'];
    $q = trim((string)($input['q'] ?? ''));
    $q2 = '%' . $q . '%';
    $rows = db_all($pdo, "SELECT d.id,d.amount,d.message,d.donated_at,e.title AS event_title,e.id AS event_id
      FROM donations d JOIN events e ON e.id=d.event_id
      WHERE d.user_id=? AND (e.title LIKE ? OR d.message LIKE ?)
      ORDER BY d.donated_at DESC", [$uid,$q2,$q2]);
    json_out(['ok'=>true,'items'=>$rows]);
  }

  case 'donate_update': {
    require_login('user');
    $uid = (int)current_user()['id'];
    $id = (int)($input['id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    $message = trim((string)($input['message'] ?? ''));

    if ($id <= 0 || $amount <= 0) {
      json_out(['ok'=>false,'message'=>'Invalid donation details.'], 400);
    }

    $own = db_one($pdo, "SELECT id FROM donations WHERE id=? AND user_id=?", [$id,$uid]);
    if (!$own) json_out(['ok'=>false,'message'=>'Donation not found.'], 404);

    db_exec($pdo, "UPDATE donations SET amount=?, message=? WHERE id=? AND user_id=?", [$amount,$message,$id,$uid]);
    json_out(['ok'=>true,'message'=>'Donation updated.']);
  }

  case 'donate_delete': {
    require_login('user');
    $uid = (int)current_user()['id'];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'message'=>'Invalid donation.'], 400);
    db_exec($pdo, "DELETE FROM donations WHERE id=? AND user_id=?", [$id,$uid]);
    json_out(['ok'=>true,'message'=>'Donation deleted.']);
  }

  case 'event_list': {
    require_login('admin');
    $q = trim((string)($input['q'] ?? ''));
    $q2 = '%' . $q . '%';
    $rows = db_all($pdo, "SELECT id,title,category,location,start_date,end_date,goal_amount,status,banner,description,
      (SELECT COALESCE(SUM(amount),0) FROM donations d WHERE d.event_id=events.id) AS raised
      FROM events
      WHERE title LIKE ? OR category LIKE ? OR location LIKE ? OR status LIKE ?
      ORDER BY created_at DESC", [$q2,$q2,$q2,$q2]);
    json_out(['ok'=>true,'items'=>$rows]);
  }

  case 'event_save': {
    require_login('admin');
    $id = (int)($input['id'] ?? 0);
    $title = trim((string)($input['title'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $location = trim((string)($input['location'] ?? ''));
    $start = (string)($input['start_date'] ?? '');
    $end = (string)($input['end_date'] ?? '');
    $goal = (float)($input['goal_amount'] ?? 0);
    $status = (string)($input['status'] ?? 'ongoing');
    $banner_existing = trim((string)($input['banner_existing'] ?? ''));
    $banner = $banner_existing;

    // Optional banner upload (from device)
    if (!empty($_FILES['banner_file']) && is_array($_FILES['banner_file']) && ($_FILES['banner_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
      $tmp = (string)$_FILES['banner_file']['tmp_name'];
      $orig = (string)($_FILES['banner_file']['name'] ?? 'banner');
      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      $allowed = ['jpg','jpeg','png','webp','gif'];
      if (!in_array($ext, $allowed, true)) {
        json_out(['ok'=>false,'message'=>'Banner must be an image (jpg, png, webp, gif).'], 400);
      }
      $uploadDir = __DIR__ . '/../assests/uploads';
      if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

      $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($orig, PATHINFO_FILENAME)) ?: 'banner';
      $fname = $safe . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $dest = $uploadDir . '/' . $fname;

      if (!move_uploaded_file($tmp, $dest)) {
        json_out(['ok'=>false,'message'=>'Failed to upload banner.'], 500);
      }
      // Store path usable from /public pages
      $banner = '../assests/uploads/' . $fname;
    }
    $description = trim((string)($input['description'] ?? ''));

    if ($title==='' || $category==='' || $location==='' || $start==='' || $end==='' || $description==='') {
      json_out(['ok'=>false,'message'=>'Please fill all required fields.'], 400);
    }
    if (!in_array($status, ['ongoing','completed','planned'], true)) {
      json_out(['ok'=>false,'message'=>'Invalid status.'], 400);
    }

    if ($id > 0) {
      db_exec($pdo, "UPDATE events SET title=?,category=?,location=?,start_date=?,end_date=?,goal_amount=?,status=?,banner=?,description=? WHERE id=?",
        [$title,$category,$location,$start,$end,$goal,$status,$banner,$description,$id]);
      json_out(['ok'=>true,'message'=>'Event updated.']);
    }

    db_exec($pdo, "INSERT INTO events (title,category,location,start_date,end_date,goal_amount,status,banner,description,created_at)
      VALUES (?,?,?,?,?,?,?,?,?,NOW())", [$title,$category,$location,$start,$end,$goal,$status,$banner,$description]);
    json_out(['ok'=>true,'message'=>'Event created.']);
  }

  case 'event_delete': {
    require_login('admin');
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) json_out(['ok'=>false,'message'=>'Invalid event.'], 400);
    db_exec($pdo, "DELETE FROM events WHERE id=?", [$id]);
    json_out(['ok'=>true,'message'=>'Event deleted.']);
  }

  case 'donations_admin': {
    require_login('admin');
    $q = trim((string)($input['q'] ?? ''));
    $q2 = '%' . $q . '%';
    $rows = db_all($pdo, "SELECT d.id,d.amount,d.message,d.donated_at,u.name u_name,u.email u_email,e.title e_title
      FROM donations d
      JOIN users u ON u.id=d.user_id
      JOIN events e ON e.id=d.event_id
      WHERE u.name LIKE ? OR u.email LIKE ? OR e.title LIKE ? OR d.message LIKE ?
      ORDER BY d.donated_at DESC LIMIT 200", [$q2,$q2,$q2,$q2]);
    json_out(['ok'=>true,'items'=>$rows]);
  }

  default:
    json_out(['ok'=>false,'message'=>'Unknown action.'], 400);
}
