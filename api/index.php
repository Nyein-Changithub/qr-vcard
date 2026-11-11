<?php
declare(strict_types=1);

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/supabase.php';
require __DIR__ . '/../lib/vcard.php';
require __DIR__ . '/../lib/handlers.php';
require __DIR__ . '/../lib/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = '/' . trim(rawurldecode($path), '/');

try {
    dispatch($method, $path);
} catch (HttpError $e) {
    render_error($e->getCode(), $e->getMessage());
} catch (Throwable $e) {
    error_log((string) $e);
    render_error(500, is_admin() ? $e->getMessage() : 'Something went wrong. Please try again.');
}
