/* ============================================================
   clientLion.js — LionRDV Owner Dashboard
   Handles: navigation, language, theme, modals, toast, toggles
   ============================================================ */

/* ── LANGUAGE ─────────────────────────────────────────── */
let currentLang = 'fr';

function setLang(lang, btn) {
  currentLang = lang;

  // update lang buttons
  document.querySelectorAll('.lf-lb, .lang-btn').forEach(b => b.classList.remove('active', 'on'));
  if (btn) btn.classList.add('active');

  // update all [data-fr] / [data-en] elements
  document.querySelectorAll('[data-fr]').forEach(el => {
    const val = lang === 'fr' ? el.dataset.fr : el.dataset.en;
    if (val !== undefined) {
      // if it's an input placeholder
      if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
        el.placeholder = val;
      } else {
        el.textContent = val;
      }
    }
  });
}

/* ── PAGE NAVIGATION ──────────────────────────────────── */
function showPage(pageId, clickedLink) {
  // hide all pages
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));

  // show target
  const target = document.getElementById('page-' + pageId);
  if (target) target.classList.add('active');

  // update sidebar active state
  document.querySelectorAll('.ds-nav-item').forEach(item => item.classList.remove('active'));
  if (clickedLink) {
    // handle both element passed directly and event target
    const el = clickedLink.target ? clickedLink.currentTarget : clickedLink;
    el.classList.add('active');
  }

  // update topbar title
  const titles = {
    dashboard: { fr: 'Dashboard',          en: 'Dashboard' },
    rdv:       { fr: 'Mes réservations',   en: 'My bookings' },
    avail:     { fr: 'Disponibilités',     en: 'Availability' },
    services:  { fr: 'Mes services',       en: 'My services' },
    qr:        { fr: 'QR Code & Mon lien', en: 'QR Code & My link' },
    profile:   { fr: 'Mon profil',         en: 'My profile' },
  };

  const titleEl = document.getElementById('page-title');
  if (titleEl && titles[pageId]) {
    titleEl.textContent = titles[pageId][currentLang] || titles[pageId]['fr'];
    titleEl.dataset.fr = titles[pageId].fr;
    titleEl.dataset.en = titles[pageId].en;
  }

  // scroll to top
  const main = document.querySelector('.dash-main');
  if (main) main.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── CANCEL MODAL ─────────────────────────────────────── */
let pendingCancelId = null;

function cancelRdv(id, name) {
  pendingCancelId = id;
  const nameEl = document.getElementById('modal-name');
  if (nameEl) nameEl.textContent = name;
  const modal = document.getElementById('cancel-modal');
  if (modal) modal.classList.add('open');
}

function closeModal() {
  const modal = document.getElementById('cancel-modal');
  if (modal) modal.classList.remove('open');
  pendingCancelId = null;
}

document.addEventListener('DOMContentLoaded', function () {

  // confirm cancel
  const confirmBtn = document.getElementById('modal-confirm');
  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      if (pendingCancelId !== null) {
        // find and remove the rdv row
        const rows = document.querySelectorAll('.rdv-row');
        rows.forEach(row => {
          const cancelBtn = row.querySelector('.rdv-btn.cancel');
          if (cancelBtn) {
            const onclickAttr = cancelBtn.getAttribute('onclick');
            if (onclickAttr && onclickAttr.includes('cancelRdv(' + pendingCancelId + ',')) {
              row.style.transition = 'opacity 0.3s, height 0.3s';
              row.style.opacity = '0';
              setTimeout(() => row.remove(), 300);
            }
          }
        });
        closeModal();
        showToast(currentLang === 'fr' ? 'RDV annulé avec succès' : 'Booking cancelled successfully', 'danger');
      }
    });
  }

  // close modal on overlay click
  const overlay = document.getElementById('cancel-modal');
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
  }

  // close modal on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

  /* ── FILTER TABS ── */
  document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      // In production: filter rows by date. For now just visual.
    });
  });

  /* ── DAY TOGGLES (availability) ── */
  document.querySelectorAll('.day-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
      const isOn = this.classList.contains('on');
      this.classList.toggle('on', !isOn);
      this.classList.toggle('off', isOn);
      const row = this.closest('.day-row');
      if (row) row.classList.toggle('closed', isOn);
      updatePhonePreview();
    });
  });

  /* ── TIME INPUTS ── */
  document.querySelectorAll('.time-input').forEach(input => {
    input.addEventListener('change', updatePhonePreview);
  });

  /* ── LOGO FILE INPUT ── */
  const logoFile = document.getElementById('logo-file');
  if (logoFile) {
    logoFile.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const preview = document.querySelector('.biz-logo-preview');
          if (preview) {
            preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">`;
          }
        };
        reader.readAsDataURL(this.files[0]);
      }
    });
  }

  /* ── PASSWORD TOGGLE ── */
  // handled by togglePwd() below

});

