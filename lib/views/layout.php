<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'Admin') ?> · Smart Cards</title>
<link rel="stylesheet" href="/assets/app.css?v=<?= ASSET_V ?>">
</head>
<body class="admin">
<?php if (is_admin()): ?>
<header class="topbar">
  <a class="brand" href="/admin"><span class="brand-mark">▦</span> Smart Cards</a>
  <nav><a href="/admin">Companies</a><a href="/admin/logout">Sign out</a></nav>
</header>
<?php endif; ?>
<main class="wrap">
<?php if (!empty($_GET['saved'])): ?><div class="flash ok">Saved.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="flash">Deleted.</div><?php endif; ?>
<?= $content ?>
</main>
<script src="/assets/admin.js?v=<?= ASSET_V ?>" defer></script>
</body>
</html>
