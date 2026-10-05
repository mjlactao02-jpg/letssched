<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST only']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$code = trim($input['code'] ?? $input['c'] ?? '');
$name = trim($input['name'] ?? '');
$slots = $input['slots'] ?? [];

if (is_string($slots)) { $d=json_decode($slots,true); if(is_array($d)) $slots=$d; else $slots=[]; }

if ($code==='') { http_response_code(400); echo json_encode(['error'=>'Missing event code']); exit; }
if ($name==='' || strlen($name)>80) { http_response_code(400); echo json_encode(['error'=>'Name required (max 80)']); exit; }
if (!preg_match('/^[A-Za-z0-9 _.\-]{1,80}$/', $name)) {
    // allow broader unicode names but block control chars
    if (preg_match('/[\x00-\x1F\x7F]/', $name)) { http_response_code(400); echo json_encode(['error'=>'Invalid name']); exit; }
}
if (!is_array($slots) || count($slots)===0) { http_response_code(400); echo json_encode(['error'=>'Select at least one time slot']); exit; }
if (count($slots)>500) { http_response_code(400); echo json_encode(['error'=>'Too many slots']); exit; }

$stmt = $pdo->prepare("SELECT * FROM events WHERE code=?");
$stmt->execute([$code]);
$event = $stmt->fetch();
if (!$event) { http_response_code(404); echo json_encode(['error'=>'Event not found']); exit; }

$dates = json_decode($event['dates'], true) ?: [];
$allowedKeys = [];
foreach (getAllSlots($dates, $event['time_start'], $event['time_end'], intval($event['slot_minutes'])) as $s) $allowedKeys[$s['key']] = $s;

// validate slots
$normalized = [];
foreach ($slots as $s) {
    if (is_string($s)) {
        // "2026-09-21 09:00:00"
        if (!isset($allowedKeys[$s])) { http_response_code(400); echo json_encode(['error'=>"Invalid slot: $s"]); exit; }
        $parts = explode(' ', $s);
        $normalized[] = ['date'=>$parts[0], 'slot_time'=>$parts[1]];
    } elseif (is_array($s)) {
        $d = $s['date'] ?? $s['d'] ?? '';
        $t = $s['slot_time'] ?? $s['time'] ?? $s['slot'] ?? '';
        // also accept start_time
        if (isset($s['start'])) $t = $s['start'];
        $t = substr(trim($t),0,8);
        if (strlen($t)===5) $t.=':00';
        $k = "$d $t";
        if (!isset($allowedKeys[$k])) { http_response_code(400); echo json_encode(['error'=>"Invalid slot: $k"]); exit; }
        $normalized[] = ['date'=>$d, 'slot_time'=>$t];
    }
}
$normalized = array_unique(array_map(fn($x)=>$x['date'].' '.$x['slot_time'], $normalized));
$normalized = array_map(function($k){ $p=explode(' ', $k); return ['date'=>$p[0],'slot_time'=>$p[1]]; }, $normalized);

// upsert participant
$pdo->beginTransaction();
try {
    $ps = $pdo->prepare("SELECT id FROM participants WHERE event_id=? AND name=?");
    $ps->execute([$event['id'], $name]);
    $part = $ps->fetch();
    if ($part) {
        $pid = (int)$part['id'];
        $pdo->prepare("DELETE FROM availabilities WHERE participant_id=?")->execute([$pid]);
    } else {
        $pdo->prepare("INSERT INTO participants (event_id,name) VALUES (?,?)")->execute([$event['id'], $name]);
        $pid = (int)$pdo->lastInsertId();
    }
    $ins = $pdo->prepare("INSERT INTO availabilities (event_id, participant_id, date, slot_time) VALUES (?,?,?,?)");
    foreach ($normalized as $n) {
        $ins->execute([$event['id'], $pid, $n['date'], $n['slot_time']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); echo json_encode(['error'=>'DB error: '.$e->getMessage()]); exit;
}

echo json_encode(['success'=>true, 'name'=>$name, 'count'=>count($normalized)]);
