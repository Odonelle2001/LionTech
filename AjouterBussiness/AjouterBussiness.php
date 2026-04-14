<?php
$currentPage = 'add_business';
$businessCount = 6;
$qrCount = 3;
$alertCount = 2;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un business - LionRDV</title>
   <link rel="stylesheet" href="../sidebar.css">
   <link rel="stylesheet" href="AjouterBussiness.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="app-layout">
    
   <?php include '../sidebar.php'; ?>

    <main class="addbiz-main">
      <header class="addbiz-topbar">
        <div class="addbiz-topbar-left">
          <h1>Ajouter un business</h1>
          <p>Créer un nouveau client sur la plateforme LionRDV</p>
        </div>

        <div class="addbiz-topbar-actions">
          <button type="button" class="btn-outline-dark">
            <i class="fa-solid fa-arrow-left"></i>
            Retour
          </button>
          <button type="button" class="btn-gold-dark">
            <i class="fa-solid fa-check"></i>
            Créer le compte
          </button>
        </div>
      </header>

      <div class="addbiz-content">
        <!-- LEFT FORM -->
        <section class="addbiz-form-panel">
          
          <!-- SECTION 1 -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">1</div>
              <div>
                <h2>Informations du business</h2>
                <p>Nom, lien, contact, localisation, description et logo</p>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="business_name">Nom du business</label>
                <input type="text" id="business_name" placeholder="Ex: Nora Beauty">
              </div>

              <div class="form-group">
                <label for="subdomain">Sous-domaine</label>
                <div class="input-prefix">
                  <span>lionrdv.cm/</span>
                  <input type="text" id="subdomain" placeholder="nora-beauty">
                </div>
              </div>

              <div class="form-group">
                <label for="whatsapp">WhatsApp</label>
                <input type="text" id="whatsapp" placeholder="+237 6XX XXX XXX">
              </div>

              <div class="form-group">
                <label for="owner_email">Email propriétaire</label>
                <input type="email" id="owner_email" placeholder="owner@email.com">
              </div>

              <div class="form-group">
                <label for="city">Ville</label>
                <input type="text" id="city" placeholder="Yaoundé">
              </div>

              <div class="form-group">
                <label for="quarter">Quartier</label>
                <input type="text" id="quarter" placeholder="Bastos">
              </div>

              <div class="form-group full-width">
                <label for="description">Description courte</label>
                <textarea id="description" rows="4" placeholder="Petite description visible sur la page de réservation..."></textarea>
              </div>

              <div class="form-group full-width">
                <label for="logo">Logo du business</label>
                <input type="file" id="logo">
              </div>
            </div>
          </div>
          
          <div class="form-group full-width">
  <label>Accès propriétaire</label>
  <div class="owner-access-box">
    <div class="owner-access-info">
      <div class="owner-access-icon">
        <i class="fa-solid fa-lock"></i>
      </div>
      <div>
        <div class="owner-access-title">Connexion espace propriétaire</div>
        <div class="owner-access-sub">Ce bouton apparaîtra sur la page du business après création</div>
      </div>
    </div>
    <a href="/LionRDV/Clien%20de%20LionTech/ClientLion.php" 
       target="_blank" 
       class="owner-connexion-btn">
      <i class="fa-solid fa-right-to-bracket"></i>
      Connexion
    </a>
  </div>
</div>

          <!-- SECTION 2 -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">2</div>
              <div>
                <h2>Type de business</h2>
                <p>Sélectionnez le type pour personnaliser la réservation</p>
              </div>
            </div>

            <div class="business-type-grid">
    <label class="type-option active">
        <input type="radio" name="business_type" value="salon" checked>
        <span>💅 Salon de beauté</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="restaurant">
        <span>🍽️ Restaurant</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="hotel">
        <span>🏨 Hôtellerie</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="medical">
        <span>🩺 Clinique / Médical</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="barber">
        <span>✂️ Barbier</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="fitness">
        <span>🏋️ Sport & fitness</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="photo">
        <span>📸 Photographie</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="law">
        <span>⚖️ Avocat / Cabinet</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="coach">
        <span>🎯 Coach</span>
    </label>

    <label class="type-option">
        <input type="radio" name="business_type" value="other">
        <span>📝 Autre</span>
    </label>
</div>

<div class="type-services-box" id="typeServicesBox">
    <label for="business_category_select">Catégories proposées</label>
    <select id="business_category_select" name="business_category_select">
        <option value="">-- Sélectionnez une catégorie --</option>
    </select>
</div>

