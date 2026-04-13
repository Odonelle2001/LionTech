<?php
$currentPage = 'dashboard';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RSVAdmin</title>

  <!-- Sidebar CSS -->
  <link rel="stylesheet" href="sidebar.css">

  <!-- Admin Layout CSS -->
  <link rel="stylesheet" href="RSVAdmin.css">

  <!-- Icons -->
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <div class="app-layout">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main">

      <header class="topbar">
        <div>
          <h2>Dashboard</h2>
          <p>Mardi 9 Avril 2026</p>
        </div>
        <button class="export-btn">Exporter</button>
      </header>

      <section class="stats-grid">
        <div class="stat-card">
          <div class="stat-head">
            <div class="icon-box soft">
              <i class="fa-regular fa-id-card"></i>
            </div>
            <span class="trend positive">↑ +2</span>
          </div>
          <h3>6</h3>
          <h4>Businesses actifs</h4>
          <p>3 Yaoundé · 2 Douala · 1 Baf.</p>
        </div>

        <div class="stat-card">
          <div class="stat-head">
            <div class="icon-box dark">
              <i class="fa-regular fa-calendar-check"></i>
            </div>
            <span class="trend positive">↑ +18%</span>
          </div>
          <h3>287</h3>
          <h4>RDV ce mois</h4>
          <p>vs 243 le mois dernier</p>
        </div>

        <div class="stat-card">
          <div class="stat-head">
            <div class="icon-box soft">
              <i class="fa-regular fa-square-check"></i>
            </div>
          </div>
          <h3>72K</h3>
          <h4>FCFA revenus</h4>
          <p>Abonnements mensuels</p>
        </div>
      </section>

      <section class="panel">
        <div class="panel-header">
          <div class="panel-title-wrap">
            <h3><i class="fa-regular fa-id-card"></i> Tous les businesses</h3>
            <span class="small-count">6</span>
          </div>
          <a href="#" class="see-all">Voir tout</a>
        </div>

        <div class="table-wrapper">
          <table class="business-table">
            <thead>
              <tr>
                <th>BUSINESS</th>
                <th>TYPE</th>
                <th>RDV</th>
                <th>STATUT</th>
                <th>PLAN</th>
                <th>ACTIONS</th>
              </tr>
            </thead>
            <tbody id="businessTableBody"></tbody>
          </table>
        </div>
      </section>

      <section class="bottom-grid">
        <div class="panel">
          <div class="panel-header">
            <h3>Répartition des plans</h3>
            <span class="revenue-pill">72 000 FCFA / mois</span>
          </div>

          <div class="plans-list">
            <div class="plan-row">
              <div class="plan-left">
                <span class="dot black"></span>
                <div>
                  <strong>Premium — 15 000 F</strong>
                  <div class="mini-line premium"></div>
                </div>
              </div>
              <div class="plan-right">2 clients &nbsp; <strong>30 000 F</strong></div>
            </div>

            <div class="plan-row">
              <div class="plan-left">
                <span class="dot gold"></span>
                <div>
                  <strong>Standard — 10 000 F</strong>
                  <div class="mini-line standard"></div>
                </div>
              </div>
              <div class="plan-right">3 clients &nbsp; <strong>30 000 F</strong></div>
            </div>

            <div class="plan-row">
              <div class="plan-left">
                <span class="dot pale"></span>
                <div>
                  <strong>Basic — 5 000 F</strong>
                  <div class="mini-line basic"></div>
                </div>
              </div>
              <div class="plan-right">1 client &nbsp; <strong>5 000 F</strong></div>
            </div>

            <div class="plan-row">
              <div class="plan-left">
                <span class="dot light"></span>
                <div>
                  <strong>En attente — 0 F</strong>
                  <div class="mini-line waiting"></div>
                </div>
              </div>
              <div class="plan-right">2 clients &nbsp; <strong>—</strong></div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <h3>Actions rapides</h3>
          </div>

          <div class="quick-actions">
            <div class="quick-card">
              <div class="quick-icon dark">
                <i class="fa-solid fa-plus"></i>
              </div>
              <h4>Nouveau business</h4>
              <p>Créer un compte business</p>
            </div>

            <div class="quick-card">
              <div class="quick-icon mint">
                <i class="fa-regular fa-square-check"></i>
              </div>
              <h4>Rapport mensuel</h4>
              <p>Exporter en PDF</p>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div>

  <script src="RSVAdmin.js"></script>

</body>
</html>
