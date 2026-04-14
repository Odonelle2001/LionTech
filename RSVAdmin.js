/* ============================================================
   Business List Rendering (Dashboard)
============================================================ */

const businesses = [
  {
    name: "Nora Beauty",
    slug: "nora-beauty",
    city: "Bastos, Yaoundé",
    type: "Beauté",
    rdv: 48,
    status: "Actif",
    statusClass: "active",
    plan: "10 000 F",
    icon: "💅",
    iconBg: "#f7e8ef"
  },
  {
    name: "Le Palmier",
    slug: "le-palmier",
    city: "Akwa, Douala",
    type: "Restaurant",
    rdv: 94,
    status: "Actif",
    statusClass: "active",
    plan: "15 000 F",
    icon: "🍽️",
    iconBg: "#f3ebde"
  },
  {
    name: "Clinique Santé+",
    slug: "clinique-sante",
    city: "Centre, Yaoundé",
    type: "Médical",
    rdv: 76,
    status: "Actif",
    statusClass: "active",
    plan: "12 000 F",
    icon: "🏥",
    iconBg: "#e7f0f8"
  },
  {
    name: "Barber Kings",
    slug: "barber-kings",
    city: "Mvog-Ada, Ydé",
    type: "Barbier",
    rdv: 12,
    status: "Config",
    statusClass: "config",
    plan: "5 000 F",
    icon: "💈",
    iconBg: "#e8f6ea"
  },
  {
    name: "Studio Lumière",
    slug: "studio-lumiere",
    city: "Bonanjo, Douala",
    type: "Photo",
    rdv: "—",
    status: "Nouveau",
    statusClass: "new",
    plan: "En attente",
    icon: "📸",
    iconBg: "#eee8f8"
  }
];

function buildStatusContent(item) {
  if (item.statusClass === "active") return `✓ ${item.status}`;
  if (item.statusClass === "config") return `⚙ ${item.status}`;
  return item.status;
}

function renderBusinesses() {
  const tableBody = document.getElementById("businessTableBody");
  if (!tableBody) return;

  tableBody.innerHTML = businesses
    .map(
      (item) => `
        <tr>
          <td>
            <div class="business-cell">
              <div class="business-icon" style="background:${item.iconBg}">
                ${item.icon}
              </div>
              <div>
                <strong>${item.name}</strong>
                <span>${item.city}</span>
              </div>
            </div>
          </td>
          <td>${item.type}</td>
          <td><strong>${item.rdv}</strong></td>
          <td>
            <span class="status ${item.statusClass}">
              ${buildStatusContent(item)}
            </span>
          </td>
          <td class="plan-price">${item.plan}</td>
          <td>
            <div class="action-buttons">
              <button class="action-btn" onclick="editBusiness('${item.slug}')">
                <i class="fa-solid fa-pen-to-square"></i> Éditer
              </button>
              <button class="action-btn action-btn-view" onclick="viewPage('${item.slug}')">
                <i class="fa-solid fa-eye"></i> Voir page
              </button>
            </div>
          </td>
        </tr>
      `
    )
    .join("");
}

/* ── VIEW — opens Utulisateur.php in a new tab ── */
function viewPage(slug) {
  window.open(
    '/LionRDV/Utulisateur.php?slug=' + slug,
    '_blank'
  );
}

/* ── EDIT — opens AjouterBussiness pre-filled ── */
function editBusiness(slug) {
  window.location.href =
    '/LionRDV/AjouterBussiness/AjouterBussiness.php?edit=' + slug;
}

/* ============================================================
   Ajouter Business — Dynamic Business Type + Booking Style
============================================================ */

