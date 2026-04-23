/* ============================================================
   clientLion.js — LionRDV Espace Propriétaire
   Gère : navigation, filtres RDV, thème, langue, previews
   ============================================================ */

/* ── Filtre des RDV par statut ────────────────────────────
   Affecte : la liste des rendez-vous dans "Mes RDV"
────────────────────────────────────────────────────────── */
function filterRdv(status, btn) {
  document.querySelectorAll('.cl-filter-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  document.querySelectorAll('.cl-rdv-item').forEach(item => {
    if (status === 'all' || item.dataset.status === status) {
      item.style.display = '';
    } else {
      item.style.display = 'none';
    }
  });
}

/* ── Mise à jour des messages WhatsApp avec les données client
   Affecte : les modèles de messages sur la page WhatsApp
────────────────────────────────────────────────────────── */
function updateWaTemplates() {
  const sel = document.getElementById('wa-client-select');
  if (!sel) return;

  const opt  = sel.options[sel.selectedIndex];
  const name = opt?.dataset?.name  || '';
  const date = opt?.dataset?.date  || '';
  const time = opt?.dataset?.time  || '';
  const svc  = opt?.dataset?.svc   || '';
  const wa   = sel.value           || '';

  document.querySelectorAll('.cl-wa-msg').forEach(el => {
    const template = el.dataset.template || el.textContent;
    el.textContent = template
      .replace(/{name}/g, name)
      .replace(/{date}/g, date)
      .replace(/{time}/g, time)
      .replace(/{svc}/g,  svc);
  });

  document.querySelectorAll('.cl-wa-btn').forEach(btn => {
    const tmpl = btn.previousElementSibling;
    if (!tmpl) return;
    const msg = tmpl.textContent;
    if (wa) {
      btn.href = 'https://wa.me/237' + wa + '?text=' + encodeURIComponent(msg);
    } else {
      btn.href = '#';
    }
  });
}

/* ── Application du thème clair/sombre ───────────────────
   Affecte : data-theme sur <html> → toutes les CSS variables
────────────────────────────────────────────────────────── */
function applyTheme(dark) {
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
}

/* ── Changement de langue FR/EN ──────────────────────────
   Affecte : tous les éléments avec attribut data-fr/data-en
────────────────────────────────────────────────────────── */
function setLang(lang, btn) {
  /* Mettre à jour les boutons FR/EN */
  document.querySelectorAll('.cl-lang-btn').forEach(b => b.classList.remove('on'));
  if (btn) btn.classList.add('on');

  /* Traduire tous les éléments marqués */
  const attr = 'data-' + lang;
  document.querySelectorAll('[' + attr + ']').forEach(el => {
    const val = el.getAttribute(attr);
    if (val) el.textContent = val;
  });

  /* Mettre à jour le sélecteur de langue dans le profil si présent */
  const langSel = document.querySelector('select[name="language_pref"]');
  if (langSel) langSel.value = lang;
}

/* ── Toggle du sidebar mobile ─────────────────────────────
   Affecte : sidebar sur mobile (class .open)
────────────────────────────────────────────────────────── */
function toggleSidebar() {
  document.getElementById('cl-sidebar')?.classList.toggle('open');
}

/* Fermer le sidebar en cliquant en dehors */
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('cl-sidebar');
  const menuBtn = document.querySelector('.cl-menu-btn');
  if (!sidebar || !menuBtn) return;
  if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
    sidebar.classList.remove('open');
  }
});

/* ── Navigation entre pages ──────────────────────────────
   Affecte : visibilité des .cl-page
────────────────────────────────────────────────────────── */
function goPage(id, navEl) {
  /* Cacher toutes les pages */
  document.querySelectorAll('.cl-page').forEach(p => p.classList.add('hidden'));

  /* Désactiver tous les items de nav */
  document.querySelectorAll('.cl-nav-item').forEach(n => n.classList.remove('active'));

  /* Afficher la page cible */
  const page = document.getElementById('page-' + id);
  if (page) page.classList.remove('hidden');

  /* Activer l'item de nav */
  if (navEl) navEl.classList.add('active');

  /* Mettre à jour le titre dans la topbar */
  const titles = {
    dashboard:  'Tableau de bord',
    rdv:        'Mes RDV',
    upcoming:   'À venir',
    whatsapp:   'Messages WA',
    hours:      'Disponibilités',
    services:   'Mes services',
    gallery:    'Galerie',
    profile:    'Mon profil',
  };
  const tb = document.getElementById('cl-page-title');
  if (tb) tb.textContent = titles[id] || id;

  /* Remonter en haut de la page */
  window.scrollTo(0, 0);

  /* Fermer le sidebar mobile */
  document.getElementById('cl-sidebar')?.classList.remove('open');

  return false; /* Empêcher la navigation href */
}

