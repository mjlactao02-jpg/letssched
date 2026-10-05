<?php
$code = trim($_GET['c'] ?? $_GET['code'] ?? '');
if ($code === '') {
    header('Location: index.php');
    exit;
}
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = dirname($_SERVER['SCRIPT_NAME']);
if ($base==='/'||$base==='\\') $base='';
$shareUrl = $proto.'://'.$host.$base.'/event.php?c='.urlencode($code);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0ea5e9">
<title>Event <?= htmlspecialchars($code) ?> - LetsSched</title>
<link rel="stylesheet" href="assets/style.css?v=11">
</head>
<body>
<header class="topbar">
  <div class="container topbar-inner">
    <a href="index.php" class="brand" aria-label="LetsSched home">
      <span class="brand-badge" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="3.5" y="4.5" width="17" height="14.5" rx="2.2" fill="white"/>
          <path d="M3.5 8.2H20.5" stroke="#e8f0fe" stroke-width="1"/>
          <path d="M8 2.8V6M16 2.8V6" stroke="white" stroke-width="1.9" stroke-linecap="round" opacity="0.95"/>
          <circle cx="8.2" cy="11.7" r="1.1" fill="#0ea5e9"/>
          <circle cx="12" cy="11.7" r="1.1" fill="#0ea5e9"/>
          <circle cx="15.8" cy="11.7" r="1.1" fill="#0ea5e9"/>
          <circle cx="8.2" cy="15" r="1.1" fill="#0ea5e9"/>
          <circle cx="12" cy="15" r="1.1" fill="#6366f1"/>
          <circle cx="16.5" cy="15.3" r="2.4" fill="#10b981" stroke="white" stroke-width="1.1"/>
          <path d="M15.3 15.3l0.9 0.9 1.9-1.9" stroke="white" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
        </svg>
      </span>
      <span class="brand-text">LetsSched</span>
    </a>
    <nav class="nav"><a href="index.php">+ New event</a></nav>
  </div>
</header>

<main class="container" id="eventRoot" data-code="<?= htmlspecialchars($code,ENT_QUOTES) ?>">
  <div class="event-head">
    <h1 class="event-title" id="eventTitle">Loading...</h1>
    <div class="muted" id="eventMeta"></div>
    <div class="share-row">
      <div class="linkbox" style="flex:1">
        <span class="muted" style="font-weight:800;font-size:12px">SHARE LINK</span>
        <input id="shareInput" readonly value="<?= htmlspecialchars($shareUrl,ENT_QUOTES) ?>">
        <button class="btn btn-ghost" id="copyLinkBtn" style="padding:7px 12px;white-space:nowrap">Copy Link</button>
      </div>
      <span class="badge" id="countBadge">-</span>
      <a class="btn btn-ghost" href="<?= htmlspecialchars($shareUrl,ENT_QUOTES) ?>" target="_blank" rel="noopener">Open</a>
    </div>
    <div class="muted" style="font-size:13px">Send this link to your GC - anyone with the link can add their schedule. Re-enter same name to edit.</div>
  </div>

  <div class="grid2">
    <div>
      <div class="card">
        <div class="card-pad">
          <h3>Add your availability</h3>
          <p class="muted" style="margin:4px 0 8px">Type your name, then <b>tap or drag</b> on the grid. Green = free. <span id="selCount" style="font-weight:800;color:#059669">0</span> slots selected.</p>
          <label for="nameInput">Your name *</label>
          <input id="nameInput" class="input" type="text" inputmode="text" autocomplete="name" autocapitalize="words" placeholder="e.g. Alex, Mara, JC" maxlength="80" style="position:relative;z-index:1">
          <div id="nameHint" class="muted" style="font-size:12px;margin-top:4px;display:none"></div>
          <div id="selectWrap" class="schedule-wrap schedule-wrap--select" style="margin-top:10px"></div>
          <div style="display:flex;gap:8px;margin-top:10px">
            <button class="btn btn-ghost" id="clearSelBtn" type="button">Clear</button>
            <button class="btn btn-ok" id="submitAvailBtn" type="button" style="flex:1">Save My Availability</button>
          </div>
          <div id="eventMsg"></div>
          <div class="legend"><i style="background:#10b981"></i> your selection &nbsp; <i style="background:#fff"></i> busy/unpicked &nbsp; <span class="muted">Tip: drag to paint many slots</span></div>
        </div>
      </div>

      <div class="card" style="margin-top:14px">
        <div class="card-pad">
          <h3>Participants</h3>
          <div id="partList" class="participants" style="margin-top:8px"></div>
        </div>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-pad">
          <h3>Overlapping view (heatmap)</h3>
          <p class="muted" style="margin:4px 0 8px">Darker green = more people free. Number = headcount.</p>
          <div id="heatmapWrap" class="schedule-wrap schedule-wrap--heat"></div>
          <div class="legend">
            <span><i style="background:#ffffff"></i> 0</span>
            <span><i style="background:#ecfdf5"></i> 1</span>
            <span><i style="background:#d1fae5"></i> 2</span>
            <span><i style="background:#a7f3d0"></i> 3</span>
            <span><i style="background:#6ee7b7"></i> 4</span>
            <span><i style="background:#059669"></i> max (all)</span>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:14px">
        <div class="card-pad">
          <h3>Best times to meet</h3>
          <p class="muted" style="margin:4px 0 8px">Ranked by most available. Dark green = top overlap.</p>
          <div id="bestList" class="best-list"></div>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer"><a href="index.php">&lt;- Create another event</a> - Tip: drag to select multiple slots quickly.</footer>
</main>
<script src="assets/app.js?v=11"></script>
</body>
</html>

