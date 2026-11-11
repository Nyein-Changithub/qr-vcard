<?php $s = $staff; $c = $company; ?>
<div class="page-head">
  <div><a class="back" href="/admin/staff/<?= e($s['id']) ?>">← <?= e($s['full_name']) ?></a><h1>QR code</h1></div>
</div>

<div class="qr-layout" id="qr-app"
     data-card="<?= e($cardUrl) ?>" data-vcf="<?= e($vcfUrl) ?>" data-contact="<?= e($contact) ?>"
     data-logo="<?= e($c['logo_url'] ?? '') ?>" data-color="<?= e(hex_color((string) $c['brand_color'])) ?>"
     data-name="<?= e($s['slug']) ?>">
  <div class="card qr-preview">
    <div id="qr-canvas"></div>
    <p class="muted small-text" id="qr-data"></p>
    <div class="btn-row">
      <button type="button" class="btn primary" data-download="png">Download PNG</button>
      <button type="button" class="btn" data-download="svg">Download SVG</button>
    </div>
  </div>

  <form class="card form qr-controls" onsubmit="return false">
    <h2>Customize</h2>
    <label>When scanned, open
      <select name="target">
        <option value="contact">Contact details (scanner shows name, phones → Import contact)</option>
        <option value="vcf">Contact file .vcf (imports with logo / photo picture)</option>
        <option value="card">Card page (photo, info, products, website)</option>
      </select>
    </label>
    <label data-for="vcf">Contact picture
      <select name="picture">
        <option value="logo">Company logo</option>
        <option value="photo">Staff photo</option>
      </select>
    </label>
    <p class="muted small-text" data-for="contact">The contact is stored inside the QR, so it works offline. A QR cannot hold a picture, so the contact circle stays empty. To get the logo in the contact circle, choose “Contact file .vcf”.</p>
    <div class="two">
      <label>Dot style
        <select name="dots">
          <option value="rounded">Rounded</option><option value="dots">Dots</option>
          <option value="classy-rounded">Classy rounded</option><option value="classy">Classy</option>
          <option value="extra-rounded">Extra rounded</option><option value="square">Square</option>
        </select>
      </label>
      <label>Corner style
        <select name="corners">
          <option value="extra-rounded">Rounded</option><option value="dot">Dot</option><option value="square">Square</option>
        </select>
      </label>
    </div>
    <div class="three">
      <label class="color">Dots <input type="color" name="color"></label>
      <label class="color">Corners <input type="color" name="corner_color"></label>
      <label class="color">Background <input type="color" name="bg" value="#ffffff"></label>
    </div>
    <label class="check"><input type="checkbox" name="logo" checked <?= empty($c['logo_url']) ? 'disabled' : '' ?>> Company logo in the middle</label>
    <label>Logo size <input type="range" name="logo_size" min="0.15" max="0.4" step="0.01" value="0.3"></label>
    <label>Download size
      <select name="size"><option value="600">600 px</option><option value="1200" selected>1200 px (print)</option><option value="2400">2400 px (large print)</option></select>
    </label>
    <p class="muted">Tip: link-based QRs (.vcf / card page) stay simple and let you edit details later without reprinting. Test with your phone camera before printing.</p>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.9.2/lib/qr-code-styling.js"></script>
<script src="/assets/qr.js?v=<?= ASSET_V ?>"></script>
