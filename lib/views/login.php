<form class="card login" method="post" action="/admin/login">
  <h1>Smart Cards</h1>
  <p class="muted">Sign in to manage companies, staff and QR codes.</p>
  <?php if (!empty($error)): ?><div class="flash err"><?= e($error) ?></div><?php endif; ?>
  <label>Password<input type="password" name="password" required autofocus autocomplete="current-password"></label>
  <button class="btn primary block">Sign in</button>
</form>
