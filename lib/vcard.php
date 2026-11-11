<?php
declare(strict_types=1);

/* vCard 3.0 builder. The photo is embedded as base64, so phones import it with the contact. */

function vc_escape(?string $s): string
{
    $s = str_replace("\r", '', (string) $s);
    return str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $s);
}

/** Fold to 75 octets per line without splitting UTF-8 characters (RFC 2425). */
function vc_fold(string $line): string
{
    $out = [];
    $max = 75;
    while (strlen($line) > $max) {
        $chunk = mb_strcut($line, 0, $max, 'UTF-8');
        $out[] = $chunk;
        $line = substr($line, strlen($chunk));
        $max = 74; // continuation lines start with a space
    }
    $out[] = $line;
    return implode("\r\n ", $out);
}

/** Phones for a staff member, plus the company line if it is not already listed. */
function all_phones(array $staff, array $company): array
{
    $phones = parse_phones($staff['phones'] ?? '');
    $digits = array_map(fn ($p) => preg_replace('/\D/', '', $p['number']), $phones);
    foreach (parse_phones($company['phone'] ?? '') as $p) {
        if (!in_array(preg_replace('/\D/', '', $p['number']), $digits, true)) {
            $p['label'] = $p['label'] === 'Mobile' ? 'Office' : $p['label'];
            $p['type'] = $p['type'] === 'CELL' ? 'WORK' : $p['type'];
            $phones[] = $p;
        }
    }
    return $phones;
}

function build_vcard(array $s, array $c, ?array $photo, string $cardUrl): string
{
    $parts = preg_split('/\s+/u', trim((string) $s['full_name'])) ?: [];
    $family = count($parts) > 1 ? array_pop($parts) : '';
    $given = implode(' ', $parts);
    $fn = trim(($s['prefix'] ?? '') . ' ' . $s['full_name']);

    $L = ['BEGIN:VCARD', 'VERSION:3.0'];
    $L[] = 'N:' . vc_escape($family) . ';' . vc_escape($given) . ';;' . vc_escape($s['prefix'] ?? '') . ';';
    $L[] = 'FN:' . vc_escape($fn);
    $L[] = 'ORG:' . vc_escape($c['name']) . (!empty($s['department']) ? ';' . vc_escape($s['department']) : '');
    if (!empty($s['role'])) {
        $L[] = 'TITLE:' . vc_escape($s['role']);
    }
    foreach (all_phones($s, $c) as $p) {
        $type = $p['type'] === 'CELL' ? 'CELL,VOICE' : $p['type'] . ($p['type'] === 'FAX' ? '' : ',VOICE');
        $L[] = "TEL;TYPE=$type:" . vc_escape($p['number']);
    }
    $emails = array_unique(array_filter([$s['email'] ?? '', $c['email'] ?? '']));
    foreach (array_values($emails) as $i => $em) {
        $L[] = 'EMAIL;TYPE=INTERNET,WORK' . ($i === 0 ? ',PREF' : '') . ':' . vc_escape($em);
    }
    $addr = ($s['address'] ?? '') ?: ($c['address'] ?? '');
    if ($addr) {
        $L[] = 'ADR;TYPE=WORK:;;' . vc_escape($addr) . ';;;;';
    }
    if (!empty($c['website'])) {
        $L[] = 'URL;TYPE=WORK:' . vc_escape($c['website']);
    }
    if (!empty($c['facebook'])) {
        $L[] = 'X-SOCIALPROFILE;TYPE=facebook:' . vc_escape($c['facebook']);
    }
    $note = trim(($s['notes'] ?? '') . "\n" . ($c['tagline'] ?? '') . "\nCard: " . $cardUrl);
    $L[] = 'NOTE:' . vc_escape($note);
    if ($photo) {
        $L[] = 'PHOTO;ENCODING=b;TYPE=' . $photo[1] . ':' . base64_encode($photo[0]);
    }
    $L[] = 'REV:' . gmdate('Ymd\THis\Z');
    $L[] = 'END:VCARD';

    return implode("\r\n", array_map('vc_fold', $L)) . "\r\n";
}
