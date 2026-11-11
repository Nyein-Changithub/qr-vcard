<?php
$s = $staff; $c = $company;
$name = trim(($s['prefix'] ?? '') . ' ' . $s['full_name']);
$brand = hex_color((string) $c['brand_color']);
$addr = ($s['address'] ?? '') ?: ($c['address'] ?? '');
$image = $s['photo_url'] ?: ($c['logo_url'] ?? '');
$firstPhone = $phones[0]['number'] ?? null;
$viber = $s['viber'] ? preg_replace('/[^0-9+]/', '', $s['viber']) : null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($name) ?> · <?= e($c['name']) ?></title>
<meta name="description" content="<?= e(trim(($s['role'] ?? '') . ' · ' . $c['name'], ' ·')) ?>">
<meta name="theme-color" content="<?= e($brand) ?>">
<meta property="og:title" content="<?= e($name) ?>">
<meta property="og:description" content="<?= e(trim(($s['role'] ?? '') . ' · ' . $c['name'], ' ·')) ?>">
<?php if ($image): ?><meta property="og:image" content="<?= e($image) ?>"><?php endif; ?>
<?php if (!empty($c['logo_url'])): ?><link rel="icon" href="<?= e($c['logo_url']) ?>"><?php endif; ?>
<link rel="stylesheet" href="/assets/app.css?v=<?= ASSET_V ?>">
</head>
<body class="vcard-page" style="--brand: <?= e($brand) ?>">
<main class="vcard">
  <header class="vc-head">
    <?php if (!empty($c['logo_url'])): ?><img class="vc-logo" src="<?= e($c['logo_url']) ?>" alt="<?= e($c['name']) ?>"><?php endif; ?>
  </header>

  <section class="vc-body">
    <div class="vc-photo">
      <?php if ($s['photo_url']): ?><img src="<?= e($s['photo_url']) ?>" alt="<?= e($name) ?>">
      <?php else: ?><span><?= e(initials($s['full_name'])) ?></span><?php endif; ?>
    </div>
    <h1><?= e($name) ?></h1>
    <?php if ($s['role'] || $s['department']): ?>
      <p class="vc-role"><?= e(implode(' · ', array_filter([$s['role'], $s['department']]))) ?></p>
    <?php endif; ?>
    <p class="vc-company"><?= e($c['name']) ?></p>
    <?php if (!empty($c['tagline'])): ?><p class="vc-tagline"><?= e($c['tagline']) ?></p><?php endif; ?>

    <nav class="vc-quick">
      <?php if ($firstPhone): ?><a href="<?= e(tel_href($firstPhone)) ?>"><span>☎</span>Call</a><?php endif; ?>
      <?php if ($s['email']): ?><a href="mailto:<?= e($s['email']) ?>"><span>✉</span>Email</a><?php endif; ?>
      <?php if ($viber): ?><a href="viber://chat?number=<?= e(rawurlencode($viber)) ?>"><span>✆</span>Viber</a><?php endif; ?>
      <?php if ($addr): ?><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($addr)) ?>" target="_blank" rel="noopener"><span>⌖</span>Map</a><?php endif; ?>
    </nav>

    <ul class="vc-list">
      <?php foreach ($phones as $p): ?>
        <li><a href="<?= e(tel_href($p['number'])) ?>"><small><?= e($p['label']) ?></small><?= e($p['number']) ?></a></li>
      <?php endforeach; ?>
      <?php foreach (array_unique(array_filter([$s['email'], $c['email'] ?? null])) as $em): ?>
        <li><a href="mailto:<?= e($em) ?>"><small>Email</small><?= e($em) ?></a></li>
      <?php endforeach; ?>
      <?php if (!empty($c['website'])): ?>
        <li><a href="<?= e($c['website']) ?>" target="_blank" rel="noopener"><small>Website</small><?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($c['website'], '/'))) ?></a></li>
      <?php endif; ?>
      <?php if (!empty($c['facebook'])): ?>
        <li><a href="<?= e($c['facebook']) ?>" target="_blank" rel="noopener"><small>Facebook</small><?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($c['facebook'], '/'))) ?></a></li>
      <?php endif; ?>
      <?php if ($addr): ?>
        <li><a href="https://www.google.com/maps/search/?api=1&amp;query=<?= e(rawurlencode($addr)) ?>" target="_blank" rel="noopener"><small>Address</small><?= nl2br(e($addr)) ?></a></li>
      <?php endif; ?>
    </ul>

    <?php if ($s['notes']): ?><p class="vc-note"><?= nl2br(e($s['notes'])) ?></p><?php endif; ?>
  </section>

  <div class="vc-save">
    <a class="btn primary block" href="<?= e($vcfUrl) ?>">＋ Save to contacts</a>
    <button type="button" class="btn block ghost" id="share" data-url="<?= e($cardUrl) ?>" data-title="<?= e($name) ?>">Share this card</button>
  </div>
</main>
<script>
document.getElementById('share').addEventListener('click', async (ev) => {
  const b = ev.currentTarget, url = b.dataset.url;
  try {
    if (navigator.share) { await navigator.share({ title: b.dataset.title, url }); return; }
    await navigator.clipboard.writeText(url); b.textContent = 'Link copied';
  } catch (e) { /* user cancelled */ }
});
</script>
</body>
</html>