document.addEventListener("DOMContentLoaded", function () {

  /* ---------------- BUSINESS TYPE LOGIC ---------------- */

  const businessTypeData = {
    salon:      ["Coiffure","Manucure","Pédicure","Massage","Maquillage","Soins du visage","Épilation"],
    restaurant: ["Petit déjeuner","Déjeuner","Dîner","Boissons","Desserts","Livraison","À emporter"],
    hotel:      ["Chambre standard","Chambre VIP / Deluxe","Suite","Restaurant","Blanchisserie","Spa / Bien-être","Navette"],
    medical:    ["Consultation générale","Pédiatrie","Gynécologie","Dentisterie","Laboratoire","Vaccination","Soins infirmiers"],
    barber:     ["Coupe homme","Barbe","Rasage","Coloration","Soin du visage"],
    fitness:    ["Coaching privé","Musculation","Cardio","Yoga / Pilates","Cours collectifs","Nutrition"],
    photo:      ["Shooting portrait","Mariage","Anniversaire","Photo studio","Vidéo","Retouche photo"],
    law:        ["Consultation juridique","Droit civil","Droit pénal","Droit du travail","Droit des affaires","Rédaction de documents"],
    coach:      ["Coaching personnel","Coaching carrière","Coaching business","Développement personnel","Sessions en ligne"]
  };

  const radios          = document.querySelectorAll('input[name="business_type"]');
  const selectBox       = document.getElementById("business_category_select");
  const typeServicesBox = document.getElementById("typeServicesBox");
  const customTypeBox   = document.getElementById("customTypeBox");
  const labels          = document.querySelectorAll(".type-option");

  function updateBusinessTypeUI(selectedValue) {
    labels.forEach(label => {
      const input = label.querySelector("input");
      label.classList.toggle("active", input.value === selectedValue);
    });

    if (selectedValue === "other") {
      typeServicesBox.style.display = "none";
      customTypeBox.style.display   = "block";
      return;
    }

    customTypeBox.style.display   = "none";
    typeServicesBox.style.display = "block";

    selectBox.innerHTML = '<option value="">-- Sélectionnez une catégorie --</option>';

    if (businessTypeData[selectedValue]) {
      businessTypeData[selectedValue].forEach(category => {
        const option       = document.createElement("option");
        option.value       = category;
        option.textContent = category;
        selectBox.appendChild(option);
      });
    }
  }

  radios.forEach(radio => {
    radio.addEventListener("change", function () {
      updateBusinessTypeUI(this.value);
    });
  });

  const checkedRadio = document.querySelector('input[name="business_type"]:checked');
  if (checkedRadio) updateBusinessTypeUI(checkedRadio.value);


  /* ---------------- BOOKING STYLE LOGIC ---------------- */

  const bookingRadios      = document.querySelectorAll('input[name="booking_style"]');
  const bookingOptions     = document.querySelectorAll(".booking-option");
  const bookingFieldGroups = document.querySelectorAll(".booking-fields-group");

  function updateBookingStyleUI(selectedStyle) {
    bookingOptions.forEach(option => {
      const input = option.querySelector("input");
      option.classList.toggle("active", input.value === selectedStyle);
    });

    bookingFieldGroups.forEach(group => {
      group.style.display = group.dataset.style === selectedStyle ? "grid" : "none";
    });
  }

  bookingRadios.forEach(radio => {
    radio.addEventListener("change", function () {
      updateBookingStyleUI(this.value);
    });
  });

  const checkedBookingRadio = document.querySelector('input[name="booking_style"]:checked');
  if (checkedBookingRadio) updateBookingStyleUI(checkedBookingRadio.value);


  /* ---------------- PLAN CARD SELECTION ---------------- */

  document.querySelectorAll(".plan-card").forEach(card => {
    card.addEventListener("click", function () {
      document.querySelectorAll(".plan-card").forEach(c => c.classList.remove("active"));
      this.classList.add("active");
      const radio = this.querySelector("input[type='radio']");
      if (radio) radio.checked = true;
    });
  });


  /* ---------------- GENERATE PASSWORD ---------------- */

  const generateBtn = document.querySelector(".small-generate-btn");
  if (generateBtn) {
    generateBtn.addEventListener("click", function () {
      const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#";
      let pwd = "";
      for (let i = 0; i < 10; i++) {
        pwd += chars[Math.floor(Math.random() * chars.length)];
      }
      const pwdField = document.getElementById("temp_password");
      if (pwdField) pwdField.value = pwd;
    });
  }


  /* ---------------- DASHBOARD TABLE ---------------- */
  renderBusinesses();

});


/* ============================================================
   Theme & Branding personalisation
============================================================ */