/* ── Toggle d'un jour de disponibilité ───────────────────
   Affecte : page Disponibilités et onboarding étape 2
────────────────────────────────────────────────────────── */
function togDay(en, open) {
  const nm     = document.getElementById('dnm-' + en);
  const times  = document.getElementById('dtimes-' + en);
  let   closed = document.getElementById('dclosed-' + en);

  if (nm)    nm.classList.toggle('cl-day-off', !open);
  if (times) times.style.display = open ? '' : 'none';

  if (open && closed) {
    closed.remove();
  } else if (!open && !closed) {
    const s = document.createElement('span');
    s.className = 'cl-day-closed';
    s.id        = 'dclosed-' + en;
    s.textContent = 'Fermé';
    document.getElementById('row-' + en)?.appendChild(s);
  }
}

/* ── Force du mot de passe ───────────────────────────────
   Affecte : barre de force sur les pages password
────────────────────────────────────────────────────────── */
function checkPwdStrength(v) {
  const fill = document.getElementById('pwd-fill');
  const hint = document.getElementById('pwd-hint');
  if (!fill) return;

  let score = 0;
  if (v.length >= 6)  score += 25;
  if (v.length >= 8)  score += 25;
  if (v.length >= 12) score += 20;
  if (/[A-Z]/.test(v)) score += 15;
  if (/[0-9!@#$%]/.test(v)) score += 15;

  const color = score < 40 ? '#E74C3C' : score < 70 ? '#E67E22' : '#059669';
  const label = score < 40 ? 'Trop court' : score < 70 ? 'Moyen' : 'Fort ✓';

  fill.style.width      = Math.min(score, 100) + '%';
  fill.style.background = color;
  if (hint) { hint.textContent = label; hint.style.color = color; }
}

/* ── Toggle visibilité d'une carte service ───────────────
   Affecte : opacité de la carte service (actif/inactif)
────────────────────────────────────────────────────────── */
function toggleSvcCard(id, active) {
  const card = document.getElementById('svc-' + id);
  if (card) card.classList.toggle('cl-svc-inactive', !active);
}

/* ── Prévisualisation avatar ─────────────────────────────
   Affecte : cercle avatar dans profil et onboarding
────────────────────────────────────────────────────────── */
function previewAvatar(input, imgId, initId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = function(e) {
    const img  = document.getElementById(imgId  || 'av-img');
    const init = document.getElementById(initId || 'av-initials');
    if (img)  { img.src = e.target.result; img.style.display = 'block'; }
    if (init) init.style.display = 'none';
  };
  reader.readAsDataURL(file);
}

/* ── Prévisualisation galerie (onboarding) ───────────────
   Affecte : grille de preview dans l'étape galerie
────────────────────────────────────────────────────────── */
function previewGallery(input) {
  const grid = document.getElementById('gal-preview-grid');
  if (!grid) return;
  grid.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const reader = new FileReader();
    reader.onload = function(e) {
      const div = document.createElement('div');
      div.className = 'cl-gal-preview-item';
      div.innerHTML = '<img src="' + e.target.result + '" alt="">';
      grid.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

/* ── Passer une étape d'onboarding ───────────────────────
   Affecte : progression de l'onboarding
────────────────────────────────────────────────────────── */
function skipStep() {
  const form = document.createElement('form');
  form.method = 'POST';
  const input = document.createElement('input');
  input.type  = 'hidden';
  input.name  = 'action';
  input.value = 'onboard_gallery';
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}

/* ── Envoyer un message WhatsApp ─────────────────────────
   Affecte : bouton "Envoyer via WhatsApp" sur les templates
────────────────────────────────────────────────────────── */
function sendWa(template, link) {
  const sel = document.getElementById('wa-client-select');
  if (!sel || !sel.value) {
    alert('Veuillez sélectionner un client d\'abord.');
    return false;
  }
  const opt  = sel.options[sel.selectedIndex];
  const msg  = template
    .replace(/{name}/g, opt.dataset.name || '')
    .replace(/{date}/g, opt.dataset.date || '')
    .replace(/{time}/g, opt.dataset.time || '')
    .replace(/{svc}/g,  opt.dataset.svc  || '');
  link.href = 'https://wa.me/237' + sel.value + '?text=' + encodeURIComponent(msg);
  return true;
}

/* ── Auto-cacher les alertes flottantes ──────────────────
   Affecte : messages de succès/erreur qui apparaissent
────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.cl-alert-floating').forEach(el => {
    setTimeout(() => {
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 3500);
  });
});