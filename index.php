<?php
// index.php - Landing + Create Event
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#0ea5e9">
<title>LetsSched - Find overlapping free time without logins</title>
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
    <nav class="nav"><a href="index.php">Create</a><a href="#how">How it works</a></nav>
  </div>
</header>

<main class="container">
  <section class="hero">
    <h1>Find a time that works for <span>everyone</span></h1>
    <p>Enter your available times, send the link to your GC, and see overlapping schedules instantly.</p>
  </section>

  <div class="grid2">
    <div class="card">
      <div class="card-pad">
        <h3>Create a schedule poll</h3>
        <p class="muted" style="margin:4px 0 10px">Choose <b>Specific dates</b> or <b>By week</b> - both work great for forms & overlap testing.</p>
        <form id="createForm">
          <label>Event title</label>
          <input class="input" name="title" placeholder="e.g. Thesis Meeting, Badminton, Study Group" maxlength="150" required>
          <label>Description (optional)</label>
          <input class="input" name="description" placeholder="e.g. Pick your free slots this week">
          <label>Choose Dates<span class="muted" style="font-weight:400"> - up to 14 dates total</span></label>
          <div class="mode-tabs" role="tablist" aria-label="Date selection mode">
            <button type="button" class="mode-tab active" data-mode="dates" role="tab" aria-selected="true">Specific dates</button>
            <button type="button" class="mode-tab" data-mode="week" role="tab" aria-selected="false">By week</button>
          </div>

          <!-- DATES MODE -->
          <div id="pane-dates" class="pane active">
            <div class="row">
              <input class="input" type="date" id="dateInput" aria-label="Add date">
              <button type="button" class="btn btn-ghost" id="addDateBtn" style="max-width:120px">Add date</button>
            </div>
          </div>

          <!-- WEEK MODE -->
          <div id="pane-week" class="pane">
            <div class="row">
              <div>
                <label style="margin-top:0">Week *</label>
                <input class="input" type="week" id="weekInput" aria-label="Week">
                <input class="input" type="date" id="weekDateFallback" aria-label="Week fallback" style="display:none;margin-top:8px" placeholder="Pick any day in that week">
              </div>
              <div style="display:flex;align-items:flex-end">
                <button type="button" class="btn btn-ghost btn-block" id="addWeekBtn" style="min-height:42px">Add week -&gt; dates</button>
              </div>
            </div>
            <label style="margin-top:10px">Days in that week</label>
            <div class="week-grid" id="weekGrid" role="group" aria-label="Weekdays"></div>
            <div class="row" style="margin-top:8px">
              <button type="button" class="btn btn-ghost" id="weekAllBtn" style="flex:1">Whole week</button>
              <button type="button" class="btn btn-ghost" id="weekDaysBtn" style="flex:1">Weekdays</button>
              <button type="button" class="btn btn-ghost" id="weekEndBtn" style="flex:1">Weekends</button>
            </div>
          </div>

          <div id="datesWrap" class="dates-wrap" style="margin-top:10px"></div>
          <div id="datesCount" class="muted" style="font-size:12px;margin-top:6px"></div>
          <input type="hidden" id="datesHidden" name="dates">
          <input type="hidden" id="modeHidden" name="mode" value="dates">
          <input type="hidden" id="weekHidden" name="week" value="">
          <input type="hidden" id="weekdaysHidden" name="weekdays" value="">

          <div class="row row-time" style="margin-top:10px">
            <div>
              <label>Daily start</label>
              <input class="input" type="time" name="time_start" value="09:00" required>
            </div>
            <div>
              <label>Daily end</label>
              <input class="input" type="time" name="time_end" value="18:00" required>
            </div>
            <div>
              <label>Slot size</label>
              <select name="slot_minutes" aria-label="Slot size">
                <option value="15">15 min</option>
                <option value="30" selected>30 min</option>
                <option value="60">60 min</option>
              </select>
            </div>
          </div>

          <div id="createMsg"></div>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">Create &amp; Get Shareable Link -&gt;</button>
        </form>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-pad">
          <h3>How it works</h3>
          <div class="steps" style="grid-template-columns:1fr">
            <div class="step"><b>1.</b> You create a poll - <b>Specific dates</b> or <b>By week</b>.<br><span class="muted">e.g. Sep 22-26 via week picker, 9AM-6PM, 30-min slots.</span></div>
            <div class="step"><b>2.</b> Copy the link &amp; send to GC. <br><span class="muted">Friends open it, type name, tap/drag their free slots.</span></div>
            <div class="step"><b>3.</b> See overlap instantly.<br><span class="muted">Heatmap + "Best times" where most people are free.</span></div>
          </div>
        
      </div>
  
    </div>
  </div>

</main>
<script src="assets/app.js?v=11"></script>
</body>
</html>

