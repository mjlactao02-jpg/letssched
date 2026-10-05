// app.js - shared helpers for index + event
const $ = s => document.querySelector(s);
const $$ = s => document.querySelectorAll(s);

// index page: date/week picker handling
(function indexPage(){
  const form = $('#createForm');
  if(!form) return;
  const dateInput = $('#dateInput');
  const datesWrap = $('#datesWrap');
  const datesHidden = $('#datesHidden');
  const msg = $('#createMsg');
  const countEl = $('#datesCount');
  const modeHidden = $('#modeHidden');
  const weekHidden = $('#weekHidden');
  const weekdaysHidden = $('#weekdaysHidden');
  const weekInput = $('#weekInput');
  const weekFallback = $('#weekDateFallback');
  const weekGrid = $('#weekGrid');
  const addWeekBtn = $('#addWeekBtn');
  let dates = [];
  let mode = 'dates'; // 'dates' | 'week'
  let weekdays = new Set([0,1,2,3,4]); // default Mon-Fri
  let lastWeekValue = '';

  const DAY_LABELS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
  const DAY_SMALL  = ['M','T','W','T','F','S','S'];

  function updateCount(){
    if(countEl) countEl.textContent = dates.length ? `${dates.length}/14 dates selected` + (dates.length>=14 ? ' - max reached' : '') : '';
  }

  function renderDates(){
    datesWrap.innerHTML='';
    dates.sort();
    datesHidden.value = JSON.stringify(dates);
    if(modeHidden) modeHidden.value = mode;
    // store last week for submit
    if(weekHidden) weekHidden.value = lastWeekValue || (weekInput?.value || '');
    if(weekdaysHidden) weekdaysHidden.value = JSON.stringify([...weekdays].sort((a,b)=>a-b));
    updateCount();
    if(dates.length===0){
      datesWrap.innerHTML='<span class="muted">No dates picked yet - use Specific dates or By week above.</span>';
      return;
    }
    dates.forEach(d=>{
      const chip=document.createElement('span');
      chip.className='date-chip';
      chip.innerHTML=`<span>${d} <small class="muted">${new Date(d+'T00:00:00').toLocaleDateString('en-US',{weekday:'short'})}</small></span> <button type="button" aria-label="remove">&times;</button>`;
      chip.querySelector('button').onclick=()=>{ dates=dates.filter(x=>x!==d); renderDates(); };
      datesWrap.appendChild(chip);
    });
  }

  function renderWeekGrid(){
    if(!weekGrid) return;
    weekGrid.innerHTML='';
    DAY_LABELS.forEach((lbl,i)=>{
      const btn=document.createElement('button');
      btn.type='button';
      btn.className='week-day' + (weekdays.has(i) ? ' active' : '');
      btn.dataset.wd=i;
      btn.setAttribute('aria-pressed', weekdays.has(i) ? 'true' : 'false');
      btn.innerHTML=`${lbl}<small>${DAY_SMALL[i]}</small>`;
      btn.addEventListener('click', ()=>{
        if(weekdays.has(i)) weekdays.delete(i); else weekdays.add(i);
        renderWeekGrid();
      });
      weekGrid.appendChild(btn);
    });
  }

  // mode tabs
  $$('.mode-tab').forEach(tab=>{
    tab.addEventListener('click', ()=>{
      mode = tab.dataset.mode;
      $$('.mode-tab').forEach(t=>{ t.classList.toggle('active', t===tab); t.setAttribute('aria-selected', t===tab?'true':'false'); });
      $$('.pane').forEach(p=> p.classList.toggle('active', p.id==='pane-'+mode));
      if(modeHidden) modeHidden.value=mode;
    });
  });

  // detect week input support; Firefox has no type=week
  (function detectWeekSupport(){
    if(!weekInput) return;
    const test=document.createElement('input');
    test.type='week';
    const supported = test.type==='week';
    if(!supported){
      weekInput.style.display='none';
      if(weekFallback) weekFallback.style.display='block';
      const hint=$('#weekHint');
      if(hint) hint.textContent='Your browser has no week picker - pick any day in that week below.';
    }
  })();

  function mondayOfISOWeek(weekStr){
    // weekStr "2026-W39" -> "2026-09-21"
    const m=weekStr.match(/^(\d{4})-W(\d{2})$/);
    if(!m) return null;
    const y=parseInt(m[1],10), w=parseInt(m[2],10);
    // ISO Monday: Jan 4 is always week 1
    const jan4=new Date(Date.UTC(y,0,4));
    const jan4Day=(jan4.getUTCDay()+6)%7; // 0=Mon
    const monday=new Date(jan4);
    monday.setUTCDate(jan4.getUTCDate() - jan4Day + (w-1)*7);
    return monday.toISOString().slice(0,10);
  }
  function datesFromWeek(weekVal, wds){
    let monday=null;
    if(/^\d{4}-W\d{2}$/.test(weekVal)) monday=mondayOfISOWeek(weekVal);
    else if(/^\d{4}-\d{2}-\d{2}$/.test(weekVal)){
      // date fallback: get Monday of that week's ISO week
      const d=new Date(weekVal+'T00:00:00');
      const day=(d.getDay()+6)%7; // 0 Mon
      const mon=new Date(d); mon.setDate(d.getDate()-day);
      monday=mon.toISOString().slice(0,10);
      weekVal = `${mon.getFullYear()}-W${String(getISOWeek(mon)).padStart(2,'0')}`;
    }
    if(!monday) return {week:weekVal, monday:null, dates:[]};
    const base=new Date(monday+'T00:00:00');
    const out=[];
    wds.forEach(wd=>{
      const d=new Date(base); d.setDate(base.getDate()+wd);
      out.push(d.toISOString().slice(0,10));
    });
    out.sort();
    return {week:weekVal, monday, dates:out};
  }
  function getISOWeek(date){
    const d=new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const day=(d.getUTCDay()+6)%7;
    d.setUTCDate(d.getUTCDate()-day+3);
    const jan4=new Date(Date.UTC(d.getUTCFullYear(),0,4));
    return 1+Math.round((d-jan4)/604800000);
  }

  $('#addDateBtn')?.addEventListener('click', ()=>{
    const v=dateInput.value;
    if(!v) return;
    if(dates.includes(v)) return;
    if(dates.length>=14){ msg.innerHTML='<div class="alert alert-err">Max 14 dates.</div>'; return; }
    dates.push(v); dateInput.value=''; renderDates(); msg.innerHTML='';
  });
  dateInput?.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); $('#addDateBtn').click(); }});

  addWeekBtn?.addEventListener('click', ()=>{
    let weekVal = weekInput?.value?.trim() || '';
    // if week unsupported, use fallback date
    if(!weekVal && weekFallback && weekFallback.value) weekVal = weekFallback.value.trim();
    if(!weekVal){ msg.innerHTML='<div class="alert alert-err">Pick a week first.</div>'; return; }
    if(weekdays.size===0){ msg.innerHTML='<div class="alert alert-err">Pick at least one weekday.</div>'; return; }
    const res=datesFromWeek(weekVal, [...weekdays]);
    if(!res.monday){ msg.innerHTML='<div class="alert alert-err">Invalid week.</div>'; return; }
    let added=0, skipped=0;
    res.dates.forEach(d=>{
      if(dates.includes(d)){ skipped++; return; }
      if(dates.length>=14){ skipped++; return; }
      dates.push(d); added++;
    });
    lastWeekValue = res.week;
    msg.innerHTML='';
    if(added===0 && skipped>0) msg.innerHTML='<div class="alert alert-err">All those dates already added or max 14 reached.</div>';
    else if(added>0) msg.innerHTML=`<div class="alert alert-ok">Added ${added} date(s) from week ${res.week} (${res.monday}).</div>`;
    renderDates();
  });
  $('#weekAllBtn')?.addEventListener('click', ()=>{ weekdays=new Set([0,1,2,3,4,5,6]); renderWeekGrid(); });
  $('#weekDaysBtn')?.addEventListener('click', ()=>{ weekdays=new Set([0,1,2,3,4]); renderWeekGrid(); });
  $('#weekEndBtn')?.addEventListener('click', ()=>{ weekdays=new Set([5,6]); renderWeekGrid(); });

  form.addEventListener('submit', async e=>{
    e.preventDefault();
    msg.innerHTML='';
    if(dates.length===0){ msg.innerHTML='<div class="alert alert-err">Pick at least one date (via Specific dates or By week).</div>'; return; }
    const fd=new FormData(form);
    // detect if user actually used week mode (lastWeekValue set) else dates-only
    const effectiveMode = lastWeekValue ? 'week' : 'dates';
    // if effectiveMode is week but no weekdays? fallback
    const payload={
      title: fd.get('title'),
      description: fd.get('description'),
      dates: dates,
      mode: effectiveMode,
      week: lastWeekValue || weekInput?.value || weekFallback?.value || '',
      weekdays: [...weekdays].sort((a,b)=>a-b),
      time_start: fd.get('time_start'),
      time_end: fd.get('time_end'),
      slot_minutes: parseInt(fd.get('slot_minutes'),10)
    };
    const btn=form.querySelector('button[type=submit]');
    btn.disabled=true; btn.textContent='Creating...';
    try{
      const r=await fetch('api/create_event.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
      const j=await r.json();
      if(!r.ok) throw new Error(j.error||'Failed');
      msg.innerHTML=`<div class="alert alert-ok">Created! Redirecting... <a href="event.php?c=${j.code}">${j.url}</a></div>`;
      setTimeout(()=> location.href='event.php?c='+j.code, 700);
    }catch(err){ msg.innerHTML=`<div class="alert alert-err">${err.message}</div>`; }
    finally{ btn.disabled=false; btn.textContent='Create & Get Shareable Link \u2192'; }
  });

  renderWeekGrid();
  renderDates();
})();

