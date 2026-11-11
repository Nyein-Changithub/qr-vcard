<div class="card">
  <h1>Setup needed</h1>
  <p>These environment variables are not set yet:</p>
  <ul><?php foreach ($missing as $m): ?><li><code><?= e($m) ?></code></li><?php endforeach; ?></ul>
  <p class="muted">On Vercel: Project → Settings → Environment Variables, then redeploy. Locally: copy <code>.env.example</code> to <code>.env</code>.</p>
</div>
