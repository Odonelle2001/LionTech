<?php
session_start();
require_once __DIR__ . '/../db.php';

function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function hex_rgba(string $hex, float $a=1): string {
  $hex=ltrim($hex,'#');
  if(strlen($hex)===3)$hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  [$r,$g,$b]=array_map('hexdec',str_split($hex,2));
  return "rgba($r,$g,$b,$a)";
}
function is_open(array $av): bool {
  $today=(int)date('w');$now=date('H:i:s');
  foreach($av as $s){if((int)$s['day_index']===$today&&(int)$s['is_open']===1){if(!empty($s['open_time'])&&!empty($s['close_time']))return $now>=$s['open_time']&&$now<$s['close_time'];}}
  return false;
}
function get_close(array $av): ?string {
  $today=(int)date('w');
  foreach($av as $s){if((int)$s['day_index']===$today&&(int)$s['is_open']===1)return $s['close_time']?:null;}
  return null;
}

$preview = isset($_GET['preview']) && $_GET['preview']==='1';
$services=[]; $av=[]; $gallery=[];

if ($preview) {
  $biz = array_merge([
    'id'=>0,'slug'=>'preview','name'=>'Nom du business','initials'=>'NB',
    'type'=>'Salon de beauté','description'=>'Description de votre business...',
    'city'=>'Yaoundé','neighborhood'=>'Bastos','whatsapp'=>'',
    'logo'=>'','cover_photo'=>'','theme_color'=>'#C9A84C','theme_bg'=>'#FFF9EE',
    'primary_color'=>'#C9A84C','secondary_color'=>'#0A0A0A','button_color'=>'#C9A84C',
    'text_color'=>'#222222','background_color'=>'#ffffff','border_color'=>'#e5e7eb',
    'navbar_style'=>'light','footer_style'=>'minimal','show_biz_logo'=>1,
    'show_lt_logo'=>1,'lt_footer_only'=>0,'language'=>'fr','show_prices'=>1,
    'plan'=>'basic','rating'=>0,'review_count'=>0,
  ], $_SESSION['builder_preview'] ?? []);
  $av=[
    ['day_name'=>'Lundi','day_en'=>'Monday','day_index'=>1,'is_open'=>1,'open_time'=>'08:00:00','close_time'=>'18:00:00'],
    ['day_name'=>'Mardi','day_en'=>'Tuesday','day_index'=>2,'is_open'=>1,'open_time'=>'08:00:00','close_time'=>'18:00:00'],
    ['day_name'=>'Mercredi','day_en'=>'Wednesday','day_index'=>3,'is_open'=>1,'open_time'=>'08:00:00','close_time'=>'18:00:00'],
    ['day_name'=>'Jeudi','day_en'=>'Thursday','day_index'=>4,'is_open'=>1,'open_time'=>'08:00:00','close_time'=>'18:00:00'],
    ['day_name'=>'Vendredi','day_en'=>'Friday','day_index'=>5,'is_open'=>1,'open_time'=>'08:00:00','close_time'=>'19:00:00'],
    ['day_name'=>'Samedi','day_en'=>'Saturday','day_index'=>6,'is_open'=>1,'open_time'=>'09:00:00','close_time'=>'17:00:00'],
    ['day_name'=>'Dimanche','day_en'=>'Sunday','day_index'=>0,'is_open'=>0,'open_time'=>null,'close_time'=>null],
  ];
  $services=[
    ['name'=>'Service exemple 1','name_en'=>'Sample 1','duration'=>'45 min','price'=>2500,'color'=>$biz['theme_color']],
    ['name'=>'Service exemple 2','name_en'=>'Sample 2','duration'=>'1h','price'=>5000,'color'=>$biz['theme_color']],
    ['name'=>'Service exemple 3','name_en'=>'Sample 3','duration'=>'1h30','price'=>7000,'color'=>$biz['theme_color']],
  ];
} else {
  $slug=preg_replace('/[^a-z0-9\-]/','',strtolower(trim($_GET['slug']??'')));
  if($slug===''){http_response_code(400);die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">Slug manquant</h1>');}
  $st=$pdo->prepare("SELECT * FROM businesses WHERE slug=? AND status IN ('active','config','new') LIMIT 1");
  $st->execute([$slug]); $biz=$st->fetch(PDO::FETCH_ASSOC);
  if(!$biz){http_response_code(404);die('<div style="font-family:sans-serif;text-align:center;margin-top:80px;"><div style="font-size:48px;">🔍</div><h1>Business introuvable</h1><p>'.h($slug).'</p></div>');}
  $st=$pdo->prepare("SELECT * FROM services WHERE business_id=? AND active=1 ORDER BY display_order,id");$st->execute([$biz['id']]);$services=$st->fetchAll(PDO::FETCH_ASSOC);
  $st=$pdo->prepare("SELECT * FROM availability WHERE business_id=? ORDER BY day_index");$st->execute([$biz['id']]);$av=$st->fetchAll(PDO::FETCH_ASSOC);
  $st=$pdo->prepare("SELECT * FROM gallery WHERE business_id=? ORDER BY display_order,id");$st->execute([$biz['id']]);$gallery=$st->fetchAll(PDO::FETCH_ASSOC);
}
$biz=array_merge(['initials'=>'B','type'=>'','description'=>'','city'=>'','neighborhood'=>'',
  'whatsapp'=>'','logo'=>'','cover_photo'=>'','theme_color'=>'#C9A84C','theme_bg'=>'#FFF9EE',
  'primary_color'=>'#C9A84C','secondary_color'=>'#0A0A0A','button_color'=>'#C9A84C',
  'text_color'=>'#222222','background_color'=>'#ffffff','border_color'=>'#e5e7eb',
  'navbar_style'=>'light','footer_style'=>'minimal','show_biz_logo'=>1,'show_lt_logo'=>1,
  'lt_footer_only'=>0,'language'=>'fr','show_prices'=>1,'plan'=>'basic','rating'=>0,'review_count'=>0],$biz);
$loc=trim(implode(', ',array_filter([$biz['neighborhood'],$biz['city']])));
$defLang=$biz['language']==='en'?'en':'fr';
$isOpen=is_open($av); $closesAt=get_close($av);
$navBg=$biz['navbar_style']==='dark'?$biz['secondary_color']:'#ffffff';
$navColor=$biz['navbar_style']==='dark'?'#ffffff':$biz['text_color'];
$footBg=$biz['footer_style']==='dark'?$biz['secondary_color']:'#ffffff';
$footColor=$biz['footer_style']==='dark'?'rgba(255,255,255,0.7)':'#7A7570';
$logoSrc=!empty($biz['logo'])?BASE_URL.'/'.ltrim($biz['logo'],'/'):'';
$coverSrc=!empty($biz['cover_photo'])?BASE_URL.'/'.ltrim($biz['cover_photo'],'/'):'';
?>
<!DOCTYPE html>
<html lang="<?=h($defLang)?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=h($biz['name'])?> — LionRDV</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="Utulisateur.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/responsive.css">
<style>
:root{
  --brand:<?=h($biz['theme_color'])?>;
  --brand-bg:<?=h($biz['theme_bg'])?>;
  --brand-rgba:<?=hex_rgba($biz['theme_color'],0.10)?>;
  --btn-color:<?=h($biz['button_color'])?>;
  --primary:<?=h($biz['primary_color'])?>;
  --secondary:<?=h($biz['secondary_color'])?>;
  --text-color:<?=h($biz['text_color'])?>;
  --page-bg:<?=h($biz['background_color'])?>;
  --border-col:<?=h($biz['border_color'])?>;
  --navbar-bg:<?=h($navBg)?>;
  --navbar-text:<?=h($navColor)?>;
  --footer-bg:<?=h($footBg)?>;
  --footer-text:<?=h($footColor)?>;
}
<?php if($preview): ?>
.pc{cursor:pointer;transition:outline 0.12s;position:relative;}
.pc:hover{outline:2px dashed rgba(201,168,76,0.75);outline-offset:2px;}
.pc:hover::after{content:attr(data-hint);position:absolute;top:4px;right:4px;background:#C9A84C;color:#0A0A0A;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;pointer-events:none;z-index:999;white-space:nowrap;}
<?php endif; ?>
a.cl-rdv-btn-inline,a.cl-sticky-btn{display:inline-flex;align-items:center;gap:7px;text-decoration:none;color:#fff;}
a.cl-sticky-btn{width:100%;justify-content:center;}
</style>
</head>
<body style="background:<?=h($biz['background_color'])?>;" id="el-body">

<!-- NAVBAR -->
<nav class="cl-navbar<?=$preview?' pc':''?>" id="el-navbar"<?=$preview?' data-section="navbar" data-hint="Éditer navbar"':''?> style="background:var(--navbar-bg);">
<div class="cl-navbar-inner">
<div class="cl-nav-left">
<?php if((int)$biz['show_biz_logo']): ?>
<?php if($logoSrc): ?><img src="<?=h($logoSrc)?>" alt="<?=h($biz['name'])?>" class="cl-nav-logo-img" id="el-logo-img">
<?php else: ?><div class="cl-nav-initials" style="background:var(--brand);" id="el-initials"><?=h($biz['initials'])?></div><?php endif; ?>
<?php endif; ?>
<div class="cl-nav-biz-info">
<div class="cl-nav-biz-name" style="color:var(--navbar-text);" id="el-biz-name"><?=h($biz['name'])?></div>
<div class="cl-nav-biz-type" id="el-biz-type"><?=h($biz['type'])?></div>
</div></div>
<div class="cl-nav-right">
<?php if($biz['language']==='bilingual'): ?>
<div class="cl-lang-toggle" id="el-lang-toggle">
<button class="cl-lang-btn <?=$defLang==='fr'?'active':''?>" onclick="setLang('fr',this)">FR</button>
<button class="cl-lang-btn <?=$defLang==='en'?'active':''?>" onclick="setLang('en',this)">EN</button>
</div>
<?php else: ?><div class="cl-lang-toggle" id="el-lang-toggle" style="display:none;"></div><?php endif; ?>
<?php if((int)$biz['show_lt_logo']&&!(int)$biz['lt_footer_only']): ?>
<div class="cl-lt-badge" id="el-lt-badge"><div class="cl-lt-mark">LT</div><span class="cl-lt-text">LionRDV</span></div>
<?php else: ?><div class="cl-lt-badge" id="el-lt-badge" style="display:none;"><div class="cl-lt-mark">LT</div><span class="cl-lt-text">LionRDV</span></div><?php endif; ?>
</div></div></nav>

<!-- HERO -->
<?php
  $ovPos = [
    'ov_name'     => $biz['ov_name']     ?? 'centre',
    'ov_type'     => $biz['ov_type']     ?? 'centre',
    'ov_address'  => $biz['ov_address']  ?? 'centre',
    'ov_whatsapp' => $biz['ov_whatsapp'] ?? 'centre',
  ];
  function ovDisp($p){ return $p==='masqué'||$p==='masque' ? 'display:none;' : ''; }
  function ovZone($p){ return $p==='bas' ? 'bas' : 'centre'; }
?>
<div class="cl-hero<?=$preview?' pc':''?>" id="el-hero"<?=$preview?' data-section="hero" data-hint="Éditer hero"':''?>>
<?php if($coverSrc): ?><img src="<?=h($coverSrc)?>" alt="<?=h($biz['name'])?>" class="cl-hero-img" id="el-cover-img">
<?php else: ?><div class="cl-hero-placeholder" style="background:var(--brand);" id="el-hero-ph"><i class="fa-regular fa-image cl-hero-ph-icon"></i><span data-fr="Photo de couverture" data-en="Cover photo">Photo de couverture</span></div><?php endif; ?>
<div class="cl-hero-overlay"></div>

<!-- Zone CENTRE de la couverture -->
<div class="cl-hero-zone cl-hero-zone-centre" id="el-hero-zone-centre">
  <div class="cl-hero-name" id="el-hero-name" data-overlay="ov_name" style="<?=ovDisp($ovPos['ov_name'])?>"><?=h($biz['name'])?></div>
  <div class="cl-hero-type" id="el-hero-type" data-overlay="ov_type" style="<?=ovDisp($ovPos['ov_type'])?>"><?=h($biz['type'])?></div>
  <div class="cl-hero-addr" id="el-hero-addr" data-overlay="ov_address" style="<?=ovDisp($ovPos['ov_address'])?>"><i class="fa-solid fa-location-dot"></i> <?=h($loc)?></div>
  <a class="cl-hero-wa" id="el-hero-wa" data-overlay="ov_whatsapp" style="<?=ovDisp($ovPos['ov_whatsapp'])?>" href="https://wa.me/<?=preg_replace('/\D/','',$biz['whatsapp'])?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> <?=h($biz['whatsapp'])?></a>
  <?php if((float)$biz['rating']>0): ?><div class="cl-hero-rating"><i class="fa-solid fa-star cl-star"></i><span><?=h((string)$biz['rating'])?></span><span class="cl-review-count" data-fr="(<?=(int)$biz['review_count']?> avis)" data-en="(<?=(int)$biz['review_count']?> reviews)">(<?=(int)$biz['review_count']?> avis)</span></div><?php endif; ?>
</div>

<!-- Zone BAS de la couverture (vide par défaut) -->
<div class="cl-hero-zone cl-hero-zone-bas" id="el-hero-zone-bas"></div>

<script>
(function(){
  /* Au chargement, déplace dans la zone "bas" les éléments configurés ainsi */
  var initialPos = <?=json_encode($ovPos)?>;
  var bas = document.getElementById('el-hero-zone-bas');
  ['ov_name','ov_type','ov_address','ov_whatsapp'].forEach(function(f){
    if (initialPos[f] === 'bas') {
      var el = document.querySelector('[data-overlay="'+f+'"]');
      if (el && bas) bas.appendChild(el);
    }
  });
})();
</script>
</div>

<!-- STATUS -->
<div class="cl-status-bar">
<?php if($isOpen): ?>
<div class="cl-status-open"><div class="cl-status-dot" style="background:#059669;"></div><span data-fr="Ouvert maintenant" data-en="Open now">Ouvert maintenant</span></div>
<?php if($closesAt): ?><div class="cl-status-hours">Ferme à <?=h(substr($closesAt,0,5))?></div><?php endif; ?>
<?php else: ?>
<div class="cl-status-open"><div class="cl-status-dot" style="background:#DC2626;"></div><span data-fr="Fermé" data-en="Closed">Fermé</span></div>
<?php endif; ?>
</div>

<!-- RDV SECTION -->
<div class="cl-rdv-section<?=$preview?' pc':''?>" id="el-rdv-sec"<?=$preview?' data-section="rdv" data-hint="Éditer bouton RDV"':''?>>
<div class="cl-rdv-section-inner">
<div class="cl-rdv-section-text">
<div class="cl-rdv-section-title" data-fr="Prêt à réserver ?" data-en="Ready to book?">Prêt à réserver ?</div>
<div class="cl-rdv-section-sub" data-fr="Choisissez votre créneau en quelques secondes" data-en="Pick your slot in seconds">Choisissez votre créneau en quelques secondes</div>
</div>
<?php if(!$preview): ?>
<a href="<?= BASE_URL ?>/Reserver/Reserver.php?slug=<?=urlencode($biz['slug'])?>" class="cl-rdv-btn-inline" style="background:var(--btn-color);" id="el-rdv-btn">
<?php else: ?><button class="cl-rdv-btn-inline" style="background:var(--btn-color);" id="el-rdv-btn"><?php endif; ?>
<i class="fa-regular fa-calendar-check"></i><span data-fr="Prendre un RDV" data-en="Book now">Prendre un RDV</span>
<?php echo $preview ? '</button>' : '</a>'; ?>
</div></div>

<!-- GALLERY -->
<section class="cl-section<?=$preview?' pc':''?>" id="el-gallery"<?=$preview?' data-section="gallery" data-hint="Gérer galerie"':''?>>
<div class="cl-section-title" data-fr="Galerie photos" data-en="Photo gallery">Galerie photos</div>
<div class="cl-gallery">
<?php if(!empty($gallery)): ?>
<?php foreach(array_slice($gallery,0,5) as $i=>$p): ?>
<div class="cl-gallery-item <?=$i===0?'cl-gallery-wide':''?>"><img src="<?= BASE_URL ?>/<?=h(ltrim($p['path'],'/'))?>" alt="<?=h($p['alt_text']??$biz['name'])?>" loading="lazy"></div>
<?php endforeach; ?>
<?php else: ?>
<div class="cl-gallery-item cl-gallery-wide cl-gallery-ph" style="background:<?=hex_rgba($biz['theme_color'],0.12)?>;"><i class="fa-regular fa-image" style="color:var(--brand);font-size:28px;"></i></div>
<?php for($i=0;$i<4;$i++): ?><div class="cl-gallery-item cl-gallery-ph" style="background:<?=hex_rgba($biz['theme_color'],0.06+$i*0.02)?>;"><i class="fa-regular fa-image" style="color:var(--brand);font-size:18px;opacity:0.5;"></i></div><?php endfor; ?>
<div class="cl-gallery-more cl-gallery-add-hint" data-fr="Photos ajoutées par le propriétaire" data-en="Photos added by owner">Photos ajoutées par le propriétaire</div>
<?php endif; ?>
</div></section>

<!-- ABOUT -->
<section class="cl-section<?=$preview?' pc':''?>" id="el-about"<?=$preview?' data-section="info" data-hint="Éditer description"':''?>>
<div class="cl-section-title" data-fr="À propos" data-en="About us">À propos</div>
<div class="cl-about-text" id="el-desc" data-fr="<?=h($biz['description'])?>" data-en="<?=h($biz['description'])?>"><?=h($biz['description'])?></div>
<button class="cl-read-more" style="color:var(--brand);" onclick="toggleAbout(this)" data-fr="Lire la suite →" data-en="Read more →">Lire la suite →</button>
</section>

<!-- HORAIRES -->
<section class="cl-section<?=$preview?' pc':''?>" id="el-horaires"<?=$preview?' data-section="horaires" data-hint="Horaires d\'ouverture"':''?>>
<div class="cl-section-title" data-fr="Horaires d'ouverture" data-en="Opening hours">Horaires d'ouverture</div>
<div class="cl-hor-content">
<?php
$hor_days = [
  ['Lundi','08:00','18:00',true],['Mardi','08:00','18:00',true],
  ['Mercredi','08:00','18:00',true],['Jeudi','08:00','18:00',true],
  ['Vendredi','08:00','19:00',true],['Samedi','09:00','17:00',true],
  ['Dimanche','','',false],
];
if(!$preview && !empty($av)):
  foreach($av as $a): $isOp=(int)$a['is_open']; ?>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:6px;background:<?=$isOp?'rgba(0,0,0,0.03)':'transparent'?>;font-size:1rem;">
    <span style="font-weight:600;color:<?=$isOp?'#333':'#bbb'?>;"><?=h($a['day_name'])?></span>
    <span style="color:<?=$isOp?'#555':'#bbb'?>;"><?=$isOp?h(substr($a['open_time']??'',0,5)).' – '.h(substr($a['close_time']??'',0,5)):'Fermé'?></span>
  </div>
<?php endforeach;
else: foreach($hor_days as [$fr,$op,$cl,$open]): ?>
  <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:6px;background:<?=$open?'rgba(0,0,0,0.03)':'transparent'?>;font-size:1rem;">
    <span style="font-weight:600;color:<?=$open?'#333':'#bbb'?>;"><?=$fr?></span>
    <span style="color:<?=$open?'#555':'#bbb'?>;"><?=$open?$op.' – '.$cl:'Fermé'?></span>
  </div>
<?php endforeach; endif; ?>
</div>
</section>

<!-- SERVICES -->
<section class="cl-section<?=$preview?' pc':''?>" id="el-services"<?=$preview?' data-section="services" data-hint="Gérer services"':''?>>
<div class="cl-section-title" data-fr="Nos services" data-en="Our services">Nos services</div>
<?php if(!empty($services)): ?>
<div class="cl-services-list" id="el-services-list">
<?php foreach($services as $svc): ?>
<div class="cl-service-item"<?=!$preview?' onclick="window.location.href=\''.BASE_URL.'/Reserver/Reserver.php?slug='.urlencode($biz['slug']).'\'"':''; ?>>
<div class="cl-svc-bar" style="background:<?=h($svc['color']??$biz['theme_color'])?>"></div>
<div class="cl-svc-info">
<div class="cl-svc-name" data-fr="<?=h($svc['name']??'')?>" data-en="<?=h($svc['name_en']??$svc['name']??'')?>"><?=h($svc['name']??'')?></div>
<div class="cl-svc-duration"><?=h($svc['duration']??'')?></div>
</div>
<?php if((int)$biz['show_prices']&&isset($svc['price'])): ?><div class="cl-svc-price"><?=number_format((int)$svc['price'])?> F</div><?php endif; ?>
<div class="cl-svc-arrow"><i class="fa-solid fa-chevron-right"></i></div>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="cl-services-placeholder"><i class="fa-solid fa-list-ul" style="color:var(--brand);font-size:24px;margin-bottom:8px;"></i><div>Les services seront affichés ici</div></div>
<?php endif; ?>
</section>

<!-- CONTACT -->
<section class="cl-section<?=$preview?' pc':''?>" id="el-contact"<?=$preview?' data-section="contact" data-hint="Éditer contact"':''?>>
<div class="cl-section-title" data-fr="Contact" data-en="Contact">Contact</div>
<div class="cl-contact-card">
<a href="https://wa.me/<?=preg_replace('/\D/','',$biz['whatsapp'])?>?text=Bonjour+<?=urlencode($biz['name'])?>+je+voudrais+prendre+un+RDV" target="<?=$preview?'_self':'_blank'?>" class="cl-wa-btn">
<i class="fa-brands fa-whatsapp cl-wa-icon"></i><span data-fr="Contacter sur WhatsApp" data-en="Contact on WhatsApp">Contacter sur WhatsApp</span>
</a>
<div class="cl-contact-row"><span data-fr="Ville" data-en="City">Ville</span><span id="el-location"><?=h($loc)?></span></div>
<div class="cl-contact-row"><span data-fr="Type" data-en="Type">Type</span><span id="el-type"><?=h($biz['type'])?></span></div>
</div></section>

<!-- FOOTER -->
<footer class="cl-footer<?=$preview?' pc':''?>" id="el-footer"<?=$preview?' data-section="footer" data-hint="Éditer footer"':''?> style="background:var(--footer-bg);border-top-color:var(--border-col);">
<?php if((int)$biz['show_lt_logo']): ?>
<div class="cl-footer-lt"><div class="cl-footer-lt-mark">LT</div><span class="cl-footer-lt-text" style="color:var(--footer-text);">Propulsé par <strong style="color:#C9A84C;">LionTech</strong> · LionRDV</span></div>
<?php endif; ?>
<div class="cl-footer-copy" style="color:var(--footer-text);">© 2026 <?=h($biz['name'])?> · lionrdv.cm/<?=h($biz['slug'])?></div>
</footer>

<!-- STICKY -->
<?php if(!$preview): ?>
<div class="cl-sticky-rdv">
<a href="<?= BASE_URL ?>/Reserver/Reserver.php?slug=<?=urlencode($biz['slug'])?>" class="cl-sticky-btn" style="background:var(--btn-color);" id="el-sticky">
<i class="fa-regular fa-calendar-check"></i><span data-fr="Prendre un RDV" data-en="Book an appointment">Prendre un RDV</span>
</a></div>
<?php else: ?>
<div class="cl-sticky-rdv pc" data-section="rdv" data-hint="Éditer bouton RDV">
<button class="cl-sticky-btn" style="background:var(--btn-color);" id="el-sticky">
<i class="fa-regular fa-calendar-check"></i><span data-fr="Prendre un RDV" data-en="Book an appointment">Prendre un RDV</span>
</button></div>
<?php endif; ?>

<script>
var PREVIEW=<?=$preview?'true':'false'?>;
var BIZ={name:<?=json_encode($biz['name'])?>,slug:<?=json_encode($biz['slug'])?>,lang:<?=json_encode($biz['language'])?>,defLang:<?=json_encode($defLang)?>,whatsapp:<?=json_encode($biz['whatsapp'])?>};
var currentLang=BIZ.defLang;
function setLang(l,btn){currentLang=l;document.querySelectorAll('[data-fr]').forEach(function(el){var v=l==='en'?el.dataset.en:el.dataset.fr;if(v!==undefined)el.innerHTML=v;});document.querySelectorAll('.cl-lang-btn').forEach(function(b){b.classList.remove('active');});if(btn)btn.classList.add('active');document.documentElement.lang=l;}
function toggleAbout(btn){var txt=document.querySelector('.cl-about-text');if(!txt)return;var exp=txt.classList.toggle('expanded');btn.textContent=exp?'Réduire ↑':'Lire la suite →';}
function setText(id,v){var el=document.getElementById(id);if(el)el.textContent=v;}
function hexRgba(hex,a){hex=hex.replace('#','');if(hex.length===3)hex=hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];var r=parseInt(hex.substring(0,2),16),g=parseInt(hex.substring(2,4),16),b=parseInt(hex.substring(4,6),16);return 'rgba('+r+','+g+','+b+','+a+')';}
function tLabel(v,o){var m={salon:'Salon de beauté',restaurant:'Restaurant',hotel:'Hôtellerie',medical:'Clinique / Médical',barber:'Barbier',fitness:'Sport & Fitness',photo:'Photographie',law:'Avocat / Cabinet',coach:'Coach',other:o||'Autre'};return m[v]||v||'Salon de beauté';}

if(PREVIEW){
  window.addEventListener('message',function(e){
    var msg=e.data; if(!msg||!msg.type)return;

    /* ── PREVIEW_UPDATE : données complètes du formulaire ── */
    if(msg.type==='PREVIEW_UPDATE'){
      var d=msg.data;
      var tc=d.primary_color||'#C9A84C';
      var bc=d.button_color||tc;
      var bgMap={'#D4447A':'#FFF0F8','#0A0A0A':'#FAFAF8','#0EA5E9':'#F0F9FF','#059669':'#F0FDF4','#E07B39':'#FFF8F3','#7C3AED':'#F5F3FF','#DC2626':'#FFF5F5','#1B4332':'#F9F6F0'};
      var tbg=bgMap[tc]||'#FFF9EE';
      var root=document.documentElement;

      /* ── Couleurs CSS variables ── */
      root.style.setProperty('--brand',tc);
      root.style.setProperty('--brand-bg',tbg);
      root.style.setProperty('--brand-rgba',hexRgba(tc,0.10));
      root.style.setProperty('--btn-color',bc);
      root.style.setProperty('--primary',tc);
      root.style.setProperty('--secondary',d.secondary_color||'#0A0A0A');
      root.style.setProperty('--text-color',d.text_color||'#222222');
      root.style.setProperty('--page-bg',d.background_color||'#ffffff');
      root.style.setProperty('--border-col',d.border_color||'#e5e7eb');

      /* ── Navbar & footer ── */
      var ns=d.navbar_style||'light';
      root.style.setProperty('--navbar-bg',ns==='dark'?(d.secondary_color||'#0A0A0A'):'#ffffff');
      root.style.setProperty('--navbar-text',ns==='dark'?'#ffffff':(d.text_color||'#222222'));
      var fs=d.footer_style||'minimal';
      var footBg=fs==='dark'?(d.secondary_color||'#0A0A0A'):fs==='branded'?tc:'#ffffff';
      root.style.setProperty('--footer-bg',footBg);
      root.style.setProperty('--footer-text',fs==='dark'||fs==='branded'?'rgba(255,255,255,0.8)':'#7A7570');
      var fo=document.getElementById('el-footer');
      if(fo){fo.style.background=footBg;}

      /* ── Fond de page & texture ── */
      var body=document.getElementById('el-body');
      var bgColor=d.background_color||'#ffffff';
      var tex=d.bg_texture||'none';
      if(body){
        body.style.backgroundColor=bgColor;
        if(tex==='dots'){body.style.backgroundImage='radial-gradient(#bbb 1px,transparent 1px)';body.style.backgroundSize='8px 8px';}
        else if(tex==='lines'){body.style.backgroundImage='repeating-linear-gradient(45deg,rgba(0,0,0,0.06) 0,rgba(0,0,0,0.06) 1px,transparent 0,transparent 10px)';body.style.backgroundSize='';}
        else if(tex==='grid'){body.style.backgroundImage='repeating-linear-gradient(0deg,rgba(0,0,0,0.05) 0,rgba(0,0,0,0.05) 1px,transparent 0,transparent 16px),repeating-linear-gradient(90deg,rgba(0,0,0,0.05) 0,rgba(0,0,0,0.05) 1px,transparent 0,transparent 16px)';body.style.backgroundSize='';}
        else{body.style.backgroundImage='none';body.style.backgroundSize='';}
      }

      /* ── Typographie globale ── */
      var gf=d.global_font||'system-ui';
      var gfs=d.global_font_size||'1rem';
      var gfw=d.global_font_weight||'400';
      body.style.fontFamily=gf;
      body.style.fontSize=gfs;
      body.style.fontWeight=gfw;

      /* ── Textes principaux ── */
      setText('el-biz-name',d.business_name||'Nom du business');
      setText('el-hero-name',d.business_name||'Nom du business');
      setText('el-desc',d.description||'Description...');
      var tl=tLabel(d.business_type,d.other_type);
      setText('el-biz-type',tl);setText('el-type',tl);
      var loc=[d.quarter,d.city].filter(Boolean).join(', ');
      setText('el-location',loc||'');
      var parts=(d.business_name||'MB').trim().split(/\s+/);
      var init=parts.slice(0,2).map(function(w){return w[0]?w[0].toUpperCase():'';}).join('');
      setText('el-initials',init||'MB');

      /* ── Boutons RDV ── */
      var rb=document.getElementById('el-rdv-btn');
      var sb=document.getElementById('el-sticky');
      var btnR=d.btn_radius||'0.5rem';
      var btnSt=d.btn_style||'filled';
      [rb,sb].forEach(function(btn){
        if(!btn)return;
        btn.style.borderRadius=btnR;
        if(btnSt==='pill')btn.style.borderRadius='999px';
        if(btnSt==='square')btn.style.borderRadius='0.25rem';
        if(btnSt==='outline'){btn.style.background='transparent';btn.style.color=bc;btn.style.border='2px solid '+bc;}
        else if(btnSt==='soft'){btn.style.background=hexRgba(bc,0.12);btn.style.color=bc;btn.style.border='1px solid '+hexRgba(bc,0.3);}
        else if(btnSt==='dark'){btn.style.background='#0A0A0A';btn.style.color='#C9A84C';btn.style.border='none';}
        else{btn.style.background=bc;btn.style.color='#ffffff';btn.style.border='none';}
      });
      /* Texte du bouton RDV */
      if(d.btn_text){document.querySelectorAll('#el-rdv-btn span,#el-sticky span').forEach(function(s){s.textContent=d.btn_text;});}

      /* ── Hero placeholder couleur ── */
      var hp=document.getElementById('el-hero-ph');if(hp)hp.style.background=tc;

      /* ── Bouton Connexion dans la navbar ── */
      var connBtn=document.getElementById('el-conn-btn');
      if(d.show_connexion_btn==1||d.show_connexion_btn==='1'||d.show_connexion_btn===true){
        if(!connBtn){
          connBtn=document.createElement('button');
          connBtn.id='el-conn-btn';
          connBtn.textContent='Connexion';
          connBtn.style.cssText='padding:6px 12px;border-radius:6px;border:none;font-size:0.875rem;font-weight:700;color:#fff;cursor:pointer;margin-right:6px;';
          var navRight=document.querySelector('.cl-nav-right');
          if(navRight)navRight.insertBefore(connBtn,navRight.firstChild);
        }
        connBtn.style.background=tc;
        connBtn.style.display='';
      } else {
        if(connBtn)connBtn.style.display='none';
      }

      /* ── Badge LionTech ── */
      var lt=document.getElementById('el-lt-badge');
      if(lt)lt.style.display=(d.show_liontech_logo==1||d.show_liontech_logo==='1')?'':'none';

      /* ── Logo business dans la navbar ── */
      var navLeft=document.querySelector('.cl-nav-left');
      var logoEl=document.getElementById('el-logo-img');
      var initEl=document.getElementById('el-initials');
      if(d.show_business_logo==0||d.show_business_logo==='0'){
        if(logoEl)logoEl.style.display='none';
        if(initEl)initEl.style.display='none';
      } else {
        if(logoEl)logoEl.style.display='';
        if(initEl)initEl.style.display='';
      }

      /* ── Toggle langue ── */
      var ltg=document.getElementById('el-lang-toggle');
      if(ltg)ltg.style.display=d.site_language==='bilingual'?'flex':'none';

      /* ── Section À propos : style ── */
      var descEl=document.getElementById('el-desc');
      if(descEl){
        if(d.about_font)descEl.style.fontFamily=d.about_font;
        if(d.about_font_size)descEl.style.fontSize=d.about_font_size;
        if(d.about_text_color)descEl.style.color=d.about_text_color;
      }

      /* ── Galerie : mode d'affichage + nombre de photos ── */
      applyGalleryMode(d.gal_mode||d.gal_display_mode||'grid', tc, d.gal_max_photos||9);

      /* ── Horaires : style d'affichage ── */
      applyHorStyle(d.hor_style||'list', tc);

      /* ── Services : style d'affichage ── */
      applySvcStyle(d.svc_style||d.svc_display_style||'list', tc);

      /* ── Ordre des sections ── */
      if(d.sections_order && d.sections_order.length) {
        applySectionsOrder(d.sections_order);
      }
    }

    /* ── PREVIEW_LOGO ── */
    if(msg.type==='PREVIEW_LOGO'){
      var img=document.getElementById('el-logo-img');
      var init2=document.getElementById('el-initials');
      if(img){img.src=msg.src;}
      else if(init2&&init2.parentNode){
        var ni=document.createElement('img');ni.id='el-logo-img';ni.src=msg.src;ni.className='cl-nav-logo-img';
        init2.parentNode.replaceChild(ni,init2);
      }
    }

    /* ── PREVIEW_COVER ── */
    if(msg.type==='PREVIEW_COVER'){
      var ce=document.getElementById('el-cover-img');
      var ph=document.getElementById('el-hero-ph');
      var hero=document.getElementById('el-hero');
      if(ce){ce.src=msg.src;}
      else{
        var nc=document.createElement('img');nc.id='el-cover-img';nc.src=msg.src;nc.className='cl-hero-img';
        if(ph&&ph.parentNode){ph.parentNode.replaceChild(nc,ph);}
        else if(hero){var ov=hero.querySelector('.cl-hero-overlay');hero.insertBefore(nc,ov||hero.firstChild);}
      }
    }

    /* ── PREVIEW_LANG : changer la langue de l'aperçu en direct ── */
    if(msg.type==='PREVIEW_LANG'){
      var ltg=document.getElementById('el-lang-toggle');
      if(msg.lang==='bilingual'){
        if(ltg) ltg.style.display='flex';
        setLang('fr', null);
      } else {
        if(ltg) ltg.style.display='none';
        setLang(msg.lang==='en'?'en':'fr', null);
      }
    }

    /* ── PREVIEW_OVERLAY_POS : déplacer un élément de la couverture ── */
    if(msg.type==='PREVIEW_OVERLAY_POS'){
      var item=document.querySelector('[data-overlay="'+msg.field+'"]');
      var zCentre=document.getElementById('el-hero-zone-centre');
      var zBas=document.getElementById('el-hero-zone-bas');
      if(item){
        if(msg.pos==='masqué'||msg.pos==='masque'){
          item.style.display='none';
        } else {
          item.style.display='';
          if(msg.pos==='bas' && zBas) zBas.appendChild(item);
          else if(zCentre) zCentre.appendChild(item);
        }
      }
    }

    /* ── PREVIEW_AVATAR ── */
    if(msg.type==='PREVIEW_AVATAR'){
      var av=document.getElementById('el-avatar');
      if(!av){
        av=document.createElement('div');av.id='el-avatar';
        av.style.cssText='position:absolute;bottom:-16px;right:12px;width:40px;height:40px;border-radius:50%;border:3px solid #fff;overflow:hidden;z-index:5;background:#f0ede8;';
        var hero2=document.getElementById('el-hero');if(hero2)hero2.appendChild(av);
      }
      av.innerHTML='<img src="'+msg.src+'" style="width:100%;height:100%;object-fit:cover;">';
    }

    /* ── PREVIEW_FONT ── */
    if(msg.type==='PREVIEW_FONT'){
      document.getElementById('el-body').style.fontFamily=msg.font;
    }

    /* ── PREVIEW_BG ── */
    if(msg.type==='PREVIEW_BG'){
      var body2=document.getElementById('el-body');
      if(body2){
        body2.style.backgroundColor=msg.bg||'#ffffff';
        if(msg.tex==='dots'){body2.style.backgroundImage='radial-gradient(#bbb 1px,transparent 1px)';body2.style.backgroundSize='8px 8px';}
        else if(msg.tex==='lines'){body2.style.backgroundImage='repeating-linear-gradient(45deg,rgba(0,0,0,0.06) 0,rgba(0,0,0,0.06) 1px,transparent 0,transparent 10px)';body2.style.backgroundSize='';}
        else if(msg.tex==='grid'){body2.style.backgroundImage='repeating-linear-gradient(0deg,rgba(0,0,0,0.05) 0,rgba(0,0,0,0.05) 1px,transparent 0,transparent 16px),repeating-linear-gradient(90deg,rgba(0,0,0,0.05) 0,rgba(0,0,0,0.05) 1px,transparent 0,transparent 16px)';body2.style.backgroundSize='';}
        else{body2.style.backgroundImage='none';}
      }
    }

    /* ── PREVIEW_BG_CUSTOM ── */
    if(msg.type==='PREVIEW_BG_CUSTOM'){
      var body3=document.getElementById('el-body');
      if(body3){
        var v=msg.css||'';
        if(v.startsWith('#')||v.startsWith('rgb')){body3.style.backgroundColor=v;body3.style.backgroundImage='none';}
        else{body3.style.backgroundImage=v;body3.style.backgroundSize='';}
      }
    }

    /* ── PREVIEW_CONN ── */
    if(msg.type==='PREVIEW_CONN'){
      var cb=document.getElementById('el-conn-btn');
      if(cb)cb.style.display=msg.show?'':'none';
    }
  });

  /* ── Clic sur un élément → signaler à parent ── */
  document.addEventListener('click',function(e){
    var c=e.target.closest('.pc');
    if(c&&c.dataset.section){e.preventDefault();e.stopPropagation();window.parent.postMessage({type:'SECTION_CLICK',section:c.dataset.section},'*');}
  });

  /* ══ FONCTIONS D'APPLICATION ══ */

  /* Galerie : change la structure DOM selon le mode ET le nombre de photos max */
  function applyGalleryMode(mode, tc, maxPhotos) {
    var gal = document.querySelector('.cl-gallery');
    if (!gal) return;
    var max = parseInt(maxPhotos) || 9;
    var a10 = hexRgba(tc, 0.10);
    var a12 = hexRgba(tc, 0.12);
    var a06 = hexRgba(tc, 0.06);
    var ic  = '<i class="fa-regular fa-image" style="color:'+tc+';font-size:16px;opacity:0.5;"></i>';
    var note = '<div style="text-align:center;font-size:0.8rem;color:'+tc+';margin-top:6px;font-style:italic;">Le propriétaire ajoutera ses photos · max '+max+'</div>';

    if (mode === 'grid') {
      /* 1 grande + (max-1) petites en grille 2 colonnes */
      var html = '<div class="cl-gallery-item cl-gallery-wide cl-gallery-ph" style="background:'+a12+';display:flex;align-items:center;justify-content:center;min-height:80px;">'+ic+'</div>';
      var small = Math.min(max - 1, 8);
      var colors = [a10, a06, a10, a06, a10, a06, a10, a06];
      for (var i = 0; i < small; i++) {
        html += '<div class="cl-gallery-item cl-gallery-ph" style="background:'+colors[i%colors.length]+';display:flex;align-items:center;justify-content:center;min-height:50px;">'+ic+'</div>';
      }
      gal.innerHTML = html + note;
      gal.style.cssText = 'display:grid;grid-template-columns:1fr 1fr;gap:5px;';

    } else if (mode === 'slideshow') {
      /* Diaporama : 1 grande zone avec dots */
      gal.innerHTML =
        '<div style="height:140px;border-radius:10px;background:'+a12+';display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;">'+
        '<i class="fa-solid fa-play-circle" style="font-size:2rem;color:'+tc+';opacity:0.5;"></i>'+
        '<div style="display:flex;gap:5px;">'+
          '<div style="width:18px;height:4px;border-radius:2px;background:'+tc+';"></div>'+
          [].constructor(Math.min(max-1,5)).fill('<div style="width:6px;height:4px;border-radius:50%;background:rgba(0,0,0,0.15);"></div>').join('')+
        '</div></div>'+
        note;
      gal.style.cssText = 'display:flex;flex-direction:column;gap:6px;';

    } else if (mode === 'circle') {
      /* Cercles : max photos en rond */
      var circleHtml = '';
      for (var j = 0; j < Math.min(max, 9); j++) {
        var bg = j % 3 === 0 ? a12 : (j % 3 === 1 ? a10 : a06);
        circleHtml += '<div style="width:70px;height:70px;border-radius:50%;background:'+bg+';display:flex;align-items:center;justify-content:center;flex-shrink:0;">'+ic+'</div>';
      }
      gal.innerHTML =
        '<div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">'+circleHtml+'</div>'+
        note;
      gal.style.cssText = 'display:flex;flex-direction:column;gap:6px;';

    } else if (mode === 'portrait') {
      /* Portrait : colonnes verticales hautes */
      var portHtml = '';
      var cols = Math.min(max, 3);
      for (var p = 0; p < cols; p++) {
        var pbg = p === 0 ? a12 : (p === 1 ? a10 : a06);
        portHtml += '<div style="border-radius:8px;background:'+pbg+';display:flex;align-items:center;justify-content:center;min-height:80px;">'+ic+'</div>';
      }
      gal.innerHTML =
        '<div style="display:grid;grid-template-columns:repeat('+cols+',1fr);gap:6px;">'+portHtml+'</div>'+
        note;
      gal.style.cssText = 'display:flex;flex-direction:column;gap:6px;';

    } else if (mode === 'square') {
      /* Carré : grille de carrés égaux */
      var sqHtml = '';
      var cols2 = 3;
      var rows = Math.ceil(Math.min(max, 9) / cols2);
      var total = Math.min(max, 9);
      var sqColors = [a12, a10, a06, a10, a06, a12, a06, a10, a12];
      for (var s = 0; s < total; s++) {
        sqHtml += '<div style="aspect-ratio:1;border-radius:6px;background:'+sqColors[s % sqColors.length]+';display:flex;align-items:center;justify-content:center;">'+ic+'</div>';
      }
      gal.innerHTML =
        '<div style="display:grid;grid-template-columns:repeat('+cols2+',1fr);gap:5px;">'+sqHtml+'</div>'+
        note;
      gal.style.cssText = 'display:flex;flex-direction:column;gap:6px;';
    }
  }

  /* Horaires : applique le style d'affichage (list / cards / badges) */
  function applyHorStyle(style, tc) {
    var sec = document.getElementById('el-horaires');
    if (!sec) return;
    var list = sec.querySelector('.cl-hor-content');
    if (!list) {
      list = document.createElement('div');
      list.className = 'cl-hor-content';
      /* Insérer après le titre de section */
      var titre = sec.querySelector('.cl-section-title');
      if (titre && titre.nextSibling) sec.insertBefore(list, titre.nextSibling);
      else sec.appendChild(list);
    }
    /* Données horaires de la preview */
    var days = [
      {fr:'Lundi',    en:'Monday',    open:'08:00', close:'18:00', isOpen:true},
      {fr:'Mardi',    en:'Tuesday',   open:'08:00', close:'18:00', isOpen:true},
      {fr:'Mercredi', en:'Wednesday', open:'08:00', close:'18:00', isOpen:true},
      {fr:'Jeudi',    en:'Thursday',  open:'08:00', close:'18:00', isOpen:true},
      {fr:'Vendredi', en:'Friday',    open:'08:00', close:'19:00', isOpen:true},
      {fr:'Samedi',   en:'Saturday',  open:'09:00', close:'17:00', isOpen:true},
      {fr:'Dimanche', en:'Sunday',    open:'',      close:'',      isOpen:false},
    ];
    var html = '';
    if (style === 'list' || !style) {
      list.style.cssText = 'display:flex;flex-direction:column;gap:3px;';
      days.forEach(function(d) {
        var bg = d.isOpen ? 'rgba(0,0,0,0.03)' : 'transparent';
        var color = d.isOpen ? '#333' : '#bbb';
        var hours = d.isOpen ? d.open+' – '+d.close : 'Fermé';
        html += '<div style="display:flex;justify-content:space-between;align-items:center;padding:5px 8px;border-radius:6px;background:'+bg+';font-size:0.875rem;">'
             +  '<span style="font-weight:600;color:'+color+';">'+d.fr+'</span>'
             +  '<span style="color:'+(d.isOpen?'#555':'#bbb')+';">'+hours+'</span></div>';
      });
    } else if (style === 'cards') {
      list.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;';
      days.filter(function(d){return d.isOpen;}).forEach(function(d) {
        html += '<div style="padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;text-align:center;min-width:70px;">'
             +  '<div style="font-size:0.8rem;font-weight:700;color:#333;">'+d.fr.slice(0,3)+'</div>'
             +  '<div style="font-size:0.75rem;color:#888;margin-top:3px;">'+d.open+'</div>'
             +  '<div style="font-size:0.75rem;color:#888;">'+d.close+'</div></div>';
      });
    } else if (style === 'badges') {
      list.style.cssText = 'display:flex;flex-wrap:wrap;gap:5px;';
      days.forEach(function(d) {
        var bg = d.isOpen ? tc : '#e5e7eb';
        var col = d.isOpen ? '#fff' : '#aaa';
        html += '<div style="padding:4px 10px;border-radius:20px;background:'+bg+';color:'+col+';font-size:0.8rem;font-weight:600;">'+d.fr.slice(0,3)+'</div>';
      });
    }
    list.innerHTML = html;
  }

  /* Ordre des sections : réorganise les sections dans le DOM */
  function applySectionsOrder(order) {
    if (!order || !order.length) return;
    var main = document.getElementById('el-body');
    var sectionMap = {
      rdv:      document.getElementById('el-rdv-sec'),
      services: document.getElementById('el-services'),
      horaires: document.getElementById('el-horaires'),
      about:    document.getElementById('el-about'),
      gallery:  document.getElementById('el-gallery'),
      contact:  document.getElementById('el-contact'),
    };
    /* Trouver l'ancre : la barre de statut (après hero) */
    var anchor = document.querySelector('.cl-status-bar');
    if (!anchor) return;
    var parent = anchor.parentNode;
    /* Insérer les sections dans l'ordre voulu après la barre de statut */
    var insertAfter = anchor;
    order.forEach(function(id) {
      var el = sectionMap[id];
      if (!el) return;
      /* Insérer après le nœud courant */
      if (insertAfter.nextSibling) {
        parent.insertBefore(el, insertAfter.nextSibling);
      } else {
        parent.appendChild(el);
      }
      insertAfter = el;
    });
  }

  /* Services : change le style d'affichage */
  function applySvcStyle(style, tc) {
    var list = document.getElementById('el-services-list');
    if (!list) return;
    /* Sauvegarder les données avant de vider */
    var svcs = [];
    list.querySelectorAll('.cl-service-item').forEach(function(item) {
      var nm  = item.querySelector('.cl-svc-name');
      var dur = item.querySelector('.cl-svc-duration');
      var pr  = item.querySelector('.cl-svc-price');
      var bar = item.querySelector('.cl-svc-bar');
      if (nm && nm.textContent.trim()) {
        svcs.push({
          name:  nm.textContent.trim(),
          dur:   dur ? dur.textContent.trim() : '',
          price: pr  ? pr.textContent.trim()  : '',
          color: bar ? bar.style.background   : tc,
        });
      }
    });
    /* Fallback si la liste est vide (style vient d'être appliqué) */
    if (!svcs.length) {
      svcs = [
        {name:'Service exemple 1', dur:'45 min', price:'2 500 F', color:tc},
        {name:'Service exemple 2', dur:'1h',     price:'5 000 F', color:tc},
        {name:'Service exemple 3', dur:'1h30',   price:'7 000 F', color:tc},
      ];
    }
    var html = '';
    if (style === 'pills') {
      list.style.cssText = 'display:flex;flex-wrap:wrap;gap:8px;';
      svcs.forEach(function(s) {
        html += '<div style="padding:6px 14px;border-radius:20px;border:1.5px solid '+s.color+';color:'+s.color+';font-size:0.875rem;font-weight:600;background:'+hexRgba(s.color,0.08)+';">'+s.name+'</div>';
      });
    } else if (style === 'cards') {
      list.style.cssText = 'display:grid;grid-template-columns:1fr 1fr;gap:8px;';
      svcs.forEach(function(s) {
        html += '<div style="padding:12px;border-radius:10px;border:1px solid #e5e7eb;text-align:center;">'
             +  '<div style="font-size:0.9375rem;font-weight:700;color:#111;">'+s.name+'</div>'
             +  '<div style="font-size:0.8125rem;color:#888;margin-top:3px;">'+s.dur+'</div>'
             +  '<div style="font-size:0.875rem;font-weight:700;color:'+s.color+';margin-top:5px;">'+s.price+'</div></div>';
      });
    } else if (style === 'buttons') {
      list.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;';
      svcs.forEach(function(s) {
        html += '<button style="padding:8px 14px;border-radius:8px;background:'+s.color+';color:#fff;border:none;font-size:0.875rem;font-weight:600;cursor:pointer;">'+s.name+'</button>';
      });
    } else if (style === 'text') {
      list.style.cssText = 'display:flex;flex-direction:column;gap:6px;';
      svcs.forEach(function(s) {
        html += '<div style="display:flex;align-items:center;gap:8px;font-size:0.9375rem;">'
             +  '<div style="width:6px;height:6px;border-radius:50%;background:'+s.color+';flex-shrink:0;"></div>'
             +  s.name
             +  '<span style="margin-left:auto;font-size:0.8125rem;color:#888;">'+s.dur+'</span></div>';
      });
    } else {
      /* list (défaut) */
      list.style.cssText = 'display:flex;flex-direction:column;';
      svcs.forEach(function(s) {
        html += '<div class="cl-service-item">'
             +  '<div class="cl-svc-bar" style="background:'+s.color+'"></div>'
             +  '<div class="cl-svc-info">'
             +    '<div class="cl-svc-name">'+s.name+'</div>'
             +    '<div class="cl-svc-duration">'+s.dur+'</div>'
             +  '</div>'
             +  '<div class="cl-svc-price">'+s.price+'</div>'
             +  '<div class="cl-svc-arrow"><i class="fa-solid fa-chevron-right"></i></div>'
             +  '</div>';
      });
    }
    list.innerHTML = html;
  }
}
</script>
</body></html>