// event page
(function eventPage(){
  const root=$('#eventRoot');
  if(!root) return;
  const code=root.dataset.code;
  const nameInput=$('#nameInput');
  const submitBtn=$('#submitAvailBtn');
  const msg=$('#eventMsg');
  const heatWrap=$('#heatmapWrap');
  const selectWrap=$('#selectWrap');
  const bestList=$('#bestList');
  const partList=$('#partList');
  const countBadge=$('#countBadge');
  const shareInput=$('#shareInput');
  const titleEl=$('#eventTitle');
  const metaEl=$('#eventMeta');

  let data=null;
  let selected=new Set(); // keys "YYYY-MM-DD HH:MM:SS"
  let isDragging=false;
  let dragModeAdd=true;

  async function load(){
    const r=await fetch('api/get_event.php?c='+encodeURIComponent(code));
    const j=await r.json();
    if(!r.ok) throw new Error(j.error||'load failed');
    data=j;
    titleEl.textContent=j.event.title;
    const ev=j.event;
    const isWeek = ev.mode==='week' && ev.weekLabel;
    const datesStr = isWeek ? `${ev.weekLabel}  -  ${ev.dates.join('  -  ')}` : ev.dates.join('  -  ');
    const badge = isWeek ? '<span class="badge" style="margin-left:6px;vertical-align:middle;background:#e0f2fe;border-color:#bae6fd;color:#0369a1;font-size:11px">WEEK</span>' : '';
    metaEl.innerHTML=`${esc(datesStr)} ${badge} <span class="muted">|</span> ${ev.time_start} - ${ev.time_end}  -  ${ev.slot_minutes} min slots`;
    countBadge.textContent=`${j.totalParticipants} participant${j.totalParticipants!==1?'s':''}`;
    renderHeatmap();
    renderBest();
    renderParticipants();
    renderSelectGrid();
  }

  function escKey(k){
    if (window.CSS && CSS.escape) return CSS.escape(k);
    return k.replace(/["\\]/g, '\\$&');
  }
  function renderSelectGrid(){
    if(!data) return;
    const dates=data.event.dates;
    const slotsByTime={};
    data.allSlots.forEach(s=>{
      const k=s.slot_time;
      if(!slotsByTime[k]) slotsByTime[k]=[];
      slotsByTime[k].push(s);
    });
    const times=Object.keys(slotsByTime).sort();
    // desktop table
    let desk='<div class="sched-desktop"><div class="sched-hint"><i><-> Swipe</i> drag on table to see all dates</div><table class="sched"><thead><tr><th>Time</th>';
    dates.forEach(d=> desk+=`<th>${fmtDate(d)}</th>`);
    desk+='</tr></thead><tbody>';
    times.forEach(t=>{
      desk+=`<tr><td class="timecol">${fmtRange(t,data.event.slot_minutes)}</td>`;
      dates.forEach(d=>{
        const key=d+' '+t;
        const isSel=selected.has(key);
        desk+=`<td data-key="${key}" class="${isSel?'sel':''}" role="gridcell" aria-selected="${isSel?'true':'false'}">${isSel?'\u2713':''}</td>`;
      });
      desk+='</tr>';
    });
    desk+='</tbody></table></div>';
    // mobile stacked cards
    let mob='<div class="sched-mobile">';
    dates.forEach(d=>{
      mob+=`<div class="mday"><div class="mday-head">${fmtDate(d)}<span>${times.length} slots</span></div><div class="mgrid">`;
      times.forEach(t=>{
        const key=d+' '+t;
        const isSel=selected.has(key);
        mob+=`<button type="button" data-key="${key}" class="mslot ${isSel?'sel':''}" aria-pressed="${isSel?'true':'false'}"><span>${fmtRange(t,data.event.slot_minutes)}</span><small>${isSel?'X':'+'}</small></button>`;
      });
      mob+='</div></div>';
    });
    mob+='</div>';
    selectWrap.innerHTML=desk+mob;
    bindSelectGrid();
    $('#selCount').textContent = selected.size;
    requestAnimationFrame(updateScrollHints);
    selectWrap.onscroll = ()=> {
      if(selectWrap.scrollLeft + selectWrap.clientWidth >= selectWrap.scrollWidth - 8) selectWrap.classList.remove('is-scrollable');
      else if(selectWrap.scrollWidth > selectWrap.clientWidth) selectWrap.classList.add('is-scrollable');
    };
  }

  let bindDone=false;
  function bindSelectGrid(){
    const cells = selectWrap.querySelectorAll('[data-key]'); // td + button.mslot
    const supportsPointer = 'onpointerdown' in window;

    if (supportsPointer) {
      cells.forEach(td=>{
        td.addEventListener('pointerdown', e=>{
          if (e.pointerType==='mouse' && e.button!==0) return;
          e.preventDefault();
          isDragging=true;
          const k=td.dataset.key;
          dragModeAdd=!selected.has(k);
          toggle(k, dragModeAdd);
          try{ td.setPointerCapture && td.setPointerCapture(e.pointerId); }catch{}
        });
        if(td.tagName==='TD'){
          td.addEventListener('pointerenter', ()=>{
            if(isDragging) toggle(td.dataset.key, dragModeAdd);
          });
        }
        td.addEventListener('pointerup', ()=>{ isDragging=false; });
        td.addEventListener('pointercancel', ()=>{ isDragging=false; });
      });
      if(!bindDone){
        const endDrag = ()=>{ isDragging=false; };
        document.addEventListener('pointerup', endDrag);
        document.addEventListener('pointercancel', endDrag);
        selectWrap.addEventListener('pointermove', e=>{
          if(!isDragging || e.pointerType!=='touch') return;
          const el = document.elementFromPoint(e.clientX, e.clientY);
          const td = el && el.closest && el.closest('[data-key]');
          if(td && selectWrap.contains(td)) toggle(td.dataset.key, dragModeAdd);
        });
      }
    } else {
      cells.forEach(td=>{
        td.addEventListener('mousedown', e=>{
          e.preventDefault();
          isDragging=true;
          const k=td.dataset.key;
          dragModeAdd=!selected.has(k);
          toggle(k, dragModeAdd);
        });
        if(td.tagName==='TD'){
          td.addEventListener('mouseenter', ()=>{
            if(isDragging) toggle(td.dataset.key, dragModeAdd);
          });
        }
        td.addEventListener('mouseup', ()=>{ isDragging=false; });
        td.addEventListener('touchstart', e=>{
          e.preventDefault();
          const k=td.dataset.key;
          dragModeAdd=!selected.has(k);
          toggle(k, dragModeAdd);
          isDragging=true;
        }, {passive:false});
      });
      if(!bindDone){
        document.addEventListener('mouseup', ()=> isDragging=false);
        document.addEventListener('touchend', ()=> isDragging=false, {passive:true});
        selectWrap.addEventListener('touchmove', e=>{
          if(!isDragging) return;
          const t=e.touches[0];
          if(!t) return;
          const el=document.elementFromPoint(t.clientX, t.clientY);
          const td=el && el.closest && el.closest('[data-key]');
          if(td && selectWrap.contains(td)) toggle(td.dataset.key, dragModeAdd);
        }, {passive:false});
      }
    }
    bindDone=true;
  }

  function toggle(key, force){
    const has=selected.has(key);
    const want = typeof force==='boolean' ? force : !has;
    if(want===has) return;
    if(want) selected.add(key); else selected.delete(key);
    // update all matching elements (both desktop td and mobile button)
    const els = selectWrap.querySelectorAll(`[data-key="${escKey(key)}"]`);
    els.forEach(el=>{
      const isSel = selected.has(key);
      if(el.tagName==='TD'){
        el.className = isSel ? 'sel' : '';
        el.textContent = isSel ? 'X' : '';
        el.setAttribute('aria-selected', isSel?'true':'false');
      } else {
        el.classList.toggle('sel', isSel);
        el.setAttribute('aria-pressed', isSel?'true':'false');
        const small = el.querySelector('small');
        if(small) small.textContent = isSel ? 'X' : '+';
      }
    });
    const sc=$('#selCount');
    if(sc) sc.textContent = selected.size;
  }

  function updateScrollHints(){
    [selectWrap, heatWrap].forEach(el=>{
      if(!el) return;
      const need = el.scrollWidth > el.clientWidth + 2;
      el.classList.toggle('is-scrollable', need);
    });
  }
  // responsive scroll hint: update on resize/orientation
  window.addEventListener('resize', updateScrollHints);
  window.addEventListener('orientationchange', ()=> setTimeout(updateScrollHints, 150));

  function heatClass(c, max){
    if(max===0||c===0) return 'heat0';
    if(c===max) return 'heatmax';
    if(c>=5) return 'heat5';
    if(c>=4) return 'heat4';
    if(c>=3) return 'heat3';
    if(c>=2) return 'heat2';
    return 'heat1';
  }
  function renderHeatmap(){
    const dates=data.event.dates;
    const slotsByTime={};
    data.allSlots.forEach(s=>{
      if(!slotsByTime[s.slot_time]) slotsByTime[s.slot_time]=[];
      slotsByTime[s.slot_time].push(s);
    });
    const times=Object.keys(slotsByTime).sort();
    const max=data.maxCount||0;
    let desk='<div class="sched-desktop"><div class="sched-hint"><i><-> Swipe</i> to see all dates</div><table class="sched"><thead><tr><th>Time</th>';
    dates.forEach(d=> desk+=`<th>${fmtDate(d)}</th>`);
    desk+='</tr></thead><tbody>';
    times.forEach(t=>{
      desk+=`<tr><td class="timecol">${fmtRange(t,data.event.slot_minutes)}</td>`;
      dates.forEach(d=>{
        const key=d+' '+t;
        const c=data.heatmap[key]||0;
        const cls=heatClass(c,max);
        const names=(data.slotToNames[key]||[]).join(', ');
        desk+=`<td class="${cls}" title="${c}/${data.totalParticipants} - ${names}">${c>0?c:''}</td>`;
      });
      desk+='</tr>';
    });
    desk+='</tbody></table></div>';
    let mob='<div class="sched-mobile">';
    dates.forEach(d=>{
      mob+=`<div class="mday"><div class="mday-head">${fmtDate(d)}<span>${max>0? max+' max':''}</span></div><div class="mgrid">`;
      times.forEach(t=>{
        const key=d+' '+t;
        const c=data.heatmap[key]||0;
        const cls=heatClass(c,max);
        const names=(data.slotToNames[key]||[]).join(', ');
        mob+=`<div class="mslot ${cls}" title="${c}/${data.totalParticipants} - ${esc(names)}"><span>${fmtRange(t,data.event.slot_minutes)}</span><span class="mcnt">${c}</span></div>`;
      });
      mob+='</div></div>';
    });
    mob+='</div>';
    heatWrap.innerHTML=desk+mob;
    requestAnimationFrame(updateScrollHints);
    heatWrap.onscroll = ()=> {
      if(heatWrap.scrollLeft + heatWrap.clientWidth >= heatWrap.scrollWidth - 8) heatWrap.classList.remove('is-scrollable');
      else if(heatWrap.scrollWidth > heatWrap.clientWidth) heatWrap.classList.add('is-scrollable');
    };
  }
  function renderBest(){
    if(!data.bestTimes.length){ bestList.innerHTML='<div class="muted">No slots yet - be the first to add availability!</div>'; return; }
    const top = data.bestTimes.slice(0,12);
    bestList.innerHTML = top.map(b=>{
      const hot = b.count===data.maxCount && b.count>0;
      const pct = data.totalParticipants? Math.round(b.count/data.totalParticipants*100):0;
      return `<div class="best-item ${hot?'hot':''}">
        <div class="best-left"><b>${b.date_label} \u00B7 ${b.range}</b><span class="muted">${b.names.length? b.names.join(', '):'No one yet'} - ${pct}%</span></div>
        <div class="best-count">${b.count}/${b.total}</div>
      </div>`;
    }).join('');
  }
  function renderParticipants(){
    if(!data.participants.length){ partList.innerHTML='<span class="muted">No participants yet.</span>'; return; }
    partList.innerHTML=data.participants.map(p=>`<span class="pill-name" data-name="${esc(p.name)}" title="Click to edit ${esc(p.name)}" style="cursor:pointer">${esc(p.name)}  -  ${p.slots.length} slots</span>`).join('');
    // re-evaluate hint if name matches
    preloadForName();
  }

  // name input: preload existing person's slots when typing same name (fixes "can't put name" confusion)
  const nameHint = $('#nameHint');
  function preloadForName(){
    if(!data || !nameInput) return;
    const n = nameInput.value.trim().toLowerCase();
    if(!n){ if(nameHint) nameHint.style.display='none'; return; }
    const p = data.participants.find(x=> x.name.toLowerCase()===n);
    if(p){
      // load their slots into selected without wiping if already editing
      const newSet = new Set(p.slots);
      // only auto-load if user hasn't started selecting (or confirm)
      if(selected.size===0){
        selected = newSet;
        renderSelectGrid();
        if(nameHint){ nameHint.textContent = `Found ${p.name} - loaded ${p.slots.length} existing slots (edit and re-save).`; nameHint.style.display='block'; }
      } else {
        if(nameHint){ nameHint.textContent = `Editing ${p.name} will overwrite their ${p.slots.length} slots.`; nameHint.style.display='block'; }
      }
    } else {
      if(nameHint) nameHint.style.display='none';
    }
  }
  nameInput?.addEventListener('input', preloadForName);
  nameInput?.addEventListener('change', preloadForName);
  nameInput?.addEventListener('blur', preloadForName);
  nameInput?.addEventListener('keydown', e=>{ if(e.key==='Enter'){ e.preventDefault(); submitBtn?.click(); }});
  // make participants clickable to load
  partList?.addEventListener('click', e=>{
    const pill=e.target.closest('.pill-name');
    if(!pill) return;
    const name=pill.dataset.name;
    if(!name) return;
    nameInput.value=name;
    preloadForName();
    nameInput.focus();
    window.scrollTo({top: selectWrap.getBoundingClientRect().top + window.scrollY - 80, behavior:'smooth'});
  });

  // submit
  async function doSubmit(){
    const name=nameInput.value.trim();
    if(!name){ msg.innerHTML='<div class="alert alert-err">Enter your name.</div>'; nameInput.focus(); return; }
    if(selected.size===0){ msg.innerHTML='<div class="alert alert-err">Select at least one slot on the grid (tap or drag).</div>'; return; }
    submitBtn.disabled=true; submitBtn.textContent='Saving...';
    try{
      const r=await fetch('api/submit_availability.php',{method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({code, name, slots:[...selected]})});
      const j=await r.json();
      if(!r.ok) throw new Error(j.error||'submit failed');
      msg.innerHTML=`<div class="alert alert-ok">Saved ${j.count} slots for <b>${esc(name)}</b>! Updating board...</div>`;
      await load();
      // keep selection for that person so they see it saved
    }catch(e){ msg.innerHTML=`<div class="alert alert-err">${esc(e.message)}</div>`; }
    finally{ submitBtn.disabled=false; submitBtn.textContent='Save My Availability'; }
  }
  submitBtn?.addEventListener('click', doSubmit);

  $('#copyLinkBtn')?.addEventListener('click', async ()=>{
    try{ await navigator.clipboard.writeText(shareInput.value); $('#copyLinkBtn').textContent='Copied!'; setTimeout(()=> $('#copyLinkBtn').textContent='Copy Link',1500);}catch{ shareInput.select(); document.execCommand('copy');}
  });
  $('#clearSelBtn')?.addEventListener('click', ()=>{ selected.clear(); renderSelectGrid(); if(nameHint) nameHint.style.display='none'; });

  function fmtDate(d){ const dt=new Date(d+'T00:00:00'); return dt.toLocaleDateString('en-US',{month:'short',day:'numeric',weekday:'short'}); }
  function fmtRange(t, mins){ const a=new Date('2000-01-01T'+t); const b=new Date(a.getTime()+mins*60000); const f=x=>x.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'}); return f(a)+' - '+f(b); }
  function esc(s){ return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  load().catch(e=>{ root.innerHTML=`<div class="alert alert-err">${esc(e.message)}</div><p><a href="index.php">\u2190 Create a new event</a></p>`; });
})();
