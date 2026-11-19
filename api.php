<?php
/**
 * Cairo Shoes POS — Standalone PHP Sync API Bridge (api.php)
 *
 * Allows syncing store snapshot (products, variants, invoices, expenses, shifts, employees)
 * between Android Mobile and Desktop (React/Electron/Web) on any standard PHP hosting.
 *
 * Endpoints:
 * - GET  api.php                -> returns current store snapshot JSON
 * - POST api.php                -> merges/saves incoming store snapshot JSON
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dbFile = __DIR__ . '/cairo_shoes_store_db.json';

if (!file_exists($dbFile)) {
    $now = gmdate('Y-m-d\TH:i:s\Z');
    $initial = [
        'version' => 1,
        'updatedAt' => $now,
        'employees' => [
            [
                'id' => 'usr-admin-01',
                'username' => 'admin',
                'fullName' => 'مدير النظام',
                'role' => 'admin',
                'isActive' => true,
                'createdAt' => $now,
            ],
        ],
        'passwords' => [
            'admin' => '123456',
        ],
        'products' => [],
        'shifts' => [
            [
                'id' => 'shf-1',
                'shiftNumber' => 1,
                'openedAt' => $now,
                'closedAt' => null,
                'openingCashPiasters' => 0,
                'expectedCashPiasters' => 0,
                'countedCashPiasters' => null,
                'snapshot' => null,
            ],
        ],
        'invoices' => [],
        'expenses' => [],
    ];
    file_put_contents($dbFile, json_encode($initial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $raw = file_get_contents($dbFile);
    echo $raw;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);

    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON payload']);
        exit;
    }

    $snapshot = isset($decoded['snapshot']) && is_array($decoded['snapshot'])
        ? $decoded['snapshot']
        : $decoded;

    $snapshot['updatedAt'] = gmdate('Y-m-d\TH:i:s\Z');
    file_put_contents(
        $dbFile,
        json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    echo json_encode([
        'status' => 'ok',
        'snapshot' => $snapshot,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
