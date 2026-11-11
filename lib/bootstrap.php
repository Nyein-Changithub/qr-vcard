<?php
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
const ASSET_V = '2';
const AUTH_COOKIE = 'sc_admin';

final class HttpError extends Exception
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}

/* ---------- environment ---------- */

(function (): void {
    $file = ROOT . '/.env';
    if (!is_file($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"'");
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
})();

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    }
    return ($v === null || $v === '') ? $default : (string) $v;
}

function missing_config(): array
{
    $missing = [];
    foreach (['SUPABASE_URL', 'ADMIN_PASSWORD', 'APP_SECRET'] as $k) {
        if (!env($k)) {
            $missing[] = $k;
        }
    }
    if (!env('SUPABASE_SECRET_KEY') && !env('SUPABASE_SERVICE_ROLE_KEY')) {
        $missing[] = 'SUPABASE_SECRET_KEY';
    }
    return $missing;
}

/* ---------- small helpers ---------- */

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 303);
    exit;
}

function is_https(): bool
{
    return ($_SERVER['HTTPS'] ?? '') === 'on'
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function base_url(): string
{
    if ($u = env('APP_URL')) {
        return rtrim($u, '/');
    }
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    return (is_https() ? 'https' : 'http') . '://' . $host;
}

function is_uuid(string $s): bool
{
    return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $s);
}

function uuid4(): string
{
    $b = random_bytes(16);
    $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
    $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) {
            $s = strtolower($t);
        }
    }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return substr(trim($s, '-'), 0, 60);
}

function initials(string $name): string
{
    $out = '';
    foreach (preg_split('/\s+/u', trim($name)) ?: [] as $w) {
        if ($w !== '' && !preg_match('/^(mr|mrs|ms|dr|u|daw)\.?$/i', $w)) {
            $out .= mb_strtoupper(mb_substr($w, 0, 1));
        }
        if (mb_strlen($out) >= 2) {
            break;
        }
    }
    return $out ?: '?';
}

function normalize_url(string $url): string
{
    if ($url === '') {
        return '';
    }
    return preg_match('#^https?://#i', $url) ? $url : 'https://' . $url;
}

function hex_color(string $c, string $fallback = '#0b5cad'): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? strtolower($c) : $fallback;
}

/** "label: number" per line → [['label'=>..,'type'=>..,'number'=>..], ...] */
function parse_phones(?string $text): array
{
    $types = [
        'mobile' => 'CELL', 'cell' => 'CELL', 'phone' => 'CELL',
        'work' => 'WORK', 'office' => 'WORK', 'tel' => 'WORK',
        'home' => 'HOME', 'fax' => 'FAX',
    ];
    $out = [];
    foreach (preg_split('/\R/u', (string) $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $label = 'Mobile';
        if (preg_match('/^([^\d+:：]{1,20})[:：]\s*(.+)$/u', $line, $m)) {
            $label = ucfirst(trim($m[1]));
            $line = trim($m[2]);
        }
        $out[] = [
            'label' => $label,
            'type' => $types[strtolower($label)] ?? 'CELL',
            'number' => $line,
        ];
    }
    return $out;
}

function tel_href(string $n): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $n);
}

/* ---------- auth (stateless signed cookie, works on serverless) ---------- */

function sign(string $data): string
{
    return hash_hmac('sha256', $data, (string) env('APP_SECRET'));
}

function login_user(): void
{
    $exp = time() + 60 * 60 * 12;
    setcookie(AUTH_COOKIE, $exp . '.' . sign("admin|$exp"), [
        'expires' => $exp, 'path' => '/', 'secure' => is_https(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function logout_user(): void
{
    setcookie(AUTH_COOKIE, '', ['expires' => 1, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax']);
}

function is_admin(): bool
{
    if (!env('APP_SECRET')) {
        return false;
    }
    $c = $_COOKIE[AUTH_COOKIE] ?? '';
    if (!is_string($c) || !preg_match('/^(\d+)\.([a-f0-9]{64})$/', $c, $m) || (int) $m[1] < time()) {
        return false;
    }
    return hash_equals(sign("admin|{$m[1]}"), $m[2]);
}

function csrf_token(): string
{
    return sign('csrf|' . ($_COOKIE[AUTH_COOKIE] ?? ''));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    if (!hash_equals(csrf_token(), post('_csrf'))) {
        throw new HttpError('Form expired. Go back, reload the page and try again.', 419);
    }
}

/* ---------- views ---------- */

function view(string $tpl, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT . "/lib/views/$tpl.php";
    return (string) ob_get_clean();
}

function render(string $tpl, array $vars = [], ?string $layout = 'layout'): void
{
    header('Content-Type: text/html; charset=utf-8');
    $content = view($tpl, $vars);
    echo $layout ? view($layout, $vars + ['content' => $content]) : $content;
}

function render_error(int $code, string $message): void
{
    $code = ($code >= 400 && $code < 600) ? $code : 500;
    http_response_code($code);
    render('error', ['title' => "Error $code", 'code' => $code, 'message' => $message]);
}
