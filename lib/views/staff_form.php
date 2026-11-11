<?php $s = $staff; $id = $s['id'] ?? ''; ?>
<div class="page-head">
  <div><a class="back" href="/admin/company/<?= e($company['id']) ?>">← <?= e($company['name']) ?></a>
  <h1><?= e($id ? $s['full_name'] : 'New staff member') ?></h1></div>
  <?php if ($id): ?>
  <div class="head-actions">
    <a class="btn" href="/c/<?= e($s['slug']) ?>" target="_blank">View card</a>
    <a class="btn primary" href="/admin/staff/<?= e($id) ?>/qr">QR code</a>
  </div>
  <?php endif; ?>
</div>

<form class="card form" method="post" action="/admin/staff/save">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($id) ?>">
  <div class="row">
    <div class="uploader">
      <img id="photo-preview" class="preview photo" src="<?= e($s['photo_url'] ?? '') ?>" alt="" <?= empty($s['photo_url']) ? 'hidden' : '' ?>>
      <label class="btn small">Upload photo
        <input type="file" accept="image/*" hidden data-target="photo_data" data-preview="photo-preview" data-max="480" data-format="image/jpeg" data-square="1">
      </label>
      <input type="hidden" name="photo_data" id="photo_data">
      <?php if (!empty($s['photo_url'])): ?><label class="check"><input type="checkbox" name="remove_photo" value="1"> Remove</label><?php endif; ?>
      <small class="muted">This becomes the contact photo. Cropped square automatically. Without one, the company logo is used.</small>
    </div>
    <div class="fields">
      <label>Company
        <select name="company_id">
          <?php foreach ($companies as $co): ?>
            <option value="<?= e($co['id']) ?>" <?= $co['id'] === $s['company_id'] ? 'selected' : '' ?>><?= e($co['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="two narrow-first">
        <label>Prefix<input name="prefix" value="<?= e($s['prefix'] ?? '') ?>" placeholder="Mr."></label>
        <label>Full name *<input name="full_name" required value="<?= e($s['full_name'] ?? '') ?>" placeholder="Rishi Kumar Agarwal"></label>
      </div>
      <div class="two">
        <label>Role / title<input name="role" value="<?= e($s['role'] ?? '') ?>" placeholder="Director"></label>
        <label>Department<input name="department" value="<?= e($s['department'] ?? '') ?>" placeholder="Sales"></label>
      </div>
      <label>Phone numbers
        <textarea name="phones" rows="4" placeholder="Mobile: +95 9 510 4920&#10;Work: +95 1 925 0834&#10;Home: ...&#10;Fax: ..."><?= e($s['phones'] ?? '') ?></textarea>
        <small class="muted">One per line. Optional label before a colon: Mobile, Work, Office, Home, Fax. The company office phone is added automatically.</small>
      </label>
      <div class="two">
        <label>Email<input type="email" name="email" value="<?= e($s['email'] ?? '') ?>"></label>
        <label>Viber number<input name="viber" value="<?= e($s['viber'] ?? '') ?>" placeholder="+95 9 ..."></label>
      </div>
      <label>Address <small class="muted">(leave empty to use the company address)</small><textarea name="address" rows="2"><?= e($s['address'] ?? '') ?></textarea></label>
      <label>Note<textarea name="notes" rows="2" placeholder="Short bio, languages, working hours..."><?= e($s['notes'] ?? '') ?></textarea></label>
      <div class="two">
        <label>Card link (slug)<input name="slug" value="<?= e($s['slug'] ?? '') ?>" placeholder="auto from name" pattern="[a-z0-9\-]*"></label>
        <label>Sort order<input type="number" name="sort_order" value="<?= (int) ($s['sort_order'] ?? 0) ?>"></label>
      </div>
      <?php if ($id): ?><small class="muted">Changing the link breaks QR codes already printed.</small><?php endif; ?>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($s['is_active']) ? 'checked' : '' ?>> Active (card is public)</label>
    </div>
  </div>
  <div class="actions"><button class="btn primary">Save staff member</button></div>
</form>

<?php if ($id): ?>
<form class="danger-zone" method="post" action="/admin/staff/<?= e($id) ?>/delete" data-confirm="Delete this staff card? Printed QR codes will stop working.">
  <?= csrf_field() ?>
  <button class="btn danger small">Delete staff member</button>
</form>
<?php endif; ?>
