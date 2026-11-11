(function () {
  const app = document.getElementById('qr-app');
  if (!app || typeof QRCodeStyling === 'undefined') return;
  const form = document.querySelector('.qr-controls');
  const f = (name) => form.elements[name];
  f('color').value = app.dataset.color;
  f('corner_color').value = app.dataset.color;

  // The bundled QR encoder writes one byte per char code, so pass UTF-8 bytes
  // (keeps Myanmar / Unicode text in the contact readable).
  const utf8 = (s) => unescape(encodeURIComponent(s));

  // Load the logo with CORS under its own URL: a copy cached by a normal <img>
  // can fail the CORS check, and then the library draws nothing at all.
  // If the logo cannot be loaded, the QR is drawn without it.
  let logo = '';
  if (app.dataset.logo) {
    const url = app.dataset.logo + (app.dataset.logo.includes('?') ? '&' : '?') + 'qr=1';
    const probe = new Image();
    probe.crossOrigin = 'anonymous';
    probe.onload = () => { logo = url; redraw(); };
    probe.onerror = () => { f('logo').disabled = true; };
    probe.src = url;
  }

  function options() {
    const target = f('target').value;
    document.querySelectorAll('[data-for]').forEach((el) => { el.hidden = el.dataset.for !== target; });
    let data = app.dataset.card;
    if (target === 'vcf') data = app.dataset.vcf + (f('picture').value === 'photo' ? '?p=photo' : '');
    if (target === 'contact') data = app.dataset.contact;
    const size = Number(f('size').value);
    const useLogo = f('logo').checked && !f('logo').disabled && logo;
    document.getElementById('qr-data').textContent = target === 'contact'
      ? 'Contact card · ' + utf8(data).length + ' bytes'
      : data;
    data = utf8(data);
    return {
      width: size, height: size, type: 'canvas', data, margin: Math.round(size * 0.04),
      image: useLogo ? logo : undefined,
      qrOptions: { errorCorrectionLevel: 'H' },
      imageOptions: { crossOrigin: 'anonymous', margin: Math.round(size * 0.01), imageSize: Number(f('logo_size').value), hideBackgroundDots: true },
      dotsOptions: { type: f('dots').value, color: f('color').value },
      cornersSquareOptions: { type: f('corners').value, color: f('corner_color').value },
      cornersDotOptions: { type: f('corners').value === 'square' ? 'square' : 'dot', color: f('corner_color').value },
      backgroundOptions: { color: f('bg').value },
    };
  }

  const qr = new QRCodeStyling(options());
  qr.append(document.getElementById('qr-canvas'));
  function redraw() { qr.update(options()); }
  form.addEventListener('input', redraw);
  form.addEventListener('change', redraw);

  document.querySelectorAll('[data-download]').forEach((btn) => {
    btn.addEventListener('click', () => qr.download({ name: 'qr-' + app.dataset.name, extension: btn.dataset.download }));
  });
})();
