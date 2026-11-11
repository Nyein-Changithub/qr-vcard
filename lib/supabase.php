<?php
declare(strict_types=1);

/* Thin client for Supabase REST (PostgREST) + Storage, using the server-side secret key. */

function sb_url(): string
{
    return rtrim((string) env('SUPABASE_URL'), '/');
}

function sb_bucket(): string
{
    return env('SUPABASE_BUCKET', 'media');
}

function sb_http(string $method, string $url, mixed $body = null, array $headers = [], bool $raw = false): string
{
    $key = (string) (env('SUPABASE_SECRET_KEY') ?? env('SUPABASE_SERVICE_ROLE_KEY'));
    $h = ['apikey: ' . $key];
    if (str_starts_with($key, 'eyJ')) {           // legacy JWT service_role key
        $h[] = 'Authorization: Bearer ' . $key;
    }
    if ($body !== null && !$raw) {
        $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $h[] = 'Content-Type: application/json';
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array_merge($h, $headers),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $res = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($res === false) {
        throw new RuntimeException('Cannot reach Supabase: ' . curl_error($ch));
    }
    if ($code >= 400) {
        $msg = json_decode($res, true);
        $msg = is_array($msg) ? ($msg['message'] ?? $msg['error'] ?? $res) : $res;
        throw new RuntimeException("Supabase error $code: $msg");
    }
    return (string) $res;
}

function db(string $method, string $table, string $query = '', mixed $body = null, array $headers = []): array
{
    $url = sb_url() . '/rest/v1/' . $table . ($query !== '' ? '?' . $query : '');
    $res = sb_http($method, $url, $body, $headers);
    return $res === '' ? [] : (json_decode($res, true) ?? []);
}

function db_select(string $table, string $query = ''): array
{
    return db('GET', $table, $query);
}

function db_first(string $table, string $query): ?array
{
    return db_select($table, $query . '&limit=1')[0] ?? null;
}

function db_insert(string $table, array $data): ?array
{
    return db('POST', $table, '', $data, ['Prefer: return=representation'])[0] ?? null;
}

function db_update(string $table, string $filter, array $data): ?array
{
    return db('PATCH', $table, $filter, $data, ['Prefer: return=representation'])[0] ?? null;
}

function db_delete(string $table, string $filter): void
{
    db('DELETE', $table, $filter);
}

/** Upload a base64 data-URL image coming from the admin form; returns its public URL. */
function upload_data_url(string $dataUrl, string $basePath): string
{
    if (!preg_match('#^data:(image/(?:png|jpeg|webp));base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
        throw new HttpError('Unsupported image. Please use JPG or PNG.', 422);
    }
    $bytes = base64_decode($m[2], true);
    if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024 || !@getimagesizefromstring($bytes)) {
        throw new HttpError('Image is invalid or larger than 2 MB.', 422);
    }
    $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$m[1]];
    $path = "$basePath.$ext";
    sb_http('POST', sb_url() . '/storage/v1/object/' . sb_bucket() . '/' . $path, $bytes, [
        'Content-Type: ' . $m[1],
        'x-upsert: true',
        'cache-control: max-age=31536000',
    ], true);
    return sb_url() . '/storage/v1/object/public/' . sb_bucket() . '/' . $path;
}

/** Best-effort removal of a file we uploaded earlier (ignores anything else). */
function storage_delete_url(?string $url): void
{
    $prefix = sb_url() . '/storage/v1/object/public/' . sb_bucket() . '/';
    if (!$url || !str_starts_with($url, $prefix)) {
        return;
    }
    try {
        sb_http('DELETE', sb_url() . '/storage/v1/object/' . sb_bucket(), ['prefixes' => [substr($url, strlen($prefix))]]);
    } catch (Throwable $e) {
        error_log('storage delete failed: ' . $e->getMessage());
    }
}

/** Download an image (for embedding into the .vcf). Returns [bytes, 'JPEG'|'PNG'] or null. */
function fetch_image(?string $url): ?array
{
    if (!$url || !preg_match('#^https://#', $url)) {
        return null;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_TIMEOUT => 10]);
    $bytes = curl_exec($ch);
    if (!is_string($bytes) || curl_getinfo($ch, CURLINFO_RESPONSE_CODE) !== 200 || strlen($bytes) > 1024 * 1024) {
        return null;
    }
    $info = @getimagesizefromstring($bytes);
    $type = match ($info['mime'] ?? '') {
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        default => null,
    };
    return $type ? [$bytes, $type] : null;
}
