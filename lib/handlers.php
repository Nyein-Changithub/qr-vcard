<?php
declare(strict_types=1);

/* ================= Public ================= */

function find_card(string $slug): array
{
    $s = db_first('staff', 'slug=eq.' . rawurlencode($slug) . '&is_active=is.true&select=*,company:companies(*)');
    if (!$s || empty($s['company'])) {
        throw new HttpError('This card does not exist or is no longer active.', 404);
    }
    return $s;
}

function public_cache(): void
{
    // Vercel edge caches for 60s, so admin edits show up within a minute.
    header('Cache-Control: public, max-age=0, s-maxage=60, stale-while-revalidate=300');
}

function page_card(string $slug): void
{
    $s = find_card($slug);
    public_cache();
    render('card', [
        'staff' => $s,
        'company' => $s['company'],
        'phones' => all_phones($s, $s['company']),
        'products' => parse_products($s['company']['products'] ?? ''),
        'cardUrl' => base_url() . '/c/' . $s['slug'],
        'vcfUrl' => '/c/' . $s['slug'] . '.vcf',
    ], null);
}

function page_vcf(string $slug): void
{
    $s = find_card($slug);
    // Contact picture: company logo by default, ?p=photo prefers the staff photo.
    $urls = [$s['company']['logo_url'] ?? null, $s['photo_url'] ?? null];
    if (($_GET['p'] ?? '') === 'photo') {
        $urls = array_reverse($urls);
    }
    $photo = fetch_image($urls[0]) ?? fetch_image($urls[1]);
    $vcf = build_vcard($s, $s['company'], $photo, base_url() . '/c/' . $s['slug']);
    public_cache();
    // text/x-vcard + inline lets Android browsers hand the file straight to the
    // Contacts app (no "open the download" step); iOS Safari shows the contact as before.
    header('Content-Type: text/x-vcard; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $s['slug'] . '.vcf"');
    header('Content-Length: ' . strlen($vcf));
    echo $vcf;
}

/* ================= Auth ================= */

function admin_login_form(?string $error = null): void
{
    if (is_admin()) {
        redirect('/admin');
    }
    render('login', ['title' => 'Sign in', 'error' => $error]);
}

function admin_login_post(): void
{
    $expected = (string) env('ADMIN_PASSWORD');
    if ($expected !== '' && hash_equals($expected, post('password'))) {
        login_user();
        redirect('/admin');
    }
    usleep(800_000); // slow down guessing
    http_response_code(401);
    admin_login_form('Wrong password.');
}

/* ================= Companies ================= */

function admin_dashboard(): void
{
    $companies = db_select('companies', 'select=*,staff(count)&order=name.asc');
    render('dashboard', ['title' => 'Companies', 'companies' => $companies]);
}

function admin_company_form(?string $id = null): void
{
    $company = $id ? db_first('companies', 'id=eq.' . $id) : null;
    if ($id && !$company) {
        throw new HttpError('Company not found.', 404);
    }
    $staff = $id ? db_select('staff', 'company_id=eq.' . $id . '&order=sort_order.asc,full_name.asc') : [];
    render('company_form', [
        'title' => $company ? $company['name'] : 'New company',
        'company' => $company ?? ['brand_color' => '#0b5cad'],
        'staff' => $staff,
    ]);
}

function admin_company_save(): void
{
    $id = post('id');
    $isNew = $id === '';
    if (!$isNew && !is_uuid($id)) {
        throw new HttpError('Bad company id.');
    }
    $data = [
        'name' => post('name'),
        'tagline' => post('tagline') ?: null,
        'phone' => post('phone') ?: null,
        'email' => post('email') ?: null,
        'website' => normalize_url(post('website')) ?: null,
        'facebook' => normalize_url(post('facebook')) ?: null,
        'address' => post('address') ?: null,
        'products' => post('products') ?: null,
        'brand_color' => hex_color(post('brand_color')),
        'updated_at' => gmdate('c'),
    ];
    if ($data['name'] === '') {
        throw new HttpError('Company name is required.', 422);
    }
    if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new HttpError('Company email is not valid.', 422);
    }

    $old = $isNew ? null : db_first('companies', 'id=eq.' . $id);
    if (!$isNew && !$old) {
        throw new HttpError('Company not found.', 404);
    }
    if ($data['products'] === null && !array_key_exists('products', $old ?? [])) {
        unset($data['products']); // keeps saving working before the products column exists
    }
    if ($isNew) {
        $id = uuid4();
        $data['id'] = $id;
    }
    if (post('remove_logo') === '1') {
        $data['logo_url'] = null;
    }
    if ($img = post('logo_data')) {
        $data['logo_url'] = upload_data_url($img, "logos/$id-" . time());
    }

    $isNew ? db_insert('companies', $data) : db_update('companies', 'id=eq.' . $id, $data);

    if ($old && array_key_exists('logo_url', $data) && $old['logo_url'] !== $data['logo_url']) {
        storage_delete_url($old['logo_url']);
    }
    redirect("/admin/company/$id?saved=1");
}

