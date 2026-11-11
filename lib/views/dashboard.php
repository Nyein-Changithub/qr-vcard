<div class="page-head">
  <h1>Companies</h1>
  <a class="btn primary" href="/admin/company/new">+ Add company</a>
</div>
<?php if (!$companies): ?>
  <div class="card empty">
    <p><strong>No companies yet.</strong></p>
    <p class="muted">Add a company with its logo and info, then add staff. Each staff member gets a card page, a contact file with photo, and a custom QR code.</p>
  </div>
<?php endif; ?>
<div class="tiles">
<?php foreach ($companies as $c): $n = (int) ($c['staff'][0]['count'] ?? 0); ?>
  <a class="card tile" href="/admin/company/<?= e($c['id']) ?>" style="--brand: <?= e(hex_color((string) $c['brand_color'])) ?>">
    <span class="logo-box">
      <?php if ($c['logo_url']): ?><img src="<?= e($c['logo_url']) ?>" alt=""><?php else: ?><?= e(initials($c['name'])) ?><?php endif; ?>
    </span>
    <span><strong><?= e($c['name']) ?></strong><small class="muted"><?= $n ?> staff</small></span>
  </a>
<?php endforeach; ?>
</div>