/* ── TOAST ─────────────────────────────────────────────── */
let toastTimer = null;

function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  if (!toast) return;

  const colors = {
    success: '#059669',
    danger:  '#DC2626',
    info:    '#C9A84C',
  };

  toast.textContent = message;
  toast.style.background = colors[type] || colors.info;
  toast.classList.add('show');

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}

/* ── COPY LINK ─────────────────────────────────────────── */
function copyLink() {
  const linkEl = document.querySelector('.qr-link');
  if (!linkEl) return;

  const text = 'https://' + linkEl.textContent.trim();
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      showToast(currentLang === 'fr' ? 'Lien copié !' : 'Link copied!', 'success');
    });
  } else {
    // fallback
    const ta = document.createElement('textarea');
    ta.value = text;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast(currentLang === 'fr' ? 'Lien copié !' : 'Link copied!', 'success');
  }
}

/* ── THEME SELECTOR ────────────────────────────────────── */
function selectTheme(dot, color, bg, name) {
  // update active dot
  document.querySelectorAll('.theme-cdot').forEach(d => d.classList.remove('active'));
  dot.classList.add('active');

  // update color indicator
  const colorDot = document.getElementById('current-color-dot');
  if (colorDot) colorDot.style.background = color;

  const colorName = document.getElementById('current-color-name');
  if (colorName) colorName.textContent = name;

  // update button preview
  const btnPreview = document.getElementById('btn-preview');
  if (btnPreview) btnPreview.style.background = color;

  showToast(
    (currentLang === 'fr' ? 'Thème appliqué : ' : 'Theme applied: ') + name,
    'info'
  );
}

/* ── AVAILABILITY SAVE ─────────────────────────────────── */
function saveAvail() {
  showToast(
    currentLang === 'fr' ? 'Disponibilités enregistrées !' : 'Availability saved!',
    'success'
  );
}

/* ── PHONE PREVIEW UPDATE ──────────────────────────────── */
function updatePhonePreview() {
  const dayRows     = document.querySelectorAll('.days-list .day-row');
  const phDays      = document.querySelectorAll('.ph-day');
  const phDayTimes  = document.querySelectorAll('.ph-day-time');
  const phDayClosed = document.querySelectorAll('.ph-day-closed-txt');

  dayRows.forEach((row, i) => {
    const isClosed = row.classList.contains('closed');
    const phDay    = phDays[i];
    if (!phDay) return;

    phDay.classList.toggle('ph-day-closed', isClosed);

    const timeInputs = row.querySelectorAll('.time-input');
    const phTime     = phDayTimes[i];
    const phClosed   = phDayClosed[i];

    if (phTime && timeInputs.length >= 2) {
      phTime.textContent  = timeInputs[0].value + ' – ' + timeInputs[1].value;
      phTime.style.display = isClosed ? 'none' : '';
    }
    if (phClosed) {
      phClosed.style.display = isClosed ? '' : 'none';
    }
  });
}

/* ── ADD SERVICE ───────────────────────────────────────── */
function showAddService() {
  showToast(
    currentLang === 'fr' ? 'Fonctionnalité bientôt disponible' : 'Feature coming soon',
    'info'
  );
}

/* ── PASSWORD TOGGLE ───────────────────────────────────── */
function togglePwd() {
  const pwd     = document.getElementById('lf-pwd');
  const eyeIcon = document.getElementById('eye-icon');
  if (!pwd) return;

  if (pwd.type === 'password') {
    pwd.type = 'text';
    if (eyeIcon) { eyeIcon.classList.remove('fa-eye'); eyeIcon.classList.add('fa-eye-slash'); }
  } else {
    pwd.type = 'password';
    if (eyeIcon) { eyeIcon.classList.remove('fa-eye-slash'); eyeIcon.classList.add('fa-eye'); }
  }
}

/* ── LOGIN FORM SUBMIT FEEDBACK ────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  const loginForm = document.querySelector('.lf-form');
  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      const btn = this.querySelector('.lf-submit');
      if (btn) {
        btn.style.background = '#555';
        btn.querySelector('span[data-fr]').textContent =
          currentLang === 'fr' ? 'Connexion...' : 'Signing in...';
      }
    });
  }
});