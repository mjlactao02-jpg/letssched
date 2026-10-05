<?php
function generateCode(int $len = 6): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $code = '';
    for ($i = 0; $i < $len; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function getAllSlots(array $dates, string $timeStart, string $timeEnd, int $slotMinutes): array {
    $slots = [];
    foreach ($dates as $d) {
        $start = strtotime("$d $timeStart");
        $end = strtotime("$d $timeEnd");
        for ($t = $start; $t + $slotMinutes * 60 <= $end; $t += $slotMinutes * 60) {
            $slots[] = [
                'date' => $d,
                'slot_time' => date('H:i:s', $t),
                'label' => date('g:i A', $t),
                'key' => $d . ' ' . date('H:i:s', $t)
            ];
        }
    }
    return $slots;
}

function fmtDate(string $d): string {
    return date('M j (D)', strtotime($d));
}
function fmtTime(string $t): string {
    return date('g:i A', strtotime("2000-01-01 $t"));
}
function fmtSlotRange(string $time, int $minutes): string {
    $s = strtotime("2000-01-01 $time");
    $e = $s + $minutes * 60;
    return date('g:i A', $s) . ' - ' . date('g:i A', $e);
}
function parseISOWeek(string $week): ?string {
    // "2026-W39" -> Monday YYYY-MM-DD or null
    if (!preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) return null;
    $y = intval($m[1]); $w = intval($m[2]);
    if ($w < 1 || $w > 53) return null;
    $dto = new DateTime();
    $dto->setISODate($y, $w, 1); // Monday
    return $dto->format('Y-m-d');
}
function weekDatesInWeek(string $monday, array $weekdays): array {
    // weekdays: 0=Mon ... 6=Sun, returns YYYY-MM-DD list
    $out=[];
    $base=strtotime($monday);
    foreach ($weekdays as $wd) {
        $wd=intval($wd);
        if($wd<0||$wd>6) continue;
        $out[] = date('Y-m-d', $base + $wd*86400);
    }
    sort($out);
    return $out;
}
function fmtWeekRange(array $dates): string {
    if (!$dates) return '';
    sort($dates);
    $a=$dates[0]; $b=end($dates);
    if ($a===$b) return fmtDate($a);
    // same month? "Sep 21 - 27"
    if (date('Y-m', strtotime($a))===date('Y-m', strtotime($b))) {
        return date('M j', strtotime($a)) . ' - ' . date('j, Y', strtotime($b));
    }
    return fmtDate($a) . ' - ' . fmtDate($b);
}
function isoWeekFromDate(string $date): string {
    $ts=strtotime($date);
    return date('o-\WW', $ts); // 2026-W39
}
