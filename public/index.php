<?php
$title = 'BlueHope | Home';
require_once __DIR__ . '/../includes/header.php';

// Featured + latest events
$events = db_all($pdo, "SELECT id,title,category,location,start_date,end_date,goal_amount,banner,status,
  (SELECT COALESCE(SUM(amount),0) FROM donations d WHERE d.event_id=events.id) AS raised
  FROM events ORDER BY status='ongoing' DESC, start_date DESC LIMIT 6");

$csrf = csrf_token();
?>

<section id="home" class="hero">
  <div class="hero__left">
    <h1>Give with Heart.</h1>
    <p class="lead">
      Donora is a simple charity platform where people can discover ongoing campaigns,
      donate, track impact, and where admins can manage events and view collections.
    </p>

    <div class="hero__cta">
      <a class="btn" href="#events">Explore Events</a>
      <a class="btn btn--ghost" href="auth.php">Login / Sign up</a>
    </div>

    <div class="stats" id="impact">
      <div class="stat">
        <div class="stat__num" id="statRaised">...</div>
        <div class="stat__label">Total Raised</div>
      </div>
      <div class="stat">
        <div class="stat__num" id="statDonations">...</div>
        <div class="stat__label">Donations</div>
      </div>
      <div class="stat">
        <div class="stat__num" id="statEvents">...</div>
        <div class="stat__label">Campaigns</div>
      </div>
    </div>
  </div>

  <div class="hero__right">
    <div class="glass">
      <div class="glass__title">Ongoing Campaigns Right Now</div>
      

      <div class="search">
        <input id="eventSearch" type="text"
          placeholder="Search: Education, Health, Nepal, food..."
          autocomplete="off"/>
        <div id="eventSuggest" class="suggest"></div>
      </div>

      <div class="mini" id="miniEvent">Type to search campaigns.</div>
    </div>
  </div>
</section>


 <!--  ABOUT SECTION  -->


<section class="about-section" id="about">
  <div class="wrap">
    <div class="about-card">
      <h2>About Us</h2>
      <p>
        <strong>We are dedicated to bridging the gap between compassion and action.</strong>
        Our mission is to provide essential resources and sustainable support to communities in need.
        With a commitment to <strong>100% transparency</strong>, we ensure that every donation creates
        a tangible, lasting impact.
      </p>

      <p style="margin-top:12px;">
        Together, we can build a future where <strong>no one is left behind</strong>.
      </p>
    </div>
  </div>
</section>



<section id="events" class="section">
  <div class="section__head">
    <div>
      <h2>Ongoing charity events</h2>
      <div class="muted">Banners, goals, and real-time collection totals.</div>
    </div>
    <a class="btn btn--small btn--ghost" href="auth.php">Donate now</a>
  </div>

  <div class="grid">
    <?php foreach ($events as $e):
      $goal = (float)$e['goal_amount'];
      $raised = (float)$e['raised'];
      $pct = $goal > 0 ? min(100, (int)round(($raised/$goal)*100)) : 0;
      $banner = $e['banner'] ?: '../assets/img/banner_default.svg';


    ?>
      <article class="card" data-id="<?= (int)$e['id'] ?>">
        <div class="card__banner" style="background-image:url('<?= h($banner) ?>')">
          <div class="chip"><?= h($e['category']) ?></div>
          <div class="chip chip--soft"><?= h($e['status']) ?></div>
        </div>

        <div class="card__body">
          <h3><?= h($e['title']) ?></h3>

          <div class="meta">
            <span><?= h($e['location']) ?></span>
            <span><?= h($e['start_date']) ?> → <?= h($e['end_date']) ?></span>
          </div>

          <div class="bar">
            <div class="bar__fill" style="width: <?= $pct ?>%"></div>
          </div>

          <div class="row between">
            <div class="muted">Raised: <b><?= h(number_format($raised,2)) ?></b></div>
            <div class="muted">Goal: <b><?= h(number_format($goal,2)) ?></b></div>
          </div>

          <div class="row between" style="margin-top:12px">
            <a class="btn btn--small" href="event.php?id=<?= (int)$e['id'] ?>">View & Donate</a>
            <div class="pct"><?= $pct ?>%</div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
