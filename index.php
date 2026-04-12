<?php
require_once __DIR__ . '/db.php';

$db = getDB();

$projects = $db->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();
$team = $db->query("SELECT * FROM team_members WHERE active = 1 ORDER BY order_num ASC")->fetchAll();

function getSetting(string $key, string $default = ''): string {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? htmlspecialchars($row['setting_value']) : $default;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LIONTECH — Agence Web & Digitale</title>
  <link rel="icon" href="liontech-logo.jpg" type="image/jpeg">
  <link rel="stylesheet" href="index.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <!-- NAVBAR -->
  <header class="navbar" id="navbar">
    <div class="logo-wrap">
      <img src="liontech-logo.jpg" alt="LIONTECH Logo" class="logo-img">
      <div class="logo-text"> LION<span class="logo-gold">TECH</span></div>
    </div>

    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>

    <nav class="nav-links" id="navLinks">
      <a href="#services" data-fr="Services" data-en="Services">Services</a>
      <a href="#realisations" data-fr="Réalisations" data-en="Portfolio">Réalisations</a>
      <a href="#apropos" data-fr="À propos" data-en="About">À propos</a>
      <a href="#contact" data-fr="Contact" data-en="Contact">Contact</a>
    </nav>

    <div class="nav-controls">
      <button class="icon-btn" id="themeToggle" title="Changer le thème" aria-label="Toggle theme">
        <i class="fas fa-moon" id="themeIcon"></i>
      </button>
      <button class="lang-btn-toggle" id="langToggle">FR / EN</button>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero" id="home">
    <div class="hero-bg-grid"></div>
    <div class="mini-badge" data-fr="AGENCE TECH & DIGITALE — CAMEROUN" data-en="TECH & DIGITAL AGENCY — CAMEROON">AGENCE TECH & DIGITALE — CAMEROUN</div>

    <h1>
      <span class="hero-title-fr">Votre <span class="gold-text">présence digitale,</span><br>notre excellence.</span>
      <span class="hero-title-en" style="display:none">Your <span class="gold-text">digital presence,</span><br>our excellence.</span>
    </h1>

    <div class="divider"></div>

    <p class="hero-text" data-fr="LionTech conçoit des sites web, des applications et des campagnes digitales pour les entreprises camerounaises qui visent l'excellence." data-en="LionTech builds websites, applications and digital campaigns for Cameroonian businesses that aim for excellence.">
      LionTech conçoit des sites web, des applications et des campagnes digitales pour les entreprises camerounaises qui visent l'excellence.
    </p>

    <div class="hero-buttons">
      <a href="#contact" class="btn btn-gold" data-fr="Démarrer mon projet" data-en="Start my project">Démarrer mon projet</a>
      <a href="#realisations" class="btn btn-outline" data-fr="Voir nos réalisations" data-en="View our work">Voir nos réalisations</a>
    </div>
  </section>

  <!-- STATS -->
  <section class="stats">
    <div class="stat-box">
      <h3>10+</h3>
      <p data-fr="Projets livrés" data-en="Projects delivered">Projets livrés</p>
    </div>
    <div class="stat-box">
      <h3>98%</h3>
      <p data-fr="Clients satisfaits" data-en="Satisfied clients">Clients satisfaits</p>
    </div>
    <div class="stat-box">
      <h3>3</h3>
      <p data-fr="Villes desservies" data-en="Cities served">Villes desservies</p>
    </div>
    <div class="stat-box">
      <h3>FR/EN</h3>
      <p data-fr="Entièrement bilingue" data-en="Fully bilingual">Entièrement bilingue</p>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="section" id="services">
    <p class="section-tag" data-fr="CE QUE NOUS FAISONS" data-en="WHAT WE DO">CE QUE NOUS FAISONS</p>
    <h2 data-fr="Des services <span>d'excellence</span>" data-en="Services of <span>excellence</span>">Des services <span>d'excellence</span></h2>
    <p class="section-subtext" data-fr="Du site vitrine à la campagne digitale complète. Livrés avec précision, en français et en anglais." data-en="From showcase sites to full digital campaigns. Delivered with precision, in French and English.">
      Du site vitrine à la campagne digitale complète. Livrés avec précision, en français et en anglais.
    </p>

    <div class="services-grid">
      <div class="card reveal">
         <div class="card-img">
        <img src="photos/web.jpeg" alt="Web">
      </div>
        <div class="card-icon"><i class="fas fa-code"></i></div>
        <div class="card-number">01</div>
        <h3 data-fr="Sites Vitrines" data-en="Showcase Websites">Sites Vitrines</h3>
        <p data-fr="Sites professionnels, rapides et adaptés au mobile qui représentent votre marque 24h/24." data-en="Professional, fast and mobile-friendly websites that represent your brand 24/7.">Sites professionnels, rapides et adaptés au mobile qui représentent votre marque 24h/24.</p>
      </div>
      <div class="card reveal">
         <div class="card-img">
        <img src="photos/Application.jpeg" alt="App">
      </div>
        <div class="card-icon"><i class="fas fa-layer-group"></i></div>
        <div class="card-number">02</div>
        <h3 data-fr="Plateformes Web & Apps" data-en="Web Platforms & Apps">Plateformes Web & Apps</h3>
        <p data-fr="Applications web sur mesure, dashboards, e-commerce et systèmes de gestion adaptés à vos besoins." data-en="Custom web applications, dashboards, e-commerce and management systems tailored to your needs.">Applications web sur mesure, dashboards, e-commerce et systèmes de gestion adaptés à vos besoins.</p>
      </div>
      <div class="card reveal">
        <div class="card-img">
        <img src="photos/Card3UI.jpeg" alt="Design">
      </div>
        <div class="card-icon"><i class="fas fa-palette"></i></div>
        <div class="card-number">03</div>
        <h3 data-fr="Design Graphique & UI/UX" data-en="Graphic Design & UI/UX">Design Graphique & UI/UX</h3>
        <p data-fr="Logos, identité visuelle, maquettes et interfaces utilisateur élégantes et intuitives." data-en="Logos, visual identity, mockups and elegant, intuitive user interfaces.">Logos, identité visuelle, maquettes et interfaces utilisateur élégantes et intuitives.</p>
      </div>
      <div class="card reveal">
         <div class="card-img">
        <img src="photos/MarketingDigital.jpeg" alt="Marketing">
      </div>
        <div class="card-icon"><i class="fas fa-bullhorn"></i></div>
        <div class="card-number">04</div>
        <h3 data-fr="Marketing Digital" data-en="Digital Marketing">Marketing Digital</h3>
        <p data-fr="Réseaux sociaux, email, SEO et campagnes Google/Facebook pour atteindre vos clients efficacement." data-en="Social media, email, SEO and Google/Facebook campaigns to reach your clients effectively.">Réseaux sociaux, email, SEO et campagnes Google/Facebook pour atteindre vos clients efficacement.</p>
      </div>
      <div class="card reveal">
         <div class="card-img">
        <img src="photos/Maintenance.jpeg" alt="Support">
      </div>
        <div class="card-icon"><i class="fas fa-tools"></i></div>
        <div class="card-number">05</div>
        <h3 data-fr="Maintenance & Support" data-en="Maintenance & Support">Maintenance & Support</h3>
        <p data-fr="Mises à jour, corrections de bugs, sauvegardes et support technique réactif pour votre tranquillité." data-en="Updates, bug fixes, backups and responsive technical support for your peace of mind.">Mises à jour, corrections de bugs, sauvegardes et support technique réactif pour votre tranquillité.</p>
      </div>
      <div class="card reveal">
         <div class="card-img">
        <img src="photos/Publicite.jpeg" alt="Ads">
      </div>
        <div class="card-icon"><i class="fas fa-chart-line"></i></div>
        <div class="card-number">06</div>
        <h3 data-fr="Publicité en Ligne" data-en="Online Advertising">Publicité en Ligne</h3>
        <p data-fr="Campagnes publicitaires ciblées pour un meilleur retour sur investissement et plus de clients." data-en="Targeted advertising campaigns for a better return on investment and more clients.">Campagnes publicitaires ciblées pour un meilleur retour sur investissement et plus de clients.</p>
      </div>
    </div>
  </section>

  <!-- SECOND STATS -->
  <section class="stats second-stats">
    <div class="stat-box">
      <h3>100%</h3>
      <p data-fr="Bilingue FR / EN" data-en="Bilingual FR / EN">Bilingue FR / EN</p>
    </div>
    <div class="stat-box">
      <h3>Local</h3>
      <p data-fr="Marché camerounais" data-en="Cameroonian market">Marché camerounais</p>
    </div>
    <div class="stat-box">
      <h3>2–4 sem.</h3>
      <p data-fr="Délai de livraison" data-en="Delivery time">Délai de livraison</p>
    </div>
    <div class="stat-box">
      <h3>FCFA</h3>
      <p data-fr="Tarifs transparents" data-en="Transparent pricing">Tarifs transparents</p>
    </div>
  </section>

  <!-- REALISATIONS -->
  <section class="section" id="realisations">
    <p class="section-tag" data-fr="NOS RÉALISATIONS" data-en="OUR WORK">NOS RÉALISATIONS</p>
    <h2 data-fr="Projets <span>récents</span>" data-en="Recent <span>projects</span>">Projets <span>récents</span></h2>
    <p class="section-subtext" data-fr="Des solutions digitales pour des entreprises à Yaoundé, Douala et au-delà." data-en="Digital solutions for businesses in Yaoundé, Douala and beyond.">
      Des solutions digitales pour des entreprises à Yaoundé, Douala et au-delà.
    </p>

    <div class="filter-buttons">
      <button class="filter-btn active" data-filter="all" data-fr="Tous" data-en="All">Tous</button>
      <button class="filter-btn" data-filter="Plateforme Web" data-fr="Plateforme Web" data-en="Web Platform">Plateforme Web</button>
      <button class="filter-btn" data-filter="Design Graphique" data-fr="Design Graphique" data-en="Graphic Design">Design Graphique</button>
    </div>

    <div class="projects-grid" id="projectsGrid">
      <?php if (empty($projects)): ?>
        <p class="no-content" data-fr="Aucun projet à afficher pour le moment." data-en="No projects to display yet.">Aucun projet à afficher pour le moment.</p>
      <?php else: ?>
        <?php foreach ($projects as $project): ?>
          <div class="project-card reveal" data-category="<?= htmlspecialchars($project['category']) ?>">
            <div class="project-label"><?= htmlspecialchars($project['category']) ?></div>
            <div class="project-thumb">
              <?php if (!empty($project['image']) && file_exists(__DIR__ . '/uploads/projects/' . $project['image'])): ?>
                <img src="<?= BASE_PATH ?>/uploads/projects/<?= htmlspecialchars($project['image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>">
              <?php else: ?>
                <div class="project-placeholder"><i class="fas fa-laptop-code"></i></div>
              <?php endif; ?>
            </div>
            <div class="project-info">
              <h3><?= htmlspecialchars($project['title']) ?></h3>
              <p><?= htmlspecialchars($project['description']) ?></p>
              <?php if (!empty($project['tools'])): ?>
                <div class="tools-badges">
                  <?php foreach (explode(',', $project['tools']) as $tool): ?>
                    <span class="badge"><?= htmlspecialchars(trim($tool)) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($project['link']) && $project['link'] !== '#'): ?>
                <a href="<?= htmlspecialchars($project['link']) ?>" target="_blank" rel="noopener" data-fr="Voir le projet →" data-en="View project →">Voir le projet →</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- PROCESS -->
  <section class="section process" id="process">
    <p class="section-tag" data-fr="NOTRE MÉTHODE" data-en="OUR METHOD">NOTRE MÉTHODE</p>
    <h2 data-fr="Un processus <span>simple</span>, de A à Z" data-en="A <span>simple</span> process, from A to Z">Un processus <span>simple</span>, de A à Z</h2>
    <p class="section-subtext" data-fr="Transparent et collaboratif du premier appel jusqu'à la livraison finale." data-en="Transparent and collaborative from the first call to the final delivery.">
      Transparent et collaboratif du premier appel jusqu'à la livraison finale.
    </p>
    <div class="process-grid">
      <div class="process-card reveal">
        <div class="roman">I</div>
        <div class="process-icon"><i class="fas fa-phone-alt"></i></div>
        <h3 data-fr="Appel découverte" data-en="Discovery call">Appel découverte</h3>
        <p data-fr="On écoute vos objectifs, contraintes, budget, délais et votre vision." data-en="We listen to your goals, constraints, budget, timeline and vision.">On écoute vos objectifs, contraintes, budget, délais et votre vision.</p>
      </div>
      <div class="process-card reveal">
        <div class="roman">II</div>
        <div class="process-icon"><i class="fas fa-file-alt"></i></div>
        <h3 data-fr="Proposition" data-en="Proposal">Proposition</h3>
        <p data-fr="Devis clair avec périmètre, délais et livrables précis." data-en="Clear quote with defined scope, timeline and precise deliverables.">Devis clair avec périmètre, délais et livrables précis.</p>
      </div>
      <div class="process-card reveal">
        <div class="roman">III</div>
        <div class="process-icon"><i class="fas fa-laptop-code"></i></div>
        <h3 data-fr="Conception & Build" data-en="Design & Build">Conception & Build</h3>
        <p data-fr="Vous validez à chaque étape. On avance ensemble." data-en="You validate at each step. We move forward together.">Vous validez à chaque étape. On avance ensemble.</p>
      </div>
      <div class="process-card reveal">
        <div class="roman">IV</div>
        <div class="process-icon"><i class="fas fa-rocket"></i></div>
        <h3 data-fr="Livraison & Suivi" data-en="Delivery & Support">Livraison & Suivi</h3>
        <p data-fr="On met en ligne votre site et on reste disponible pour vos mises à jour." data-en="We launch your site and remain available for your updates.">On met en ligne votre site et on reste disponible pour vos mises à jour.</p>
      </div>
    </div>
  </section>

  <!-- EQUIPE -->
  <section class="section" id="apropos">
    <p class="section-tag" data-fr="NOTRE ÉQUIPE" data-en="OUR TEAM">NOTRE ÉQUIPE</p>
    <h2 data-fr="Les talents <span>derrière LIONTECH</span>" data-en="The talents <span>behind LIONTECH</span>">Les talents <span>derrière LIONTECH</span></h2>
    <p class="section-subtext" data-fr="Une équipe jeune, passionnée et locale, dédiée à votre succès digital." data-en="A young, passionate and local team dedicated to your digital success.">
      Une équipe jeune, passionnée et locale, dédiée à votre succès digital.
    </p>
    <div class="team-grid">
      <?php foreach ($team as $member): ?>
        <div class="team-card reveal">
          <!-- Photo circulaire -->
          <div class="team-photo">
            <?php
              $photoSrc = '';
              if (!empty($member['photo'])) {
                  if (file_exists(__DIR__ . '/' . $member['photo']))
                      $photoSrc = BASE_PATH . '/' . htmlspecialchars($member['photo']);
                  elseif (file_exists(__DIR__ . '/uploads/team/' . $member['photo']))
                      $photoSrc = BASE_PATH . '/uploads/team/' . htmlspecialchars($member['photo']);
              }
            ?>
            <?php if ($photoSrc): ?>
              <img src="<?= $photoSrc ?>" alt="<?= htmlspecialchars($member['name']) ?>">
            <?php else: ?>
              <div class="team-placeholder"><i class="fas fa-user"></i></div>
            <?php endif; ?>
          </div>

          <!-- Infos -->
          <div class="team-info">
            <h3><?= htmlspecialchars($member['name']) ?></h3>
            <p class="team-role"><?= htmlspecialchars($member['roles']) ?></p>
            <?php if (!empty($member['description'])): ?>
              <p class="team-desc"><?= htmlspecialchars($member['description']) ?></p>
            <?php endif; ?>

            <div class="team-footer">
              <?php if (!empty($member['portfolio_url'])): ?>
                <a href="<?= htmlspecialchars($member['portfolio_url']) ?>" target="_blank" rel="noopener" class="btn-portfolio">
                  <i class="fas fa-external-link-alt"></i>
                  <span data-fr="Voir le portfolio" data-en="View portfolio">Voir le portfolio</span>
                </a>
              <?php endif; ?>

              <div class="team-socials">
                <?php if (!empty($member['linkedin'])): ?>
                  <a href="https://linkedin.com/in/<?= htmlspecialchars($member['linkedin']) ?>" target="_blank" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                <?php endif; ?>
                <?php if (!empty($member['github'])): ?>
                  <a href="https://github.com/<?= htmlspecialchars($member['github']) ?>" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="contact-section" id="contact">
    <div class="contact-left">
      <p class="section-tag" data-fr="CONTACTEZ-NOUS" data-en="CONTACT US">CONTACTEZ-NOUS</p>
      <h2 data-fr="Construisons quelque chose<br>de grand ensemble" data-en="Let's build something<br>great together">Construisons quelque chose<br>de grand ensemble</h2>
      <p data-fr="Parlez-nous de votre projet. Nous vous répondons sous 24 heures, en français ou en anglais." data-en="Tell us about your project. We respond within 24 hours, in French or English.">
        Parlez-nous de votre projet. Nous vous répondons sous 24 heures, en français ou en anglais.
      </p>

      <div class="contact-info">
        <p><i class="fas fa-envelope"></i> <?= getSetting('email', 'odonellenjoya83@gmail.com') ?></p>
        <p><i class="fab fa-whatsapp"></i> <?= getSetting('phone', '(651) 347-9485') ?> (WhatsApp)</p>
        <p><i class="fas fa-map-marker-alt"></i> <?= getSetting('address', 'Yaoundé, Cameroun') ?></p>
      </div>

      <div class="social-links">
        <?php if ($fb = getSetting('facebook')): ?><a href="<?= $fb ?>" target="_blank"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
        <?php if ($ig = getSetting('instagram')): ?><a href="<?= $ig ?>" target="_blank"><i class="fab fa-instagram"></i></a><?php endif; ?>
        <?php if ($li = getSetting('linkedin')): ?><a href="<?= $li ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
        <?php if ($gh = getSetting('github')): ?><a href="<?= $gh ?>" target="_blank"><i class="fab fa-github"></i></a><?php endif; ?>
      </div>

      <div class="contact-quick">
        <a href="<?= getSetting('whatsapp_link', '#') ?>" target="_blank" class="btn btn-wa">
          <i class="fab fa-whatsapp"></i> <span data-fr="Écrire sur WhatsApp" data-en="Message on WhatsApp">Écrire sur WhatsApp</span>
        </a>
        <a href="<?= getSetting('telegram_link', '#') ?>" target="_blank" class="btn btn-tg">
          <i class="fab fa-telegram"></i> <span data-fr="Écrire sur Telegram" data-en="Message on Telegram">Écrire sur Telegram</span>
        </a>
      </div>

      <div class="contact-note">
        <h4 data-fr="Consultation gratuite" data-en="Free consultation">Consultation gratuite</h4>
        <p data-fr="Écrivez-nous ici ou sur WhatsApp. Nous revenons rapidement vers vous pour parler de votre projet." data-en="Write to us here or on WhatsApp. We'll get back to you quickly to discuss your project.">
          Écrivez-nous ici ou sur WhatsApp. Nous revenons rapidement vers vous pour parler de votre projet.
        </p>
      </div>
    </div>

    <div class="contact-form-box">
      <h3 data-fr="Obtenir un devis gratuit" data-en="Get a free quote">Obtenir un devis gratuit</h3>
      <div id="formMsg" class="form-msg" style="display:none;"></div>
      <form id="contactForm">
        <div class="row">
          <input type="text" name="name" placeholder="Nom *" required />
          <input type="text" name="whatsapp" placeholder="WhatsApp" />
        </div>
        <input type="email" name="email" placeholder="Email" />
        <select name="subject">
          <option value="" data-fr="Service souhaité" data-en="Desired service">Service souhaité</option>
          <option data-fr="Création de site web" data-en="Website creation">Création de site web</option>
          <option data-fr="Développement d'application" data-en="App development">Développement d'application</option>
          <option data-fr="Design graphique & UI/UX" data-en="Graphic design & UI/UX">Design graphique & UI/UX</option>
          <option data-fr="Marketing digital" data-en="Digital marketing">Marketing digital</option>
          <option data-fr="Publicité en ligne" data-en="Online advertising">Publicité en ligne</option>
          <option data-fr="Maintenance & support" data-en="Maintenance & support">Maintenance & support</option>
        </select>
        <textarea name="message" rows="5" placeholder="Parlez-nous de votre projet..." required></textarea>
        <button type="submit" class="btn btn-gold full-btn" data-fr="Envoyer le message" data-en="Send message">Envoyer le message</button>
      </form>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-logo">LIONTECH</div>
    <div class="footer-links">
      <a href="#services" data-fr="Services" data-en="Services">Services</a>
      <a href="#realisations" data-fr="Réalisations" data-en="Portfolio">Réalisations</a>
      <a href="#apropos" data-fr="À propos" data-en="About">À propos</a>
      <a href="#contact" data-fr="Contact" data-en="Contact">Contact</a>
    </div>
    <p>© 2026 LIONTECH — <span data-fr="Tous droits réservés" data-en="All rights reserved">Tous droits réservés</span> — Yaoundé, Cameroun</p>
  </footer>

  <script>
  // ===== THEME =====
  const html = document.documentElement;
  const themeBtn = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  const saved = localStorage.getItem('lt-theme') || 'dark';
  html.setAttribute('data-theme', saved);
  updateThemeIcon(saved);

  themeBtn.addEventListener('click', () => {
    const current = html.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('lt-theme', next);
    updateThemeIcon(next);
  });

  function updateThemeIcon(theme) {
    themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
  }

  // ===== LANGUAGE =====
  let currentLang = localStorage.getItem('lt-lang') || 'fr';
  const langBtn = document.getElementById('langToggle');
  applyLang(currentLang);

  langBtn.addEventListener('click', () => {
    currentLang = currentLang === 'fr' ? 'en' : 'fr';
    localStorage.setItem('lt-lang', currentLang);
    applyLang(currentLang);
  });

  function applyLang(lang) {
    document.querySelectorAll('[data-fr][data-en]').forEach(el => {
      const text = el.getAttribute('data-' + lang);
      if (text) el.innerHTML = text;
    });
    document.querySelector('.hero-title-fr').style.display = lang === 'fr' ? '' : 'none';
    document.querySelector('.hero-title-en').style.display = lang === 'en' ? '' : 'none';
    langBtn.textContent = lang === 'fr' ? 'EN' : 'FR';
  }

  // ===== HAMBURGER =====
  const hamburger = document.getElementById('hamburger');
  const navLinks = document.getElementById('navLinks');
  hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('open');
    hamburger.classList.toggle('open');
  });
  document.querySelectorAll('.nav-links a').forEach(a => {
    a.addEventListener('click', () => {
      navLinks.classList.remove('open');
      hamburger.classList.remove('open');
    });
  });

  // ===== NAVBAR SCROLL =====
  window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 50);
  });

  // ===== PROJECT FILTER =====
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.getAttribute('data-filter');
      document.querySelectorAll('.project-card').forEach(card => {
        if (filter === 'all' || card.getAttribute('data-category') === filter) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });

  // ===== SCROLL REVEAL =====
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('visible'); }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

  // ===== CONTACT FORM =====
  document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const msgEl = document.getElementById('formMsg');
    btn.disabled = true;
    btn.textContent = currentLang === 'fr' ? 'Envoi...' : 'Sending...';
    const data = new FormData(this);
    try {
      const res = await fetch('/contact-handler.php', { method: 'POST', body: data });
      const json = await res.json();
      msgEl.style.display = 'block';
      msgEl.className = 'form-msg ' + (json.success ? 'success' : 'error');
      msgEl.textContent = json.message;
      if (json.success) this.reset();
    } catch {
      msgEl.style.display = 'block';
      msgEl.className = 'form-msg error';
      msgEl.textContent = currentLang === 'fr' ? 'Erreur réseau. Réessayez.' : 'Network error. Please retry.';
    }
    btn.disabled = false;
    btn.textContent = currentLang === 'fr' ? 'Envoyer le message' : 'Send message';
  });
  </script>
</body>
</html>
