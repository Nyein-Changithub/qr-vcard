<?php $c = $company; $id = $c['id'] ?? ''; ?>
<div class="page-head">
  <div><a class="back" href="/admin">← Companies</a><h1><?= e($id ? $c['name'] : 'New company') ?></h1></div>
</div>

<form class="card form" method="post" action="/admin/company/save">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($id) ?>">
  <h2>Company info</h2>
  <div class="row">
    <div class="uploader">
      <img id="logo-preview" class="preview logo" src="<?= e($c['logo_url'] ?? '') ?>" alt="" <?= empty($c['logo_url']) ? 'hidden' : '' ?>>
      <label class="btn small">Upload logo
        <input type="file" accept="image/*" hidden data-target="logo_data" data-preview="logo-preview" data-max="600" data-format="image/png">
      </label>
      <input type="hidden" name="logo_data" id="logo_data">
      <?php if (!empty($c['logo_url'])): ?><label class="check"><input type="checkbox" name="remove_logo" value="1"> Remove</label><?php endif; ?>
      <small class="muted">PNG with transparent background works best. Shown in the QR and on the card.</small>
    </div>
    <div class="fields">
      <label>Company name *<input name="name" required value="<?= e($c['name'] ?? '') ?>"></label>
      <label>Tagline<input name="tagline" value="<?= e($c['tagline'] ?? '') ?>" placeholder="Petroleum products since 1995"></label>
      <div class="two">
        <label>Office phone<input name="phone" value="<?= e($c['phone'] ?? '') ?>" placeholder="+95 1 925 0833"></label>
        <label>Company email<input type="email" name="email" value="<?= e($c['email'] ?? '') ?>"></label>
      </div>
      <div class="two">
        <label>Website<input name="website" value="<?= e($c['website'] ?? '') ?>" placeholder="example.com"></label>
        <label>Facebook page<input name="facebook" value="<?= e($c['facebook'] ?? '') ?>" placeholder="facebook.com/yourpage"></label>
      </div>
      <label>Address<textarea name="address" rows="2"><?= e($c['address'] ?? '') ?></textarea></label>
      <label>Products / services <small class="muted">(one per line, optional link: <code>Diesel | example.com/diesel</code>)</small>
        <textarea name="products" rows="4" placeholder="Diesel&#10;Lubricants | example.com/lubricants"><?= e($c['products'] ?? '') ?></textarea></label>
      <label class="color">Brand color <input type="color" name="brand_color" value="<?= e(hex_color((string) ($c['brand_color'] ?? ''))) ?>"></label>
    </div>
  </div>
  <div class="actions"><button class="btn primary">Save company</button></div>
</form>

<?php if ($id): ?>
<section class="card">
  <div class="page-head">
    <h2>Staff (<?= count($staff) ?>)</h2>
    <a class="btn primary" href="/admin/staff/new?company=<?= e($id) ?>">+ Add staff</a>
  </div>
  <?php if (!$staff): ?><p class="muted">No staff yet.</p><?php endif; ?>
  <ul class="staff-list">
  <?php foreach ($staff as $s): ?>
    <li>
      <span class="avatar"><?php if ($s['photo_url']): ?><img src="<?= e($s['photo_url']) ?>" alt=""><?php else: ?><?= e(initials($s['full_name'])) ?><?php endif; ?></span>
      <span class="who">
        <strong><?= e(trim(($s['prefix'] ?? '') . ' ' . $s['full_name'])) ?></strong>
        <small class="muted"><?= e($s['role'] ?? '') ?><?= $s['is_active'] ? '' : ' · <em>inactive</em>' ?></small>
      </span>
      <span class="links">
        <a class="btn small" href="/c/<?= e($s['slug']) ?>" target="_blank">Card</a>
        <a class="btn small" href="/admin/staff/<?= e($s['id']) ?>/qr">QR</a>
        <a class="btn small" href="/admin/staff/<?= e($s['id']) ?>">Edit</a>
      </span>
    </li>
  <?php endforeach; ?>
  </ul>
</section>

<form class="danger-zone" method="post" action="/admin/company/<?= e($id) ?>/delete" data-confirm="Delete this company and ALL its staff cards? This cannot be undone.">
  <?= csrf_field() ?>
  <button class="btn danger small">Delete company</button>
</form>
<?php endif; ?>
