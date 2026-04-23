/* ============================================================
   reserver.js — LionRDV Booking Page
   BIZ, SLUG, DEFAULT_LANG injected by PHP
============================================================ */

/* ── STATE ──────────────────────────────────────────────── */
var selDay    = null;
var selSlot   = null;
var selDate   = null;  /* YYYY-MM-DD */
var curMonth  = new Date().getMonth();
var curYear   = new Date().getFullYear();
var takenSlots= [];    /* filled by AJAX when day selected */
var lang      = DEFAULT_LANG || 'fr';

var MONTHS_FR = ['Janvier','Février','Mars','Avril','Mai','Juin',
                 'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
var MONTHS_EN = ['January','February','March','April','May','June',
                 'July','August','September','October','November','December'];
var MS_FR     = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
var MS_EN     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

/* ── HELPERS ─────────────────────────────────────────────── */
function monthName(m)      { return lang === 'en' ? MONTHS_EN[m] : MONTHS_FR[m]; }
function monthShort(m)     { return lang === 'en' ? MS_EN[m]     : MS_FR[m]; }
function pad2(n)            { return String(n).padStart(2, '0'); }
function formatDate(y,m,d)  { return y + '-' + pad2(m+1) + '-' + pad2(d); }
function fmtDisplay(y,m,d)  { return monthShort(m) + ' ' + d + ' ' + y; }

function $(id) { return document.getElementById(id); }

function showToast(msg) {
  var t = document.querySelector('.rsv-toast');
  if (!t) {
    t = document.createElement('div');
    t.className = 'rsv-toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 2800);
}

/* ── LANGUAGE ─────────────────────────────────────────────── */
function applyLang(l) {
  lang = l;
  document.querySelectorAll('[data-fr]').forEach(function(el) {
    var v = l === 'en' ? el.dataset.en : el.dataset.fr;
    if (v !== undefined) el.innerHTML = v;
  });
  document.querySelectorAll('[data-ph-fr]').forEach(function(el) {
    el.placeholder = l === 'en' ? el.dataset.phEn : el.dataset.phFr;
  });
  renderCalendar();
}

/* ── OPEN DAYS MAP ────────────────────────────────────────── */
var openDays = {}; /* { 0:false, 1:{start:'08:00',end:'18:00'}, ... } */
(function buildOpenDays() {
  var dayMap = {
    'Sunday':0,'Monday':1,'Tuesday':2,'Wednesday':3,
    'Thursday':4,'Friday':5,'Saturday':6
  };
  BIZ.availability.forEach(function(slot) {
    var idx = dayMap[slot.day_en];
    if (idx !== undefined) {
      openDays[idx] = slot.open
        ? { start: slot.start, end: slot.end }
        : false;
    }
  });
})();

/* ── SLOT GENERATION ─────────────────────────────────────── */
function generateSlots(start, end) {
  if (!start || !end) return [];
  var slots = [];
  var cur  = toMinutes(start);
  var stop = toMinutes(end);
  var interval = 45; /* minutes — could come from BIZ.slot_duration */
  while (cur < stop) {
    slots.push(fromMinutes(cur));
    cur += interval;
  }
  return slots;
}

function toMinutes(t) {
  var p = t.split(':');
  return parseInt(p[0]) * 60 + parseInt(p[1]);
}

function fromMinutes(m) {
  return pad2(Math.floor(m/60)) + ':' + pad2(m % 60);
}

/* ── CALENDAR ─────────────────────────────────────────────── */
function renderCalendar() {
  var grid  = $('cal-grid');
  var label = $('cal-month-lbl');
  if (!grid || !label) return;

  label.textContent = monthName(curMonth) + ' ' + curYear;

  /* keep day-of-week headers (first 7 children) */
  while (grid.children.length > 7) grid.removeChild(grid.lastChild);

  var firstDow = new Date(curYear, curMonth, 1).getDay(); /* 0=Sun */
  var offset   = (firstDow === 0) ? 6 : firstDow - 1;    /* Mon=0 */
  var daysInMonth = new Date(curYear, curMonth + 1, 0).getDate();
  var today    = new Date();
  var todayStr = formatDate(today.getFullYear(), today.getMonth(), today.getDate());

  /* Empty cells before 1st */
  for (var i = 0; i < offset; i++) {
    var empty = document.createElement('div');
    empty.className = 'rsv-cal-day empty';
    grid.appendChild(empty);
  }

  for (var d = 1; d <= daysInMonth; d++) {
    var dateStr = formatDate(curYear, curMonth, d);
    var dt      = new Date(curYear, curMonth, d);
    var dow     = dt.getDay();
    var past    = dateStr < todayStr;
    var isOpen  = openDays[dow] !== false && openDays[dow] !== undefined;
    var el      = document.createElement('div');

    var cls = 'rsv-cal-day';
    if (past || !isOpen) cls += past ? ' past' : ' off';
    if (dateStr === todayStr && isOpen && !past) cls += ' today';
    if (selDate === dateStr) cls += ' on';

    el.className = cls;
    el.innerHTML = '<div>' + d + '</div>' + (selDate === dateStr ? '<div class="rsv-cal-dot"></div>' : '');

    if (!past && isOpen) {
      (function(day, ds, elem) {
        elem.addEventListener('click', function() {
          /* deselect old */
          document.querySelectorAll('.rsv-cal-day.on').forEach(function(x) {
            x.classList.remove('on');
            var dot = x.querySelector('.rsv-cal-dot');
            if (dot) dot.remove();
          });
          elem.classList.add('on');
          if (!elem.querySelector('.rsv-cal-dot')) {
            var dot = document.createElement('div');
            dot.className = 'rsv-cal-dot';
            elem.appendChild(dot);
          }

          selDay  = day;
          selDate = ds;
          selSlot = null;

          var badge = $('date-badge');
          if (badge) { badge.textContent = monthShort(curMonth) + ' ' + day; badge.style.display = 'inline'; }

          var slotLbl = $('slot-day-lbl');
          if (slotLbl) { slotLbl.textContent = monthShort(curMonth) + ' ' + day; slotLbl.style.display = 'inline'; }

          fetchTakenSlots(ds, openDays[new Date(curYear,curMonth,day).getDay()]);
          updateSummary();
        });
      })(d, dateStr, el);
    }

    grid.appendChild(el);
  }
}

function changeMonth(dir) {
  curMonth += dir;
  if (curMonth > 11) { curMonth = 0; curYear++; }
  if (curMonth < 0)  { curMonth = 11; curYear--; }
  selDay = null; selDate = null; selSlot = null;
  var sg = $('slots-grid'); if (sg) sg.style.display = 'none';
  var nm = $('no-slots-msg'); if (nm) nm.style.display = 'block';
  var db = $('date-badge'); if (db) db.style.display = 'none';
  renderCalendar();
  updateSummary();
}

/* ── SLOTS ────────────────────────────────────────────────── */
function fetchTakenSlots(dateStr, dayInfo) {
  var sg  = $('slots-grid');
  var nm  = $('no-slots-msg');
  if (!sg || !nm) return;

  sg.style.display = 'none';
  nm.style.display = 'block';
  nm.textContent   = lang === 'en' ? 'Loading...' : 'Chargement...';

  var fd = new FormData();
  fd.append('action', 'get_slots');
  fd.append('date',   dateStr);

  fetch('reserver.php?slug=' + SLUG, { method:'POST', body:fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      takenSlots = data.taken || [];
      renderSlots(dayInfo);
    })
    .catch(function() {
      takenSlots = [];
      renderSlots(dayInfo);
    });
}

function renderSlots(dayInfo) {
  var sg = $('slots-grid');
  var nm = $('no-slots-msg');
  if (!sg || !nm || !dayInfo) return;

  var all = generateSlots(dayInfo.start, dayInfo.end);
  sg.innerHTML = '';

  all.forEach(function(time) {
    var el  = document.createElement('div');
    var isTaken = takenSlots.includes(time);
    el.className = 'rsv-slot' + (isTaken ? ' taken' : '');
    el.textContent = time.replace(':', 'h');

    if (!isTaken) {
      el.addEventListener('click', function() {
        document.querySelectorAll('.rsv-slot:not(.taken)').forEach(function(s) {
          s.classList.remove('on');
        });
        el.classList.add('on');
        selSlot = time;
        updateSummary();
      });
    }
    sg.appendChild(el);
  });

  sg.style.display = 'grid';
  nm.style.display = 'none';
}

/* ── SERVICES ─────────────────────────────────────────────── */
function toggleSvc(el) {
  el.classList.toggle('on');
  var count = document.querySelectorAll('.rsv-svc-item.on').length;
  var badge = $('svc-badge');
  if (badge) {
    badge.style.display = count > 0 ? 'inline' : 'none';
    badge.textContent   = count + (lang === 'en'
      ? (' service' + (count > 1 ? 's' : '') + ' selected')
      : (' sélectionné' + (count > 1 ? 's' : '')));
  }
  updateSummary();
}

/* ── SUMMARY ──────────────────────────────────────────────── */
function updateSummary() {
  var svcs  = [];
  var total = 0;

  document.querySelectorAll('.rsv-svc-item.on').forEach(function(s) {
    svcs.push(s.dataset.name);
    total += parseInt(s.dataset.price || 0);
  });

  var sSvc   = $('s-svc');
  var sDate  = $('s-date');
  var sHeure = $('s-heure');
  var sTotal = $('s-total');

  if (sSvc)   sSvc.textContent   = svcs.length ? svcs.join(', ') : '—';
  if (sDate)  sDate.textContent  = selDate  ? fmtDisplay(curYear, curMonth, selDay) : '—';
  if (sHeure) sHeure.textContent = selSlot  ? selSlot.replace(':', 'h') : '—';
  if (sTotal) sTotal.textContent = total > 0 ? total.toLocaleString() + ' FCFA' : '—';
}

/* ── SUBMIT RDV ───────────────────────────────────────────── */
function submitRdv() {
  var prenom = ($('inp-prenom') || {}).value.trim();
  var nom    = ($('inp-nom')    || {}).value.trim();
  var wa     = ($('inp-wa')     || {}).value.trim();
  var svcs   = [];

  document.querySelectorAll('.rsv-svc-item.on').forEach(function(s) {
    svcs.push(s.dataset.name);
  });

  /* Validation */
  var err = '';
  if (!prenom || !nom)  err = lang === 'en' ? 'Please enter your name.' : 'Veuillez entrer votre prénom et nom.';
  else if (!wa)         err = lang === 'en' ? 'Please enter your WhatsApp number.' : 'Veuillez entrer votre numéro WhatsApp.';
  else if (svcs.length === 0) err = lang === 'en' ? 'Please select at least one service.' : 'Veuillez choisir au moins un service.';
  else if (!selDate)    err = lang === 'en' ? 'Please select a date.' : 'Veuillez choisir une date.';
  else if (!selSlot)    err = lang === 'en' ? 'Please select a time slot.' : 'Veuillez choisir un créneau.';

  var errEl = $('form-error');
  if (err) {
    if (errEl) { errEl.textContent = err; errEl.style.display = 'block'; }
    return;
  }
  if (errEl) errEl.style.display = 'none';

  var btn = $('confirm-btn');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }

  var fd = new FormData();
  fd.append('action',   'book');
  fd.append('prenom',   prenom);
  fd.append('nom',      nom);
  fd.append('whatsapp', wa);
  fd.append('rdv_date', selDate);
  fd.append('rdv_time', selSlot);
  svcs.forEach(function(s) { fd.append('services[]', s); });

  fetch('reserver.php?slug=' + SLUG, { method:'POST', body:fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        showConfirmScreen(data.rdv, prenom, nom, wa, svcs);
        /* Save to localStorage */
        try {
          var key = 'lionrdv_' + SLUG + '_' + wa + '_' + nom.toLowerCase();
          var saved = JSON.parse(localStorage.getItem(key) || '[]');
          saved.push(data.rdv);
          localStorage.setItem(key, JSON.stringify(saved));
        } catch(e) {}
      } else {
        if (errEl) { errEl.textContent = data.msg || 'Erreur.'; errEl.style.display = 'block'; }
        if (btn)   { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i> <span>' + (lang === 'en' ? 'Confirm my booking' : 'Confirmer mon RDV') + '</span>'; }
      }
    })
    .catch(function() {
      if (errEl) { errEl.textContent = 'Erreur réseau. Réessayez.'; errEl.style.display = 'block'; }
      if (btn)   { btn.disabled = false; }
    });
}

/* ── SHOW CONFIRMATION SCREEN ─────────────────────────────── */
function showConfirmScreen(rdv, prenom, nom, wa, svcs) {
  var main   = $('rsv-main');
  var screen = $('confirm-screen');
  if (main)   main.style.display   = 'none';
  if (screen) screen.style.display = 'block';

  var cWa  = $('conf-wa');
  var cNom = $('conf-nom');
  if (cWa)  cWa.textContent  = '+237 ' + wa;
  if (cNom) cNom.textContent = prenom + ' ' + nom;

  /* Deadline note */
  var dl = $('conf-deadline');
  if (dl && rdv.cancel_before) {
    var d = new Date(rdv.cancel_before.replace(' ', 'T'));
    var opts = { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' };
    var dlStr = d.toLocaleDateString(lang === 'en' ? 'en-GB' : 'fr-FR', opts);
    dl.innerHTML = (lang === 'en'
      ? '<strong>Cancellation deadline: ' + dlStr + '</strong><br>We recommend cancelling <strong>24h in advance</strong> if possible.'
      : '<strong>Annulation possible jusqu\'au ' + dlStr + '</strong><br>Nous recommandons d\'annuler <strong>24h à l\'avance</strong> si possible.');
  }

  /* Summary */
  var cs = $('conf-summary');
  if (cs) {
    cs.innerHTML =
      '<div class="rsv-sum-row"><span>' + (lang==='en'?'Name':'Nom') + '</span><span>' + prenom + ' ' + nom + '</span></div>' +
      '<div class="rsv-sum-row"><span>Service</span><span>' + svcs.join(', ') + '</span></div>' +
      '<div class="rsv-sum-row"><span>Date</span><span>' + fmtDisplay(curYear, curMonth, selDay) + '</span></div>' +
      '<div class="rsv-sum-row"><span>' + (lang==='en'?'Time':'Heure') + '</span><span>' + selSlot.replace(':','h') + '</span></div>' +
      '<div class="rsv-sum-row"><span>Business</span><span>' + BIZ.name + '</span></div>';
  }

  /* WhatsApp button */
  var waBtn = $('conf-wa-btn');
  if (waBtn && BIZ.whatsapp) {
    var waNum = BIZ.whatsapp.replace(/\D/g, '');
    var msg   = 'Bonjour ' + BIZ.name + ', j\'ai pris un RDV :\n' +
                '- Nom: ' + prenom + ' ' + nom + '\n' +
                '- Service: ' + svcs.join(', ') + '\n' +
                '- Date: ' + fmtDisplay(curYear, curMonth, selDay) + ' à ' + selSlot + '\n' +
                '- WhatsApp: +237' + wa;
    waBtn.onclick = function() {
      window.open('https://wa.me/' + waNum + '?text=' + encodeURIComponent(msg), '_blank');
    };
  }
}

/* ── LOOKUP ───────────────────────────────────────────────── */
function doLookup() {
  var wa  = ($('lookup-wa')  || {}).value.trim();
  var nom = ($('lookup-nom') || {}).value.trim();
  var res = $('lookup-result');

  if (!wa || !nom) {
    showToast(lang === 'en' ? 'Enter your WhatsApp and name.' : 'Entrez votre WhatsApp et nom.');
    return;
  }

  if (res) { res.style.display = 'block'; res.innerHTML = '<div style="font-size:12px;color:#888;text-align:center;padding:10px;">' + (lang==='en'?'Searching...':'Recherche en cours...') + '</div>'; }

  var fd = new FormData();
  fd.append('action',    'lookup');
  fd.append('whatsapp',  wa);
  fd.append('nom',       nom);

  fetch('reserver.php?slug=' + SLUG, { method:'POST', body:fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      renderLookupResult(data.rdvs || [], wa, nom, res);
    })
    .catch(function() {
      if (res) res.innerHTML = '<div class="rsv-error">Erreur réseau.</div>';
    });
}

function renderLookupResult(rdvs, wa, nom, container) {
  if (!container) return;

  if (rdvs.length === 0) {
    container.innerHTML = '<div class="rsv-error">' +
      (lang === 'en'
        ? 'No booking found for this WhatsApp + name.'
        : 'Aucun RDV trouvé pour ce WhatsApp + nom.') +
      '</div>';
    return;
  }

  var html = '';
  rdvs.forEach(function(rdv) {
    if (rdv.cancellable) {
      html += '<div class="rsv-found-card">' +
        '<div class="rsv-found-title"><i class="fa-solid fa-circle-check"></i>' +
        (lang==='en'?'Booking found':'RDV trouvé') + ' — ' + rdv.prenom + ' ' + rdv.nom + '</div>' +
        '<div class="rsv-summary">' +
          '<div class="rsv-sum-row"><span>Service</span><span>' + rdv.services.join(', ') + '</span></div>' +
          '<div class="rsv-sum-row"><span>Date</span><span>' + rdv.date + '</span></div>' +
          '<div class="rsv-sum-row"><span>' + (lang==='en'?'Time':'Heure') + '</span><span>' + rdv.time + '</span></div>' +
        '</div>' +
        '<div class="rsv-deadline-note">' +
          '<strong>' + (lang==='en'?'Cancel by: ':'Annulation avant : ') + rdv.cancel_before_fmt + '</strong><br>' +
          (lang==='en'?'We recommend cancelling 24h in advance.':'Nous recommandons d\'annuler 24h à l\'avance.') +
        '</div>' +
        '<button class="rsv-cancel-btn" onclick="doCancel(\'' + rdv.id + '\',\'' + wa + '\',\'' + nom + '\')">' +
          (lang==='en'?'Cancel this booking':'Annuler ce rendez-vous') + '</button>' +
        '<div style="font-size:10px;color:#888;text-align:center;margin-top:8px;">' +
          (lang==='en'?'To modify, cancel and create a new booking.':'Pour modifier, annulez et créez un nouveau RDV.') +
        '</div>' +
        '</div>';
    } else {
      html += '<div class="rsv-expired-card">' +
        '<div style="font-size:12px;font-weight:700;margin-bottom:6px;"><i class="fa-solid fa-clock" style="margin-right:5px;"></i>' +
          (lang==='en'?'Cancellation deadline passed':'Délai d\'annulation dépassé') + '</div>' +
        '<div>Service: <strong>' + rdv.services.join(', ') + '</strong></div>' +
        '<div>Date: <strong>' + rdv.date + ' à ' + rdv.time + '</strong></div>' +
        '<div style="margin-top:7px;">' + (lang==='en'?'It is no longer possible to cancel online. Contact the business on WhatsApp.':'Il n\'est plus possible d\'annuler en ligne. Contactez le business sur WhatsApp.') + '</div>' +
        '<a href="https://wa.me/' + BIZ.whatsapp.replace(/\D/g,'') + '" target="_blank" style="display:flex;align-items:center;justify-content:center;gap:7px;margin-top:10px;padding:10px;background:#25D366;color:#fff;border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;">' +
          '<i class="fa-brands fa-whatsapp"></i> WhatsApp ' + BIZ.name + '</a>' +
        '</div>';
    }
  });

  container.innerHTML = html;
}

/* ── CANCEL ───────────────────────────────────────────────── */
function doCancel(rdvId, wa, nom) {
  if (!confirm(lang === 'en' ? 'Cancel this booking?' : 'Annuler ce rendez-vous ?')) return;

  var fd = new FormData();
  fd.append('action',   'cancel');
  fd.append('rdv_id',   rdvId);
  fd.append('whatsapp', wa);
  fd.append('nom',      nom);

  fetch('reserver.php?slug=' + SLUG, { method:'POST', body:fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.ok) {
        showToast(lang === 'en' ? 'Booking cancelled.' : 'RDV annulé.');
        doLookup(); /* refresh lookup result */
        /* Remove from localStorage */
        try {
          var key  = 'lionrdv_' + SLUG + '_' + wa + '_' + nom.toLowerCase();
          var list = JSON.parse(localStorage.getItem(key) || '[]');
          list = list.filter(function(r) { return r.id !== rdvId; });
          localStorage.setItem(key, JSON.stringify(list));
        } catch(e) {}
      } else {
        showToast(data.msg || (lang === 'en' ? 'Error.' : 'Erreur.'));
      }
    });
}

/* ── RESET FORM ───────────────────────────────────────────── */
function resetForm() {
  selDay = null; selSlot = null; selDate = null;
  var main   = $('rsv-main');
  var screen = $('confirm-screen');
  if (main)   main.style.display   = 'block';
  if (screen) screen.style.display = 'none';
  /* reset service selections */
  document.querySelectorAll('.rsv-svc-item.on').forEach(function(s) { s.classList.remove('on'); });
  /* scroll to top */
  window.scrollTo({ top: 0, behavior: 'smooth' });
  renderCalendar();
  updateSummary();
}

/* ── INIT ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  applyLang(DEFAULT_LANG || 'fr');
  renderCalendar();
  updateSummary();

  /* Wire up service items from BIZ data */
  document.querySelectorAll('.rsv-svc-item').forEach(function(item) {
    /* onclick already set inline in PHP */
  });
});