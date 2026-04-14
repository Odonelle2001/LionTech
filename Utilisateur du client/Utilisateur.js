/* ============================================================
   Utulisateur.js — LionRDV Customer Page
   Handles: language toggle, RDV modal, about expand
   BUSINESS variable is set by PHP before this script loads
============================================================ */

/* ── LANGUAGE ─────────────────────────────────────────── */
var currentLang = (typeof BUSINESS !== 'undefined') ? BUSINESS.defLang : 'fr';

function setLang(lang, btn) {
  currentLang = lang;

  /* update lang buttons */
  document.querySelectorAll('.cl-lang-btn').forEach(function(b) {
    b.classList.remove('active');
  });
  if (btn) btn.classList.add('active');

  /* update every element that has data-fr / data-en */
  document.querySelectorAll('[data-fr]').forEach(function(el) {
    var val = lang === 'fr' ? el.dataset.fr : el.dataset.en;
    if (val !== undefined && val !== '') {
      el.textContent = val;
    }
  });

  /* update html lang attribute */
  document.documentElement.lang = lang;
}

/* ── RDV MODAL ────────────────────────────────────────── */
function openRdv() {
  var modal = document.getElementById('rdv-modal');
  if (modal) {
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeRdv(e) {
  /* close on overlay click or close button */
  if (e && e.target !== document.getElementById('rdv-modal') &&
      !e.target.closest('.cl-modal-close')) return;

  var modal = document.getElementById('rdv-modal');
  if (modal) {
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }
}

/* close on ESC */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    var modal = document.getElementById('rdv-modal');
    if (modal && modal.classList.contains('open')) {
      modal.classList.remove('open');
      document.body.style.overflow = '';
    }
  }
});

/* ── ABOUT EXPAND ─────────────────────────────────────── */
function toggleAbout(btn) {
  var aboutText = document.querySelector('.cl-about-text');
  if (!aboutText) return;

  var isExpanded = aboutText.classList.contains('expanded');
  aboutText.classList.toggle('expanded', !isExpanded);

  if (currentLang === 'fr') {
    btn.textContent = isExpanded ? 'Lire la suite →' : 'Réduire ↑';
    btn.dataset.fr  = isExpanded ? 'Lire la suite →' : 'Réduire ↑';
    btn.dataset.en  = isExpanded ? 'Read more →'     : 'Show less ↑';
  } else {
    btn.textContent = isExpanded ? 'Read more →' : 'Show less ↑';
    btn.dataset.fr  = isExpanded ? 'Lire la suite →' : 'Réduire ↑';
    btn.dataset.en  = isExpanded ? 'Read more →'     : 'Show less ↑';
  }
}

/* ── GALLERY EXPAND (placeholder) ────────────────────── */
function openGallery() {
  /* When gallery is built: open full-screen gallery viewer */
  /* For now, a simple scroll hint */
  alert(currentLang === 'fr'
    ? 'Galerie complète — bientôt disponible'
    : 'Full gallery — coming soon');
}

/* ── INIT ─────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function() {

  /* Apply default language on load */
  if (typeof BUSINESS !== 'undefined' && BUSINESS.lang !== 'bilingual') {
    /* Single language — no toggle shown, just apply */
    setLang(BUSINESS.defLang, null);
  }

  /* Smooth scroll to sections when tapping service items */
  document.querySelectorAll('.cl-service-item').forEach(function(item) {
    item.addEventListener('click', function() {
      openRdv();
    });
  });

  /* Gallery items — open full gallery in future */
  document.querySelectorAll('.cl-gallery-item:not(.cl-gallery-ph)').forEach(function(item) {
    item.addEventListener('click', function() {
      openGallery();
    });
  });

  /* Modal close button */
  var closeBtn = document.querySelector('.cl-modal-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      var modal = document.getElementById('rdv-modal');
      if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
      }
    });
  }

});