function admin_company_delete(string $id): void
{
    $c = db_first('companies', 'id=eq.' . $id);
    if ($c) {
        foreach (db_select('staff', 'company_id=eq.' . $id . '&select=photo_url') as $s) {
            storage_delete_url($s['photo_url']);
        }
        db_delete('companies', 'id=eq.' . $id); // staff rows cascade
        storage_delete_url($c['logo_url']);
    }
    redirect('/admin?deleted=1');
}

/* ================= Staff ================= */

function admin_staff_form(?string $id = null): void
{
    $staff = $id ? db_first('staff', 'id=eq.' . $id) : null;
    if ($id && !$staff) {
        throw new HttpError('Staff member not found.', 404);
    }
    $companyId = $staff['company_id'] ?? (string) ($_GET['company'] ?? '');
    if (!is_uuid($companyId)) {
        throw new HttpError('Choose a company first.', 400);
    }
    $company = db_first('companies', 'id=eq.' . $companyId);
    if (!$company) {
        throw new HttpError('Company not found.', 404);
    }
    render('staff_form', [
        'title' => $staff ? $staff['full_name'] : 'New staff member',
        'staff' => $staff ?? ['company_id' => $companyId, 'is_active' => true, 'sort_order' => 0],
        'company' => $company,
        'companies' => db_select('companies', 'select=id,name&order=name.asc'),
    ]);
}

function unique_slug(string $wanted, string $name, ?string $selfId): string
{
    $base = slugify($wanted) ?: slugify($name);
    if (strlen($base) < 2) {
        $base = 'card-' . bin2hex(random_bytes(3));
    }
    for ($i = 0; $i < 30; $i++) {
        $slug = $i === 0 ? $base : $base . '-' . ($i + 1);
        $q = 'slug=eq.' . rawurlencode($slug) . '&select=id' . ($selfId ? '&id=neq.' . $selfId : '');
        if (!db_first('staff', $q)) {
            return $slug;
        }
    }
    return $base . '-' . bin2hex(random_bytes(3));
}

function admin_staff_save(): void
{
    $id = post('id');
    $isNew = $id === '';
    if (!$isNew && !is_uuid($id)) {
        throw new HttpError('Bad staff id.');
    }
    $companyId = post('company_id');
    if (!is_uuid($companyId) || !db_first('companies', 'id=eq.' . $companyId . '&select=id')) {
        throw new HttpError('Please choose a valid company.', 422);
    }
    $data = [
        'company_id' => $companyId,
        'prefix' => post('prefix') ?: null,
        'full_name' => post('full_name'),
        'role' => post('role') ?: null,
        'department' => post('department') ?: null,
        'phones' => post('phones') ?: null,
        'email' => post('email') ?: null,
        'viber' => post('viber') ?: null,
        'address' => post('address') ?: null,
        'notes' => post('notes') ?: null,
        'sort_order' => (int) post('sort_order'),
        'is_active' => post('is_active') === '1',
        'updated_at' => gmdate('c'),
    ];
    if ($data['full_name'] === '') {
        throw new HttpError('Full name is required.', 422);
    }
    if ($data['email'] && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new HttpError('Email is not valid.', 422);
    }

    $old = $isNew ? null : db_first('staff', 'id=eq.' . $id);
    if (!$isNew && !$old) {
        throw new HttpError('Staff member not found.', 404);
    }
    if ($isNew) {
        $id = uuid4();
        $data['id'] = $id;
    }
    $data['slug'] = unique_slug(post('slug'), $data['full_name'], $isNew ? null : $id);

    if (post('remove_photo') === '1') {
        $data['photo_url'] = null;
    }
    if ($img = post('photo_data')) {
        $data['photo_url'] = upload_data_url($img, "photos/$id-" . time());
    }

    $isNew ? db_insert('staff', $data) : db_update('staff', 'id=eq.' . $id, $data);

    if ($old && array_key_exists('photo_url', $data) && $old['photo_url'] !== $data['photo_url']) {
        storage_delete_url($old['photo_url']);
    }
    redirect("/admin/staff/$id?saved=1");
}

function admin_staff_delete(string $id): void
{
    $s = db_first('staff', 'id=eq.' . $id);
    if (!$s) {
        redirect('/admin');
    }
    db_delete('staff', 'id=eq.' . $id);
    storage_delete_url($s['photo_url']);
    redirect('/admin/company/' . $s['company_id'] . '?deleted=1');
}

function admin_staff_qr(string $id): void
{
    $s = db_first('staff', 'id=eq.' . $id . '&select=*,company:companies(*)');
    if (!$s) {
        throw new HttpError('Staff member not found.', 404);
    }
    render('qr', [
        'title' => 'QR · ' . $s['full_name'],
        'staff' => $s,
        'company' => $s['company'],
        'cardUrl' => base_url() . '/c/' . $s['slug'],
        'vcfUrl' => base_url() . '/c/' . $s['slug'] . '.vcf',
        'contact' => build_vcard($s, $s['company'], null, base_url() . '/c/' . $s['slug'], true),
    ]);
}
