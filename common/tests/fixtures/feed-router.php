<?php
$code = $_GET['game'] ?? '';
header('Content-Type: application/json');
if ($code === 'az51') { http_response_code(503); echo '{}'; return; }
if ($code === 'jsssc') { echo '{bad json'; return; }
if ($code === 'jspk10') { echo str_repeat('x', 1024 * 1024 + 1); return; }
echo json_encode(['code' => 1000, 'data' => ['lotCode' => $code, 'list' => [['issue' => 'fixture_1', 'draw_code' => '1,2,3',
    'draw_time' => '2026-11-02 12:00:00', 'draw_status' => 'ignored']]]]);
