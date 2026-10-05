<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$title = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$dates = $input['dates'] ?? [];
$mode = trim($input['mode'] ?? 'dates');
if (!in_array($mode, ['dates','week'])) $mode = 'dates';
$week = trim($input['week'] ?? '');
$weekdays = $input['weekdays'] ?? [];
if (is_string($weekdays)) { $wd=json_decode($weekdays,true); if(is_array($wd)) $weekdays=$wd; else $weekdays=[]; }
$meta = null;
$time_start = $input['time_start'] ?? '09:00';
$time_end = $input['time_end'] ?? '18:00';
$slot = intval($input['slot_minutes'] ?? 30);

// decode dates if sent as JSON string
if (is_string($dates)) {
    $decoded = json_decode($dates, true);
    if (is_array($decoded)) $dates = $decoded;
    else $dates = array_filter(array_map('trim', explode(',', $dates)));
}

// week mode: compute dates from week + weekdays (also merges any manually added dates)
if ($mode === 'week') {
    if ($week === '') { http_response_code(400); echo json_encode(['error'=>'Pick a week']); exit; }
    $monday = parseISOWeek($week);
    // fallback: if week sent as YYYY-MM-DD (date input fallback), use that monday
    if (!$monday && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
        $ts=strtotime($week);
        $monday=date('Y-m-d', strtotime('Monday this week', $ts));
        if (date('N', $ts)==1) $monday=$week;
        // normalize to monday
        $week = isoWeekFromDate($monday);
    }
    if (!$monday) { http_response_code(400); echo json_encode(['error'=>"Bad week: $week"]); exit; }
    // weekdays: 0=Mon ...6=Sun
    $cleanWd=[];
    foreach ((array)$weekdays as $wd) { $wd=intval($wd); if($wd>=0&&$wd<=6) $cleanWd[]=$wd; }
    if (!$cleanWd) { http_response_code(400); echo json_encode(['error'=>'Pick at least 1 weekday']); exit; }
    $weekDates = weekDatesInWeek($monday, $cleanWd);
    $dates = array_merge((array)$dates, $weekDates);
    $meta = json_encode(['week'=>$week, 'monday'=>$monday, 'weekdays'=>$cleanWd]);
}

if ($title === '' || strlen($title) > 150) {
    http_response_code(400); echo json_encode(['error' => 'Title required (max 150)']); exit;
}
if (count($dates) === 0) {
    http_response_code(400); echo json_encode(['error' => 'Pick at least 1 date']); exit;
}
if (count($dates) > 14) {
    http_response_code(400); echo json_encode(['error' => 'Max 14 dates']); exit;
}
$cleanDates = [];
foreach ($dates as $d) {
    $d = trim($d);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { http_response_code(400); echo json_encode(['error' => "Bad date: $d"]); exit; }
    $cleanDates[] = $d;
}
sort($cleanDates);
$cleanDates = array_values(array_unique($cleanDates));

if (!in_array($slot, [15,30,60])) $slot = 30;
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time_start)) $time_start = '09:00';
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time_end)) $time_end = '18:00';
$time_start = substr($time_start,0,5) . ':00';
$time_end = substr($time_end,0,5) . ':00';
if (strtotime("2000-01-01 $time_start") >= strtotime("2000-01-01 $time_end")) {
    http_response_code(400); echo json_encode(['error' => 'Start must be before end']); exit;
}

$datesJson = json_encode($cleanDates);

$code = '';
for ($tries=0; $tries<10; $tries++) {
    $c = generateCode(6);
    $chk = $pdo->prepare("SELECT id FROM events WHERE code=?");
    $chk->execute([$c]);
    if (!$chk->fetch()) { $code = $c; break; }
}
if ($code === '') { http_response_code(500); echo json_encode(['error'=>'Failed to generate code']); exit; }

$stmt = $pdo->prepare("INSERT INTO events (code,title,description,dates,time_start,time_end,slot_minutes,mode,meta) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->execute([$code, $title, $description, $datesJson, $time_start, $time_end, $slot, $mode, $meta]);
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = dirname(dirname($_SERVER['SCRIPT_NAME'])); // /letssched
if ($base === '/' || $base === '\\') $base = '';
$url = $proto . '://' . $host . $base . '/event.php?c=' . $code;

echo json_encode(['code'=>$code, 'url'=>$url]);