<div class="custom-type-box" id="customTypeBox" style="display:none;">
    <label for="custom_business_name">Nom du type de business</label>
    <input type="text" id="custom_business_name" name="custom_business_name" placeholder="Ex: Studio Podcast">

    <label for="custom_business_categories">Catégories / services à proposer</label>
    <textarea id="custom_business_categories" name="custom_business_categories" rows="4"
        placeholder="Ex: Enregistrement, Mixage, Podcast vidéo, Location studio"></textarea>
</div>

            <div class="form-group top-space">
              <label for="other_type">Si autre, précisez</label>
              <input type="text" id="other_type" placeholder="Ex: Studio Podcast">
            </div>
          </div>
          <!-- 3. Style de réservation -->
<section class="form-section">
    <div class="section-number">3</div>
    <div class="section-content">
        <h3>Style de réservation</h3>
        <p>Choisissez comment ce business reçoit les réservations</p>

        <div class="booking-style-grid">
            <label class="booking-option active">
                <input type="radio" name="booking_style" value="individual" checked>
                <span>👤 Individuelle</span>
            </label>

            <label class="booking-option">
                <input type="radio" name="booking_style" value="multiple">
                <span>👥 Multiple</span>
            </label>

            <label class="booking-option">
                <input type="radio" name="booking_style" value="employee">
                <span>🧑‍💼 Par employé</span>
            </label>

            <label class="booking-option">
                <input type="radio" name="booking_style" value="capacity">
                <span>🏷️ Par capacité</span>
            </label>

            <label class="booking-option">
                <input type="radio" name="booking_style" value="request">
                <span>📩 Sur demande</span>
            </label>
        </div>

        <!-- Champs dynamiques -->
        <div id="bookingStyleFields" class="booking-style-fields">

            <!-- Individuelle -->
            <div class="booking-fields-group" data-style="individual">
                <label for="slot_duration_individual">Durée par créneau</label>
                <select id="slot_duration_individual" name="slot_duration_individual">
                    <option value="15">15 min</option>
                    <option value="30" selected>30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">1 heure</option>
                    <option value="90">1h30</option>
                    <option value="120">2 heures</option>
                </select>
            </div>

            <!-- Multiple -->
            <div class="booking-fields-group" data-style="multiple" style="display:none;">
                <label for="max_parallel_bookings">Nombre maximum de réservations simultanées</label>
                <input type="number" id="max_parallel_bookings" name="max_parallel_bookings" min="1" value="3">

                <label for="slot_duration_multiple">Durée par créneau</label>
                <select id="slot_duration_multiple" name="slot_duration_multiple">
                    <option value="15">15 min</option>
                    <option value="30" selected>30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">1 heure</option>
                    <option value="90">1h30</option>
                    <option value="120">2 heures</option>
                </select>
            </div>

            <!-- Par employé -->
            <div class="booking-fields-group" data-style="employee" style="display:none;">
                <label for="employee_count">Nombre d'employés</label>
                <input type="number" id="employee_count" name="employee_count" min="1" value="3">

                <label for="client_choose_employee">Le client peut choisir l'employé ?</label>
                <select id="client_choose_employee" name="client_choose_employee">
                    <option value="yes" selected>Oui</option>
                    <option value="no">Non</option>
                </select>

                <label for="slot_duration_employee">Durée par créneau</label>
                <select id="slot_duration_employee" name="slot_duration_employee">
                    <option value="15">15 min</option>
                    <option value="30" selected>30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">1 heure</option>
                    <option value="90">1h30</option>
                    <option value="120">2 heures</option>
                </select>
            </div>

            <!-- Par capacité -->
            <div class="booking-fields-group" data-style="capacity" style="display:none;">
                <label for="max_capacity_per_slot">Capacité maximale par créneau</label>
                <input type="number" id="max_capacity_per_slot" name="max_capacity_per_slot" min="1" value="10">

                <label for="capacity_label">Type de capacité</label>
                <select id="capacity_label" name="capacity_label">
                    <option value="places">Places</option>
                    <option value="tables">Tables</option>
                    <option value="rooms">Chambres</option>
                    <option value="people">Personnes</option>
                </select>
            </div>

            <!-- Sur demande -->
            <div class="booking-fields-group" data-style="request" style="display:none;">
                <label for="manual_validation">Validation des réservations</label>
                <select id="manual_validation" name="manual_validation">
                    <option value="manual" selected>Validation manuelle</option>
                    <option value="auto">Validation automatique</option>
                </select>

                <label for="request_note">Message d'information</label>
                <textarea id="request_note" name="request_note" rows="3"
                    placeholder="Ex: Votre demande sera confirmée par le business après vérification."></textarea>
            </div>

        </div>
    </div>