document.addEventListener("DOMContentLoaded", function () {

  function setupSelectableGroup(selector, inputName) {
    const items = document.querySelectorAll(selector);
    items.forEach(item => {
      const input = item.querySelector(`input[name="${inputName}"]`);
      item.addEventListener("click", () => {
        document.querySelectorAll(selector).forEach(el => el.classList.remove("active"));
        item.classList.add("active");
        if (input) input.checked = true;
        if (inputName === "background_style") toggleBackgroundUpload(input.value);
      });
    });
  }

  function toggleBackgroundUpload(value) {
    const wrap = document.getElementById("backgroundUploadWrap");
    if (wrap) wrap.style.display = value === "image" ? "block" : "none";
  }

  function bindColorPair(colorId, textId) {
    const colorInput = document.getElementById(colorId);
    const textInput  = document.getElementById(textId);
    if (!colorInput || !textInput) return;

    colorInput.addEventListener("input", () => {
      textInput.value = colorInput.value;
    });

    textInput.addEventListener("input", () => {
      const value = textInput.value.trim();
      if (/^#([0-9A-F]{3}){1,2}$/i.test(value)) {
        colorInput.value = value;
      }
    });
  }

  setupSelectableGroup(".theme-card",     "theme_preset");
  setupSelectableGroup(".design-option",  "background_style");
  setupSelectableGroup(".language-option","site_language");

  bindColorPair("primary_color",    "primary_color_text");
  bindColorPair("secondary_color",  "secondary_color_text");
  bindColorPair("button_color",     "button_color_text");
  bindColorPair("text_color",       "text_color_text");
  bindColorPair("background_color", "background_color_text");
  bindColorPair("border_color",     "border_color_text");

  const selectedBackground = document.querySelector('input[name="background_style"]:checked');
  if (selectedBackground) toggleBackgroundUpload(selectedBackground.value);

});


/* ============================================================
   Ajouter Business — Logo preview + Create button + Copy link
============================================================ */

function previewLogo(input) {
  if (!input.files || !input.files[0]) return;

  var reader    = new FileReader();
  reader.onload = function (e) {
    var img = document.getElementById("out-logo-preview");
    var ph  = document.getElementById("out-logo-placeholder");
    if (img) { img.src = e.target.result; img.style.display = "block"; }
    if (ph)  { ph.style.display = "none"; }
  };
  reader.readAsDataURL(input.files[0]);
}

function copyLink() {
  var link = document.getElementById("out-link");
  if (!link) return;
  var text = "https://" + link.textContent.trim();
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(function () {
      alert("✓ Lien copié !");
    });
  } else {
    var ta        = document.createElement("textarea");
    ta.value      = text;
    ta.style.position = "fixed";
    ta.style.opacity  = "0";
    document.body.appendChild(ta);
    ta.select();
    document.execCommand("copy");
    document.body.removeChild(ta);
    alert("✓ Lien copié !");
  }
}

document.addEventListener("DOMContentLoaded", function () {

  var createBtn = document.getElementById("create-btn");
  if (!createBtn) return;

  createBtn.addEventListener("click", function () {
    var btn = this;

    btn.disabled         = true;
    btn.innerHTML        = '<i class="fa-solid fa-spinner fa-spin"></i> Création en cours...';
    btn.style.background = "#555";
    btn.style.color      = "#fff";

    setTimeout(function () {

      btn.innerHTML        = '<i class="fa-solid fa-circle-check"></i> Compte créé !';
      btn.style.background = "#059669";
      btn.style.color      = "#fff";

      var slug  = (document.getElementById("subdomain")     || {}).value || "mon-business";
      var name  = (document.getElementById("business_name") || {}).value || "Mon Business";
      var email = (document.getElementById("login_email")   || {}).value || "—";
      var pwd   = (document.getElementById("temp_password") || {}).value || "—";

      var outLink = document.getElementById("out-link");
      var outName = document.getElementById("out-biz-name");
      var outSub  = document.getElementById("out-biz-sub");
      var outMail = document.getElementById("out-email");
      var outPwd  = document.getElementById("out-password");

      if (outLink) outLink.textContent = "lionrdv.cm/" + slug;
      if (outName) outName.textContent = name;
      if (outSub)  outSub.textContent  = "lionrdv.cm/" + slug;
      if (outMail) outMail.textContent = email;
      if (outPwd)  outPwd.textContent  = pwd;

      var outBox = document.getElementById("out-box");
      if (outBox) {
        outBox.classList.add("show");
        outBox.scrollIntoView({ behavior: "smooth", block: "start" });
      }

    }, 1800);
  });

});