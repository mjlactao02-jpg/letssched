<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

$code = trim($_GET['c'] ?? $_GET['code'] ?? '');
if ($code === '') { http_response_code(400); echo json_encode(['error'=>'Missing code']); exit; }

$stmt = $pdo->prepare("SELECT * FROM events WHERE code=?");
$stmt->execute([$code]);
$event = $stmt->fetch();
if (!$event) { http_response_code(404); echo json_encode(['error'=>'Event not found']); exit; }

$dates = json_decode($event['dates'], true) ?: [];
$slotMinutes = intval($event['slot_minutes']);
$allSlots = getAllSlots($dates, $event['time_start'], $event['time_end'], $slotMinutes);

// participants
$pstmt = $pdo->prepare("SELECT id,name FROM participants WHERE event_id=? ORDER BY created_at");
$pstmt->execute([$event['id']]);
$participants = $pstmt->fetchAll();
$pidToName = [];
foreach ($participants as $p) $pidToName[$p['id']] = $p['name'];

// availabilities grouped by slot key
$astmt = $pdo->prepare("SELECT participant_id, date, slot_time FROM availabilities WHERE event_id=?");
$astmt->execute([$event['id']]);
$rows = $astmt->fetchAll();

$heatmap = []; // key => count
$slotToNames = []; // key => [names]
foreach ($rows as $r) {
    $k = $r['date'] . ' ' . $r['slot_time'];
    $heatmap[$k] = ($heatmap[$k] ?? 0) + 1;
    $slotToNames[$k][] = $pidToName[$r['participant_id']] ?? '?';
}
// ensure all slots present
foreach ($allSlots as $s) {
    if (!isset($heatmap[$s['key']])) $heatmap[$s['key']] = 0;
    if (!isset($slotToNames[$s['key']])) $slotToNames[$s['key']] = [];
}

$maxCount = $heatmap ? max($heatmap) : 0;
$total = count($participants);

// best times sorted desc by count then earliest
$best = [];
foreach ($allSlots as $s) {
    $k = $s['key'];
    $best[] = [
        'key' => $k,
        'date' => $s['date'],
        'slot_time' => $s['slot_time'],
        'label' => $s['label'],
        'range' => fmtSlotRange($s['slot_time'], $slotMinutes),
        'date_label' => fmtDate($s['date']),
        'count' => $heatmap[$k],
        'total' => $total,
        'names' => $slotToNames[$k],
    ];
}
usort($best, function($a,$b){
    if ($b['count'] !== $a['count']) return $b['count'] <=> $a['count'];
    return strcmp($a['key'], $b['key']);
});
$best = array_slice($best, 0, 30); // top 30

// participants with their slots for detail view
$partDetail = [];
foreach ($participants as $p) {
    $slots = [];
    foreach ($rows as $r) if ((int)$r['participant_id']===(int)$p['id']) $slots[] = $r['date'].' '.$r['slot_time'];
    $partDetail[] = ['name'=>$p['name'], 'slots'=>$slots];
}

$mode = $event['mode'] ?? 'dates';
$meta = $event['meta'] ? json_decode($event['meta'], true) : null;
$weekLabel = null;
if ($mode==='week' && $meta && !empty($meta['monday'])) {
    $weekLabel = fmtWeekRange($dates);
}
echo json_encode([
    'event'=>[
        'code'=>$event['code'],
        'title'=>$event['title'],
        'description'=>$event['description'],
        'dates'=>$dates,
        'time_start'=>substr($event['time_start'],0,5),
        'time_end'=>substr($event['time_end'],0,5),
        'slot_minutes'=>$slotMinutes,
        'mode'=>$mode,
        'meta'=>$meta,
        'weekLabel'=>$weekLabel,
        'created_at'=>$event['created_at'],
    ],
    'allSlots'=>$allSlots,
    'heatmap'=>$heatmap,
    'slotToNames'=>$slotToNames,
    'maxCount'=>$maxCount,
    'totalParticipants'=>$total,
    'participants'=>$partDetail,
    'bestTimes'=>$best,
]);
