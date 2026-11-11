<?php
declare(strict_types=1);

function dispatch(string $method, string $path): void
{
    if ($method === 'GET' && preg_match('#^/assets/([a-z]+\.(?:css|js))$#', $path, $m)) {
        serve_asset($m[1]);
        return;
    }
    if ($path === '/favicon.ico') {
        http_response_code(204);
        return;
    }

    if ($missing = missing_config()) {
        http_response_code(500);
        render('setup', ['title' => 'Setup needed', 'missing' => $missing]);
        return;
    }

    if ($path === '/') {
        redirect('/admin');
    }

    // Public card + contact download
    if ($method === 'GET' && preg_match('#^/c/([a-z0-9-]+)\.vcf$#', $path, $m)) {
        page_vcf($m[1]);
        return;
    }
    if ($method === 'GET' && preg_match('#^/c/([a-z0-9-]+)$#', $path, $m)) {
        page_card($m[1]);
        return;
    }

    // Admin
    if ($path === '/admin/login') {
        $method === 'POST' ? admin_login_post() : admin_login_form();
        return;
    }
    if ($path === '/admin/logout') {
        logout_user();
        redirect('/admin/login');
    }
    if (str_starts_with($path, '/admin')) {
        if (!is_admin()) {
            redirect('/admin/login');
        }
        if ($method === 'POST') {
            check_csrf();
        }
        header('Cache-Control: no-store');
        $U = '([0-9a-f-]{36})';
        $routes = [
            ['GET',  '#^/admin$#', 'admin_dashboard'],
            ['GET',  '#^/admin/company/new$#', 'admin_company_form'],
            ['GET',  '#^/admin/company/' . $U . '$#', 'admin_company_form'],
            ['POST', '#^/admin/company/save$#', 'admin_company_save'],
            ['POST', '#^/admin/company/' . $U . '/delete$#', 'admin_company_delete'],
            ['GET',  '#^/admin/staff/new$#', 'admin_staff_form'],
            ['GET',  '#^/admin/staff/' . $U . '$#', 'admin_staff_form'],
            ['GET',  '#^/admin/staff/' . $U . '/qr$#', 'admin_staff_qr'],
            ['POST', '#^/admin/staff/save$#', 'admin_staff_save'],
            ['POST', '#^/admin/staff/' . $U . '/delete$#', 'admin_staff_delete'],
        ];
        foreach ($routes as [$verb, $re, $fn]) {
            if ($verb === $method && preg_match($re, $path, $m)) {
                $fn(...array_slice($m, 1));
                return;
            }
        }
    }

    throw new HttpError('Page not found.', 404);
}

function serve_asset(string $name): void
{
    $file = ROOT . '/assets/' . $name;
    if (!is_file($file)) {
        throw new HttpError('Not found', 404);
    }
    header('Content-Type: ' . (str_ends_with($name, '.css') ? 'text/css' : 'text/javascript') . '; charset=utf-8');
    header('Cache-Control: public, max-age=86400');
    readfile($file);
}
