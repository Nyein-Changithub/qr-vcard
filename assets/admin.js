// Resize / crop images in the browser before upload, so stored files stay small
// (small photos also keep the downloaded .vcf light).
function loadImage(file) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    img.onload = () => resolve(img);
    img.onerror = reject;
    img.src = URL.createObjectURL(file);
  });
}

document.querySelectorAll('input[type=file][data-target]').forEach((input) => {
  input.addEventListener('change', async () => {
    const file = input.files && input.files[0];
    if (!file) return;
    let img;
    try { img = await loadImage(file); } catch (e) { alert('Could not read this image.'); return; }
    const max = Number(input.dataset.max) || 512;
    const format = input.dataset.format || 'image/jpeg';
    let sx = 0, sy = 0, sw = img.naturalWidth, sh = img.naturalHeight;
    if (input.dataset.square === '1') {
      const side = Math.min(sw, sh);
      sx = (sw - side) / 2; sy = (sh - side) / 2; sw = sh = side;
    }
    const scale = Math.min(1, max / Math.max(sw, sh));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(sw * scale);
    canvas.height = Math.round(sh * scale);
    const ctx = canvas.getContext('2d');
    if (format === 'image/jpeg') { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height); }
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
    const dataUrl = canvas.toDataURL(format, 0.85);
    document.getElementById(input.dataset.target).value = dataUrl;
    const preview = document.getElementById(input.dataset.preview);
    if (preview) { preview.src = dataUrl; preview.hidden = false; }
  });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (ev) => {
    if (!confirm(form.dataset.confirm)) ev.preventDefault();
  });
});