</section>

          <!-- SECTION 3 -->
           <!-- SECTION 3 : THÈME, DESIGN & BRANDING -->
<div class="form-card">
    <div class="form-card-header">
        <div class="step-badge">3</div>
        <div>
            <h2>Thème, design & branding</h2>
            <p>Configurez le style visuel, les couleurs, les logos et la langue du site</p>
        </div>
    </div>

    <!-- 1. Theme presets -->
    <div class="branding-block">
        <h3>Thème prédéfini</h3>
        <div class="theme-preset-grid">
            <label class="theme-card active">
                <input type="radio" name="theme_preset" value="elegant" checked>
                <div class="theme-preview elegant-preview"></div>
                <span>✨ Élégant</span>
            </label>

            <label class="theme-card">
                <input type="radio" name="theme_preset" value="minimal">
                <div class="theme-preview minimal-preview"></div>
                <span>🧼 Minimal</span>
            </label>

            <label class="theme-card">
                <input type="radio" name="theme_preset" value="luxe">
                <div class="theme-preview luxe-preview"></div>
                <span>👑 Luxe</span>
            </label>

            <label class="theme-card">
                <input type="radio" name="theme_preset" value="modern">
                <div class="theme-preview modern-preview"></div>
                <span>🚀 Moderne</span>
            </label>

            <label class="theme-card">
                <input type="radio" name="theme_preset" value="nature">
                <div class="theme-preview nature-preview"></div>
                <span>🌿 Nature</span>
            </label>

            <label class="theme-card">
                <input type="radio" name="theme_preset" value="dark">
                <div class="theme-preview dark-preview"></div>
                <span>🌙 Sombre</span>
            </label>
        </div>
    </div>

    <!-- 2. Colors -->
    <div class="branding-block">
        <h3>Couleurs</h3>
        <div class="color-settings-grid">
            <div class="color-field">
                <label for="primary_color">Couleur principale</label>
                <div class="color-input-wrap">
                    <input type="color" id="primary_color" name="primary_color" value="#d4af37">
                    <input type="text" id="primary_color_text" name="primary_color_text" value="#d4af37">
                </div>
            </div>

            <div class="color-field">
                <label for="secondary_color">Couleur secondaire</label>
                <div class="color-input-wrap">
                    <input type="color" id="secondary_color" name="secondary_color" value="#111111">
                    <input type="text" id="secondary_color_text" name="secondary_color_text" value="#111111">
                </div>
            </div>

            <div class="color-field">
                <label for="button_color">Couleur des boutons</label>
                <div class="color-input-wrap">
                    <input type="color" id="button_color" name="button_color" value="#d4af37">
                    <input type="text" id="button_color_text" name="button_color_text" value="#d4af37">
                </div>
            </div>

            <div class="color-field">
                <label for="text_color">Couleur du texte</label>
                <div class="color-input-wrap">
                    <input type="color" id="text_color" name="text_color" value="#222222">
                    <input type="text" id="text_color_text" name="text_color_text" value="#222222">
                </div>
            </div>

            <div class="color-field">
                <label for="background_color">Couleur de fond</label>
                <div class="color-input-wrap">
                    <input type="color" id="background_color" name="background_color" value="#ffffff">
                    <input type="text" id="background_color_text" name="background_color_text" value="#ffffff">
                </div>
            </div>

            <div class="color-field">
                <label for="border_color">Couleur des bordures</label>
                <div class="color-input-wrap">
                    <input type="color" id="border_color" name="border_color" value="#e5e7eb">
                    <input type="text" id="border_color_text" name="border_color_text" value="#e5e7eb">
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Background design -->
    <div class="branding-block">
        <h3>Design du fond</h3>
        <div class="design-grid">
            <label class="design-option active">
                <input type="radio" name="background_style" value="solid" checked>
                <span>⬜ Fond uni</span>
            </label>

            <label class="design-option">
                <input type="radio" name="background_style" value="gradient">
                <span>🌈 Dégradé</span>
            </label>

            <label class="design-option">
                <input type="radio" name="background_style" value="pattern">
                <span>🧩 Motif léger</span>
            </label>

            <label class="design-option">
                <input type="radio" name="background_style" value="texture">
                <span>🎨 Texture</span>
            </label>

            <label class="design-option">
                <input type="radio" name="background_style" value="image">
                <span>🖼️ Image</span>
            </label>
        </div>

        <div id="backgroundUploadWrap" style="display:none; margin-top:12px;">
            <label for="background_image">Image de fond</label>
            <input type="file" id="background_image" name="background_image" accept="image/*">
        </div>

        <div id="customCssWrap" style="margin-top:12px;">
            <label for="custom_css_design">CSS design personnalisé</label>
            <textarea id="custom_css_design" name="custom_css_design" rows="4"
                placeholder="Ex: background: linear-gradient(135deg, #f8f1d4, #ffffff);"></textarea>
        </div>
    </div>

    <!-- 4. Navbar / footer -->
    <div class="branding-block">
        <h3>Navbar & footer</h3>
        <div class="branding-layout-grid">
            <div>
                <label for="navbar_style">Style de navbar</label>
                <select id="navbar_style" name="navbar_style">
                    <option value="light">Claire</option>
                    <option value="dark">Sombre</option>
                    <option value="transparent">Transparente</option>
                    <option value="boxed">Encadrée</option>
                </select>
            </div>

            <div>
                <label for="footer_style">Style de footer</label>
                <select id="footer_style" name="footer_style">
                    <option value="light">Clair</option>
                    <option value="dark">Sombre</option>
                    <option value="minimal">Minimal</option>
                    <option value="rich">Complet</option>
                </select>
            </div>

            <div>
                <label for="business_logo_position">Position logo business</label>
                <select id="business_logo_position" name="business_logo_position">
                    <option value="left">Gauche</option>
                    <option value="center">Centre</option>
                    <option value="right">Droite</option>
                </select>
            </div>

            <div>
                <label for="business_logo_size">Taille logo business</label>
                <select id="business_logo_size" name="business_logo_size">
                    <option value="small">Petit</option>
                    <option value="medium" selected>Moyen</option>
                    <option value="large">Grand</option>
                </select>
            </div>
        </div>
    </div>

    <!-- 5. Logo visibility -->
    <div class="branding-block">
        <h3>Gestion des logos</h3>
        <div class="toggle-grid">
            <label class="toggle-item">
                <input type="checkbox" name="show_business_logo" checked>
                <span>Afficher le logo du business</span>
            </label>

            <label class="toggle-item">
                <input type="checkbox" name="show_liontech_logo" checked>
                <span>Afficher le logo LionTech</span>
            </label>

            <label class="toggle-item">
                <input type="checkbox" name="show_liontech_footer_only">
                <span>Afficher LionTech uniquement dans le footer</span>
            </label>
        </div>
    </div>

    <!-- 6. Language -->
    <div class="branding-block">
        <h3>Langue du site</h3>
        <div class="language-grid">
            <label class="language-option active">
                <input type="radio" name="site_language" value="fr" checked>
                <span>🇫🇷 Français</span>
            </label>

            <label class="language-option">
                <input type="radio" name="site_language" value="en">
                <span>🇬🇧 English</span>
            </label>

            <label class="language-option">
                <input type="radio" name="site_language" value="bilingual">
                <span>🌍 Bilingue FR/EN</span>
            </label>
        </div>
    </div>
</div>

          <!-- SECTION 4 -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">4</div>
              <div>
                <h2>Compte d'accès propriétaire</h2>
                <p>Créer le compte utilisé par le propriétaire du business</p>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="login_email">Whatsapp numb</label>
                <input type="email" id="login_email" placeholder="nora@email.com">
              </div>

              <div class="form-group">
                <label for="temp_password">Mot de passe temporaire</label>
                <div class="password-row">
                  <input type="text" id="temp_password" placeholder="Temporaire123">
                  <button type="button" class="small-generate-btn">Générer</button>
                </div>
              </div>

              <div class="form-group full-width">
                <label>Plan d'abonnement</label>
                <div class="plan-options">
                  <label class="plan-card">
                    <input type="radio" name="plan">
                    <span class="plan-name">Basic</span>
                    <span class="plan-price">10 000 FCFA</span>
                  </label>

                  <label class="plan-card active">
                    <input type="radio" name="plan" checked>
                    <span class="plan-name">Standard</span>
                    <span class="plan-price">15 000 FCFA</span>
                  </label>

                  <label class="plan-card">
                    <input type="radio" name="plan">
                    <span class="plan-name">Premium</span>
                    <span class="plan-price">20 000 FCFA</span>
                  </label>
                </div>
              </div>

              <div class="form-group full-width">
                <label for="internal_notes">Notes internes</label>
                <textarea id="internal_notes" rows="4" placeholder="Visible uniquement par LionTech..."></textarea>
              </div>
            </div>
          </div>

        </section>

        <!-- RIGHT PREVIEW -->
        <aside class="addbiz-preview-panel">
          <div class="preview-top-actions">
            <button class="preview-btn dark">
              <i class="fa-solid fa-arrow-left"></i>
              Retour au formulaire
            </button>
            <button class="preview-btn solid">
              Confirmer & créer
            </button>
          </div>

          <div class="preview-toolbar">
            <div class="lang-switch">
              <span>LANGUE</span>
              <button class="lang-btn active">FR</button>
              <button class="lang-btn">EN</button>
            </div>

            <div class="theme-switch">
              <span>THÈME</span>
              <div class="theme-preview-dots">
                <span class="theme-dot pink active"></span>
                <span class="theme-dot black"></span>
                <span class="theme-dot blue"></span>
                <span class="theme-dot green"></span>
                <span class="theme-dot orange"></span>
                <span class="theme-dot purple"></span>
                <span class="theme-dot red"></span>
                <span class="theme-dot darkgreen"></span>
              </div>
            </div>
          </div>

          <div class="preview-business-tags">
            <span class="preview-tag">● Nora Beauty · Salon de beauté</span>
            <span class="preview-tag">lionrdv.cm/nora-beauty</span>
          </div>

          <div class="preview-card-row">
            <div class="preview-info-card">
              <div class="preview-info-icon gold">
                <i class="fa-regular fa-user"></i>
              </div>
              <h3>Vue Client (Propriétaire du salon)</h3>
              <p>Ce que voit Nora quand elle se connecte à son dashboard.</p>
              <span class="mini-pill">Accès via lionrdv.cm/login</span>
            </div>

            <div class="preview-info-card">
              <div class="preview-info-icon gray">
                <i class="fa-solid fa-mobile-screen"></i>
              </div>
              <h3>Vue Customer (Le client qui réserve)</h3>
              <p>Ce que voit la personne qui scanne le QR code ou ouvre le lien.</p>
              <span class="mini-pill">Accès via lionrdv.cm/nora-beauty</span>
            </div>
          </div>

          <div class="preview-section-title">
            <span class="preview-badge">CLIENT</span>
            <div>
              <h3>Dashboard propriétaire — ce que voit Nora</h3>
              <p>Se connecte via lionrdv.cm/login</p>
            </div>
          </div>

          <div class="phone-preview-row">
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-stat-row">
                    <div class="phone-stat">8</div>
                    <div class="phone-stat">47K</div>
                    <div class="phone-stat">4.9</div>
                  </div>
                  <div class="phone-card">Planning du jour</div>
                  <div class="phone-card">09h30 - Awa Tchoupo</div>
                  <div class="phone-card">11h00 - Carine Bebe</div>
                  <div class="phone-card">08h00 - Marie N.</div>
                </div>
              </div>
              <p class="phone-caption">Dashboard quotidien avec planning et statistiques</p>
            </div>

            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-card">Disponibilités</div>
                  <div class="phone-card">Lundi 08h00 - 18h00</div>
                  <div class="phone-card">Mardi 08h00 - 18h00</div>
                  <div class="phone-card">Employés actifs: 4</div>
                </div>
              </div>
              <p class="phone-caption">Gestion des horaires, jours et employés</p>
            </div>
          </div>

          <div class="preview-section-title lower">
            <span class="preview-badge secondary">CUSTOMER</span>
            <div>
              <h3>Page de réservation — ce que voit le client final</h3>
              <p>Accessible via QR code ou lien</p>
            </div>
          </div>

          <div class="phone-preview-row">
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-card">Choisissez une catégorie</div>
                  <div class="phone-card">Cheveux · Ongles · Maquillage</div>
                  <div class="phone-card">Coupe & Brushing - 2 500 F</div>
                  <div class="phone-card">Lissage - 9 000 F</div>
                </div>
              </div>
              <p class="phone-caption">Catégorie et sélection du service</p>
            </div>

            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Choisissez un créneau</div>
                <div class="phone-body">
                  <div class="phone-card">Mar 09 - 10h00</div>
                  <div class="phone-card">Service: Coupe & Brushing</div>
                  <div class="phone-card">Total: 2 500 FCFA</div>
                  <div class="confirm-box">Confirmer la RDV</div>
                </div>
              </div>
              <p class="phone-caption">Date, heure, paiement et confirmation</p>
            </div>
          </div>
        </aside>
      </div>
    </main>
  </div>
   <script src="../RSVAdmin.js"></script> 
</body>
</html>