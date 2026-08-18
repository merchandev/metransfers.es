<?php
/**
 * The template for the front page
 *
 * @package Me_Transfers
 */

get_header();

$img    = get_template_directory_uri() . '/assets/img';
$bdi    = $img . '/bdi';
$arts   = $bdi . '/articles';
?>
<!-- ──────────────────────────────────────────────────────────────
     FRONT PAGE — MeTransfers 2.0
     Fuentes: Archivo (display), Source Sans 3 (body), Source Serif 4 (citas)
────────────────────────────────────────────────────────────────── -->
<style>
/* =========================================================
   RESET & BASE
   ========================================================= */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
img{max-width:100%;height:auto;display:block;}
a{text-decoration:none;}

/* =========================================================
   TOKENS
   ========================================================= */
:root{
  --blue:   #075EA8;
  --deep:   #0B1F35;
  --light:  #EAF4FC;
  --warm:   #F7F6F2;
  --white:  #FFFFFF;
  --ink:    #15202B;
  --muted:  #5E6873;
  --border: #DDE3E8;
  --gold:   #FFB547;

  --ff-head: 'Archivo', sans-serif;
  --ff-body: 'Source Sans 3', sans-serif;
  --ff-cita: 'Source Serif 4', serif;

  --r-sm:  8px;
  --r-md: 14px;
  --r-lg: 20px;

  --ease: cubic-bezier(.16,1,.3,1);
  --tr:   .25s var(--ease);

  --sp:  80px;   /* section padding */
  --cw:  80%; /* container width  */
}

/* =========================================================
   UTILIDADES
   ========================================================= */
.wrap{max-width:var(--cw);margin:0 auto;padding:0 24px;}
.sp{padding:var(--sp) 0;}
.tc{text-align:center;}
.tag{
  display:inline-flex;align-items:center;gap:10px;
  font:700 12px/1 var(--ff-body);letter-spacing:2px;text-transform:uppercase;
  color:var(--blue);margin-bottom:18px;
}
.tag::after{content:'';width:24px;height:2px;background:var(--blue);display:block;}

/* =========================================================
   TIPOGRAFÍA — secciones claras
   ========================================================= */
.fp h1{font:700 clamp(40px,5.5vw,68px)/1.05 var(--ff-head);letter-spacing:-.03em;color:var(--ink);}
.fp h2{font:700 clamp(32px,4vw,50px)/1.1 var(--ff-head);letter-spacing:-.02em;color:var(--ink);}
.fp h3{font:700 clamp(20px,2vw,24px)/1.25 var(--ff-head);color:var(--ink);}
.fp p {font:400 clamp(15px,1.5vw,17px)/1.65 var(--ff-body);color:var(--muted);}
.fp .lead{font-size:clamp(17px,2vw,20px);line-height:1.55;color:var(--muted);max-width:660px;}

/* =========================================================
   CONTRASTE EN FONDOS OSCUROS — mayor especificidad para ganar
   a cualquier regla del tema (.fp h1 tiene especificidad 0,1,1;
   estas reglas tienen 0,2,1 — siempre ganan)
   ========================================================= */
.hero .fp h1, .hero h1,
.hero .hero__h1           { color: #ffffff !important; }
.hero .hero__lead,
.hero .lead               { color: rgba(255,255,255,.85) !important; }
.hero .hero__checks       { color: rgba(255,255,255,.72) !important; }
.hero .hero__stat-n       { color: #ffffff !important; }
.hero .hero__stat-l       { color: rgba(255,255,255,.58) !important; }

/* Sección Tours (dark) */
.tours-section .fp h2,
.tours-section h2         { color: #ffffff !important; }
.tours-section .lead,
.tours-section p          { color: rgba(255,255,255,.78) !important; }
.tours-section .tag       { color: var(--gold) !important; }

/* Sección CTA (dark) */
.cta .fp h2, .cta h2      { color: #ffffff !important; }
.cta .cta__lead           { color: rgba(255,255,255,.82) !important; }
.cta .tag                 { color: var(--gold) !important; }

/* Tour cards — texto sobre imagen oscura */
.tour__content h3         { color: #ffffff !important; }
.tour__content p          { color: rgba(255,255,255,.82) !important; }
.tour__price              { color: var(--gold) !important; }

/* =========================================================
   BOTONES
   ========================================================= */
.btn{
  display:inline-flex;align-items:center;gap:8px;
  padding:14px 28px;border-radius:var(--r-sm);
  font:600 15px/1 var(--ff-body);letter-spacing:.3px;
  border:2px solid transparent;cursor:pointer;
  transition:var(--tr);
}
.btn-solid{background:var(--blue);color:#fff;}
.btn-solid:hover{background:var(--deep);transform:translateY(-2px);}
.btn-ghost{border-color:var(--border);color:var(--ink);}
.btn-ghost:hover{border-color:var(--blue);color:var(--blue);}
.btn-ghost-inv{border-color:rgba(255,255,255,.35);color:#fff;}
.btn-ghost-inv:hover{border-color:#fff;background:rgba(255,255,255,.08);}
.btn-link{color:var(--blue);font:700 15px/1 var(--ff-body);display:inline-flex;align-items:center;gap:6px;}
.btn-link svg{transition:transform .2s;}
.btn-link:hover{color:var(--deep);}
.btn-link:hover svg{transform:translateX(4px);}

/* =========================================================
   1. HERO
   ========================================================= */
.hero{
  position:relative;min-height:88vh;display:flex;align-items:center;
  background:var(--deep);overflow:hidden;padding:120px 0 64px;
}
.hero__bg{
  position:absolute;inset:0;width:100%;height:100%;
  object-fit:cover;opacity:.32;
  animation:hzoom 18s ease-out forwards;
}
@keyframes hzoom{from{transform:scale(1.06)} to{transform:scale(1)}}
.hero__grad{
  position:absolute;inset:0;
  background:linear-gradient(110deg,rgba(11,31,53,.92) 35%,rgba(11,31,53,.35) 100%);
}
.hero__grid{
  position:relative;z-index:2;
  display:grid;grid-template-columns:3fr 2fr;gap:56px;align-items:center;
}
@media(max-width:1024px){.hero__grid{grid-template-columns:1fr;gap:40px;}}

/* left */
.hero__h1{color:#fff;font-size:clamp(1.25rem, 1.65vw, 1.65rem);line-height:1.2;}
.hero__lead{color:rgba(255,255,255,.82);font-size:19px;margin-top:20px;}
.hero__checks{
  display:flex;flex-wrap:wrap;gap:8px 20px;margin-top:24px;
  font:600 13px/1 var(--ff-body);color:rgba(255,255,255,.7);
}
.hero__checks span{display:flex;align-items:center;gap:6px;}
.hero__checks svg{color:var(--gold);}
.hero__ctas{display:flex;gap:14px;flex-wrap:wrap;margin-top:32px;}
.hero__stats{
  display:grid;grid-template-columns:repeat(3,1fr);gap:16px;
  margin-top:32px;padding-top:24px;
  border-top:1px solid rgba(255,255,255,.14);
}
.hero__stat-n{font:700 28px/1 var(--ff-head);color:#fff;}
.hero__stat-l{font:600 11px/1.3 var(--ff-body);color:rgba(255,255,255,.55);text-transform:uppercase;letter-spacing:0;margin-top:4px;}

/* panel reserva */
.hero__panel{
  background:#fff;border-radius:var(--r-lg);padding:36px 32px;
  box-shadow:0 24px 60px rgba(0,0,0,.22);
  position:relative;overflow:hidden;
}
.hero__panel::before{
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  background:var(--blue);
}
.hero__panel h3{font:700 21px/1.2 var(--ff-head);color:var(--ink);margin-bottom:24px;}
.hero__panel h2{font:700 18px/1.2 var(--ff-head);color:var(--ink);margin-bottom:20px;white-space:nowrap;}
.hero__panel .btn-solid{width:100%;justify-content:center;margin-top:16px;font-size:16px;padding:16px;}
.hero__panel .micro{text-align:center;font-size:12px;color:var(--muted);margin-top:12px;}

/* =========================================================
   2. SERVICIOS
   ========================================================= */
.srv__grid{
  display:grid;grid-template-columns:repeat(2,1fr);gap:2px;
  margin-top:56px;background:var(--border);
  border:2px solid var(--border);border-radius:var(--r-lg);overflow:hidden;
}
@media(max-width:680px){.srv__grid{grid-template-columns:1fr;}}
.srv__item{
  background:#fff;padding:40px 36px;position:relative;overflow:hidden;
  transition:var(--tr);display:flex;flex-direction:column;
}
.srv__item:hover{background:var(--warm);}
.srv__num{
  position:absolute;right:16px;top:8px;
  font:900 80px/1 var(--ff-head);color:var(--light);
  transition:var(--tr);pointer-events:none;
}
.srv__item:hover .srv__num{color:#ddeef9;}
.srv__ico{
  width:44px;height:44px;border-radius:10px;
  background:var(--light);color:var(--blue);
  display:flex;align-items:center;justify-content:center;
  margin-bottom:20px;flex-shrink:0;
}
.srv__item h3{font-size:21px;margin-bottom:12px;}
.srv__item p{font-size:15px;flex-grow:1;margin-bottom:20px;}

/* =========================================================
   3. CÓMO FUNCIONA
   ========================================================= */
.how__grid{
  display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;
}
@media(max-width:900px){.how__grid{grid-template-columns:1fr;gap:48px;}}
.how__steps{display:flex;flex-direction:column;gap:0;position:relative;}
.how__steps::before{
  content:'';position:absolute;left:27px;top:28px;bottom:28px;
  width:2px;background:var(--border);
}
.how__step{display:flex;gap:24px;padding:20px 0;}
.how__n{
  width:56px;height:56px;border-radius:50%;border:2px solid var(--blue);
  background:#fff;color:var(--blue);font:700 20px/56px var(--ff-head);
  text-align:center;flex-shrink:0;position:relative;z-index:1;
}
.how__text h3{font-size:20px;margin-bottom:6px;padding-top:14px;}
.how__text p{font-size:15px;}

/* =========================================================
   4. VENTAJAS
   ========================================================= */
.vent__grid{
  display:grid;grid-template-columns:1fr 1fr;gap:72px;align-items:center;
}
@media(max-width:900px){.vent__grid{grid-template-columns:1fr;}}
.vent__img-wrap{position:relative;}
.vent__img-wrap::after{
  content:'';position:absolute;top:-16px;left:-16px;right:16px;bottom:16px;
  border:2px solid var(--light);border-radius:var(--r-lg);z-index:0;
}
.vent__img{
  position:relative;z-index:1;border-radius:var(--r-lg);
  width:100%;aspect-ratio:4/5;object-fit:cover;
}
.vent__list{list-style:none;margin-top:36px;display:flex;flex-direction:column;gap:18px;}
.vent__list li{display:flex;gap:14px;align-items:flex-start;font:500 17px/1.5 var(--ff-body);color:var(--ink);}
.vent__list svg{flex-shrink:0;color:var(--blue);margin-top:3px;}
.vent__note{
  margin-top:28px;padding:18px 20px;background:var(--light);
  border-left:3px solid var(--blue);border-radius:0 var(--r-sm) var(--r-sm) 0;
  font-size:14px;color:var(--muted);
}

/* =========================================================
   5. FLOTA
   ========================================================= */
.fleet__grid{
  display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-top:56px;
}
@media(max-width:900px){.fleet__grid{grid-template-columns:1fr;}}
.fleet__card{
  border:1px solid var(--border);border-radius:var(--r-lg);
  background:#fff;overflow:hidden;
  transition:var(--tr);display:flex;flex-direction:column;
}
.fleet__card:hover{box-shadow:0 12px 40px rgba(7,94,168,.12);border-color:rgba(7,94,168,.3);transform:translateY(-4px);}
.fleet__img-wrap{position:relative;overflow:hidden;aspect-ratio:3/2;}
.fleet__img{width:100%;height:100%;object-fit:cover;transition:transform .5s var(--ease);}
.fleet__card:hover .fleet__img{transform:scale(1.04);}
.fleet__badge{
  position:absolute;bottom:12px;left:12px;
  background:rgba(11,31,53,.85);color:#fff;
  font:700 11px/1 var(--ff-body);letter-spacing:1px;text-transform:uppercase;
  padding:6px 12px;border-radius:30px;backdrop-filter:blur(4px);
}
.fleet__body{padding:28px;flex-grow:1;display:flex;flex-direction:column;}
.fleet__cat{font:700 11px/1 var(--ff-body);letter-spacing:1.5px;text-transform:uppercase;color:var(--blue);margin-bottom:10px;}
.fleet__body h3{font-size:23px;margin-bottom:12px;}
.fleet__body p{font-size:15px;flex-grow:1;margin-bottom:20px;}
.fleet__specs{
  border-top:1px solid var(--border);padding-top:16px;margin-bottom:20px;
  display:flex;flex-direction:column;gap:10px;
}
.fleet__spec{display:flex;align-items:center;gap:10px;font-size:14px;color:var(--muted);}
.fleet__spec svg{color:var(--blue);flex-shrink:0;}
.fleet__note{font-size:13px;text-align:center;margin-top:40px;color:var(--muted);font-style:italic;}

/* =========================================================
   6. RUTAS
   ========================================================= */
.routes__grid{
  display:grid;grid-template-columns:repeat(2,1fr);gap:0;
  margin-top:48px;border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;
}
@media(max-width:680px){.routes__grid{grid-template-columns:1fr;}}
.route__item{
  display:flex;align-items:center;justify-content:space-between;gap:20px;
  padding:22px 28px;border-bottom:1px solid var(--border);border-right:1px solid var(--border);
  background:#fff;transition:var(--tr);
}
.route__item:hover{background:var(--warm);}
/* Remove double border on right column */
.route__item:nth-child(even){border-right:none;}
/* Remove bottom border on last two */
.route__item:nth-last-child(-n+2){border-bottom:none;}
@media(max-width:680px){
  .route__item{border-right:none;}
  .route__item:nth-last-child(-n+2){border-bottom:1px solid var(--border);}
  .route__item:last-child{border-bottom:none;}
}
.route__info h3{font-size:16px;margin-bottom:4px;}
.route__info p{font-size:14px;}
.route__time{
  flex-shrink:0;background:var(--light);color:var(--blue);
  font:700 13px/1 var(--ff-body);padding:6px 12px;border-radius:20px;white-space:nowrap;
}

/* =========================================================
   7. TOURS
   ========================================================= */
.tours-section{background:var(--deep);}
.tours-section .tag{color:var(--gold);}
.tours-section .tag::after{background:var(--gold);}
.tours-section h2,.tours-section .lead{color:#fff;}
.tours__grid{
  display:grid;
  /* 2 columnas: left=tour principal, right=3 tours apilados */
  grid-template-columns:1fr 1fr;
  grid-template-rows:auto auto auto;
  gap:20px;margin-top:56px;
}
@media(max-width:800px){.tours__grid{grid-template-columns:1fr;}}
.tour__card{
  position:relative;border-radius:var(--r-md);overflow:hidden;
  display:flex;cursor:default;min-height:220px;
  transition:transform .3s var(--ease), box-shadow .3s var(--ease);
}
.tour__card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,0.35);}
.tour__card::after{
  content:'';position:absolute;inset:0;
  background:linear-gradient(to top,rgba(11,31,53,.95) 0%,rgba(11,31,53,.2) 60%,transparent 100%);
  transition:opacity .3s;
}
.tour__card:hover::after{opacity:.9;}
.tour__img{
  position:absolute;inset:0;width:100%;height:100%;
  background-size:cover;background-position:center;
  transition:transform .55s var(--ease);
}
.tour__card:hover .tour__img{transform:scale(1.06);}
.tour__content{
  position:relative;z-index:2;margin-top:auto;padding:28px 24px;
}
.tour__content h3{font:700 22px/1.2 var(--ff-head);color:#fff;margin-bottom:6px;}
.tour__content p{font-size:15px;color:rgba(255,255,255,.82);margin-bottom:14px;display:block;}
.tour__cta-btn{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,0.15);
  border:1px solid rgba(255,255,255,0.35);
  color:#fff;font:600 13px/1 var(--ff-body);
  letter-spacing:0.04em;padding:9px 16px;border-radius:50px;
  text-decoration:none;cursor:pointer;
  transition:background .2s, border-color .2s, transform .2s;
  backdrop-filter:blur(4px);
}
.tour__cta-btn:hover{
  background:rgba(255,255,255,0.25);
  border-color:rgba(255,255,255,0.6);
  transform:translateY(-1px);
}
/* Main tour = spans 3 rows in left column */
.tour__main{grid-column:1;grid-row:1 / span 3;min-height:520px;}
.tour__main .tour__content h3{font-size:30px;}
@media(max-width:800px){
  .tour__main{grid-column:1;grid-row:auto;min-height:280px;}
  .tour__card{min-height:220px;}
  .tour__card::after{background:linear-gradient(to top,rgba(11,31,53,.98) 0%,rgba(11,31,53,.7) 100%);}
}

/* =========================================================
   8. OPINIONES
   ========================================================= */
.rev__grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px;
}
@media(max-width:900px){.rev__grid{grid-template-columns:1fr;}}
.rev__card{
  background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);
  padding:36px 32px;position:relative;
}
.rev__quote{
  font:400 italic 18px/1.7 var(--ff-cita);color:var(--ink);margin-bottom:24px;
}
.rev__author{font:700 16px/1 var(--ff-body);color:var(--ink);}
.rev__meta{font:400 14px/1 var(--ff-body);color:var(--muted);margin-top:6px;}
.rev__stars{color:var(--gold);font-size:14px;letter-spacing:1px;margin-bottom:16px;}

/* Badge GYG */
.gyg-badge{
  display:inline-flex;align-items:center;gap:10px;
  background:#fff;border:1px solid var(--border);
  padding:12px 20px;border-radius:30px;
  font:600 15px/1 var(--ff-body);color:var(--ink);
  margin-top:24px;
}

/* =========================================================
   9. FAQ
   ========================================================= */
.faq__wrap{
  max-width:780px;margin:56px auto 0;
}
.faq__item{border-bottom:1px solid var(--border);}
.faq__item:first-child{border-top:1px solid var(--border);}
.faq__q{
  width:100%;text-align:left;background:none;border:none;
  padding:22px 0;cursor:pointer;
  display:flex;justify-content:space-between;align-items:center;gap:20px;
  font:700 17px/1.3 var(--ff-body);color:var(--ink);transition:color .2s;
}
.faq__q:hover{color:var(--blue);}
.faq__icon{
  width:28px;height:28px;border-radius:50%;border:2px solid var(--border);
  flex-shrink:0;display:flex;align-items:center;justify-content:center;
  transition:var(--tr);
}
.faq__item.open .faq__q{color:var(--blue);}
.faq__item.open .faq__icon{background:var(--blue);border-color:var(--blue);}
.faq__icon::before{content:'+';color:var(--muted);font:700 18px/1 var(--ff-body);}
.faq__item.open .faq__icon::before{content:'−';color:#fff;}
.faq__a{
  display:none;padding:0 0 22px 0;
  font:400 16px/1.7 var(--ff-body);color:var(--muted);
}
.faq__item.open .faq__a{display:block;animation:fadeIn .25s var(--ease);}
@keyframes fadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none}}

/* =========================================================
   10. BLOG
   ========================================================= */
.blog__grid{
  display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:56px;
}
@media(max-width:900px){.blog__grid{grid-template-columns:1fr;}}
.blog__card{
  background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);
  overflow:hidden;display:flex;flex-direction:column;transition:var(--tr);
}
.blog__card:hover{box-shadow:0 8px 30px rgba(0,0,0,.07);transform:translateY(-4px);}
.blog__img{width:100%;aspect-ratio:16/10;object-fit:cover;background:var(--warm);}
.blog__body{padding:28px;flex-grow:1;display:flex;flex-direction:column;}
.blog__cat{font:700 11px/1 var(--ff-body);letter-spacing:1.5px;text-transform:uppercase;color:var(--blue);margin-bottom:12px;}
.blog__body h3{font-size:20px;line-height:1.35;margin-bottom:12px;flex-grow:1;}
.blog__body h3 a{color:var(--ink);}
.blog__body h3 a:hover{color:var(--blue);}
.blog__meta{
  display:flex;justify-content:space-between;align-items:center;
  border-top:1px solid var(--border);padding-top:18px;margin-top:16px;
  font-size:13px;color:var(--muted);
}

/* =========================================================
   11. CTA FINAL
   ========================================================= */
.cta{background:var(--deep);padding:90px 0;overflow:hidden;position:relative;}
.cta::before{
  content:'';position:absolute;right:-5%;top:-30%;
  width:500px;height:500px;border-radius:50%;
  background:radial-gradient(ellipse,rgba(7,94,168,.25) 0%,transparent 70%);
  pointer-events:none;
}
.cta__inner{
  display:flex;justify-content:space-between;align-items:center;gap:48px;
  position:relative;z-index:1;
}
@media(max-width:900px){.cta__inner{flex-direction:column;text-align:center;}}
.cta h2,.cta .tag{color:#fff;}
.cta .tag{color:var(--gold);}
.cta .tag::after{background:var(--gold);}
.cta__lead{color:rgba(255,255,255,.8);font-size:18px;margin-top:12px;}
.cta__btns{display:flex;gap:14px;flex-shrink:0;}
@media(max-width:600px){.cta__btns{flex-direction:column;width:100%;}}

/* =========================================================
   12. CONTACTO
   ========================================================= */
.contact__grid{
  display:grid;grid-template-columns:1fr 1.3fr;gap:72px;margin-top:64px;align-items:start;
}
@media(max-width:900px){.contact__grid{grid-template-columns:1fr;gap:48px;}}
.contact__list{list-style:none;margin-top:0;display:flex;flex-direction:column;gap:36px;}
.contact__li{display:flex;gap:22px;align-items:center;}
.contact__ico{
  width:54px;height:54px;border-radius:14px;background:var(--light);
  color:var(--blue);display:flex;align-items:center;justify-content:center;flex-shrink:0;
  box-shadow: 0 4px 12px rgba(7,94,168,.06);
}
.contact__label{font:700 11px/1 var(--ff-body);letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
.contact__val{font:700 20px/1.2 var(--ff-head);color:var(--ink);display:block;}
a.contact__val{transition:color .2s;}
a.contact__val:hover{color:var(--blue);}

/* form */
.cform{background:#fff;border:1px solid var(--border);border-radius:var(--r-lg);padding:44px 40px;}
@media(max-width:600px){.cform{padding:28px 24px;}}
.cform__row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:600px){.cform__row{grid-template-columns:1fr;}}
.fg{margin-bottom:20px;}
.fg label{display:block;font:700 13px/1 var(--ff-body);color:var(--ink);margin-bottom:8px;}
.fg input,.fg select,.fg textarea{
  width:100%;padding:14px 16px;
  border:1px solid var(--border);border-radius:var(--r-sm);
  font:400 16px/1 var(--ff-body);color:var(--ink);
  background:var(--warm);transition:var(--tr);
}
.fg input:focus,.fg select:focus,.fg textarea:focus{
  outline:none;border-color:var(--blue);background:#fff;
  box-shadow:0 0 0 3px rgba(7,94,168,.1);
}
.fg textarea{resize:vertical;min-height:130px;line-height:1.5;}
.fg select{cursor:pointer;}
.cform__check{display:flex;gap:10px;align-items:flex-start;font-size:14px;color:var(--muted);margin:24px 0 28px;}
.cform__check input{margin-top:3px;accent-color:var(--blue);width:16px;height:16px;flex-shrink:0;}
.cform__submit{width:100%;padding:17px;font-size:17px;}
.cform__ok{
  display:none;margin-top:18px;padding:14px 18px;
  background:rgba(0,180,99,.1);border:1px solid rgba(0,180,99,.3);
  border-radius:var(--r-sm);color:#00832e;font:600 15px/1.4 var(--ff-body);text-align:center;
}

/* =========================================================
   SECTION BACKGROUNDS
   ========================================================= */
.bg-white{background:var(--white);}
.bg-warm {background:var(--warm);}
.bg-deep {background:var(--deep);}
</style>

<div class="fp">

<!-- ══════════════════════════ 1. HERO ══════════════════════════ -->
<section class="hero" id="reservar">
  <img src="https://metransfers.es/wp-content/uploads/2026/07/airport-transfer-me-tranfers-me-tranfers-barcelona-espana.webp" alt="Traslados privados en Barcelona" class="hero__bg" fetchpriority="high" decoding="sync">
  <div class="hero__grad"></div>
  <div class="wrap">
    <div class="hero__grid">

      <!-- TEXTO -->
      <div>
        <p class="tag" style="color: #FFB547;"><?php echo mt_translate('Traslados privados desde Barcelona · Tours · Eventos'); ?></p>
        <h1 class="hero__h1"><?php echo mt_translate('Tu chófer privado para salir desde Barcelona'); ?></h1>
        <p class="hero__lead"><?php echo mt_translate('Te recogemos en tu hotel, aeropuerto, puerto o cualquier dirección de Barcelona y te llevamos a donde necesites. Presupuesto a medida, precio fijo y atención personalizada las 24 horas.'); ?></p>
        <div class="hero__checks">
          <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Presupuesto a medida'); ?></span>
          <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Cancelación gratuita hasta 24 h antes'); ?></span>
          <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> <?php echo mt_translate('Atención 24/7'); ?></span>
        </div>
        <div class="hero__ctas">
          <a href="#panel" class="btn btn-solid"><?php echo mt_translate('Calcular mi traslado'); ?></a>
          <a href="https://wa.me/34662024136?text=Hola,%20necesito%20informaci%C3%B3n%20para%20organizar%20un%20traslado%20privado%20con%20MeTransfers." class="btn btn-ghost-inv" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:8px;"><svg width="18" height="18" viewBox="0 0 32 32" fill="currentColor"><path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"/></svg> Consultar por WhatsApp</a>
        </div>
        <div class="hero__stats">
          <div>
            <div class="hero__stat-n">+5.000</div>
            <div class="hero__stat-l"><?php echo mt_translate('traslados realizados'); ?></div>
          </div>
          <div>
            <div class="hero__stat-n">4,8/5</div>
            <div class="hero__stat-l"><a href="https://www.getyourguide.com/es-es/metransfers-s12737/" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;"><?php echo mt_translate('valoración GetYourGuide'); ?></a></div>
          </div>
          <div>
            <div class="hero__stat-n">24/7</div>
            <div class="hero__stat-l"><?php echo mt_translate('atención personalizada'); ?></div>
          </div>
        </div>
      </div>

      <!-- PANEL -->
      <div id="panel">
        <div class="hero__panel">
          <h2><?php echo mt_translate('Calcula tu traslado'); ?></h2>
          <?php if ( shortcode_exists( 'wptb_booking_form' ) ) : ?>
            <?php echo do_shortcode( '[wptb_booking_form]' ); ?>
          <?php else : ?>
            <p style="text-align:center;padding:20px;color:var(--muted);">Activa el plugin de reservas.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════════════════ 2. SERVICIOS ══════════════════════════ -->
<section class="sp bg-white" id="servicios">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Servicios de transporte privado desde Barcelona</p>
    <h2 class="tc">Un vehículo con chófer para cada trayecto desde Barcelona</h2>
    <p class="lead tc" style="margin:20px auto 0;">Desde una recogida en el aeropuerto hasta una jornada corporativa o un tour privado, organizamos cada salida desde Barcelona según tu ruta, horario y número de pasajeros.</p>

    <div class="srv__grid">

      <div class="srv__item">
        <span class="srv__num">01</span>
        <div class="srv__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
        <h3>Traslados al Aeropuerto desde Barcelona</h3>
        <p>Recogida privada en tu hotel u oficina en Barcelona con destino a El Prat. Seguimiento del vuelo, bienvenida en la terminal y hasta 60 min de espera de cortesía en llegadas.</p>
        <a href="/traslados-aeropuerto/" class="btn-link">Ver traslados al aeropuerto <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
      </div>

      <div class="srv__item">
        <span class="srv__num">02</span>
        <div class="srv__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
        <h3>Traslados al Puerto desde Barcelona</h3>
        <p>Conectamos tu hotel o dirección en Barcelona con todas las terminales de cruceros. Tu chófer te recoge puntual y ayuda con el equipaje.</p>
        <a href="/traslados-puerto/" class="btn-link">Ver traslados al puerto <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
      </div>

      <div class="srv__item">
        <span class="srv__num">03</span>
        <div class="srv__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <h3>Chófer privado por horas</h3>
        <p>Dispón de un vehículo con conductor durante el tiempo contratado. Ideal para reuniones, compras, cenas o agendas con varias paradas.</p>
        <a href="/chofer-por-horas/" class="btn-link">Ver servicio por horas <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
      </div>

      <div class="srv__item">
        <span class="srv__num">04</span>
        <div class="srv__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <h3>Empresas y Grupos</h3>
        <p>Coordinamos la movilidad de directivos, invitados y familias numerosas. Vehículos MINI VAN «V» Class disponibles para hasta 7 pasajeros con equipaje.</p>
        <a href="/grupos/" class="btn-link">Consultar para empresas y grupos <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════════════════ 3. CÓMO FUNCIONA ══════════════════════════ -->
<section class="sp bg-warm" id="como-funciona">
  <div class="wrap">
    <div class="how__grid">
      <div>
        <p class="tag">Reserva fácil</p>
        <h2>Reserva en pocos minutos y viaja sin complicaciones</h2>
        <p class="lead" style="margin-top:20px;">El proceso está diseñado para que conozcas la ruta, el vehículo, las opciones y las condiciones antes de confirmar tu reserva.</p>
        <a href="#panel" class="btn btn-solid" style="margin-top:32px;">Iniciar reserva</a>
      </div>
      <div class="how__steps">
        <div class="how__step">
          <div class="how__n">1</div>
          <div class="how__text">
            <h3>Indica tu trayecto</h3>
            <p>Añade el punto de recogida, el destino, la fecha y la hora de salida.</p>
          </div>
        </div>
        <div class="how__step">
          <div class="how__n">2</div>
          <div class="how__text">
            <h3>Elige tu vehículo</h3>
            <p>Selecciona la opción que mejor encaje con los pasajeros, el equipaje y el nivel de confort.</p>
          </div>
        </div>
        <div class="how__step">
          <div class="how__n">3</div>
          <div class="how__text">
            <h3>Confirma la reserva</h3>
            <p>Revisa las opciones y datos, completa la información y realiza el pago de forma segura.</p>
          </div>
        </div>
        <div class="how__step">
          <div class="how__n">4</div>
          <div class="how__text">
            <h3>Encuentra a tu chófer</h3>
            <p>El conductor te espera en el punto acordado con cartel identificativo y te ayuda con el equipaje.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 4. VENTAJAS ══════════════════════════ -->
<section class="sp bg-white" id="ventajas">
  <div class="wrap">
    <div class="vent__grid">
      <div class="vent__img-wrap">
        <img src="<?php echo $bdi; ?>/airport-transfer.webp" alt="Traslado privado al aeropuerto de Barcelona" class="vent__img" loading="lazy" decoding="async">
      </div>
      <div>
        <p class="tag">La experiencia MeTransfers</p>
        <h2>Puntualidad, comodidad y atención en cada trayecto</h2>
        <p class="lead" style="margin-top:20px;">No solo te llevamos de un punto a otro. Coordinamos cada detalle para que disfrutes de una recogida clara, un viaje cómodo y asistencia cuando la necesites.</p>
        <ul class="vent__list">
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Presupuesto a medida y condiciones visibles antes de confirmar</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Seguimiento del vuelo y recogida en la terminal</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Chóferes profesionales, discretos y bilingües</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Vehículos Mercedes seleccionados para cada servicio</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Cancelación gratuita hasta 24 horas antes</li>
          <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Atención 24/7 por teléfono, email y WhatsApp</li>
        </ul>
        <p class="vent__note">Puedes solicitar sillas infantiles, paradas adicionales o transporte de equipaje especial durante la reserva. La disponibilidad se confirma para cada servicio.</p>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 5. FLOTA ══════════════════════════ -->
<section class="sp bg-warm" id="flota">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Flota Mercedes</p>
    <h2 class="tc">El espacio y el confort adecuados para cada reserva</h2>
    <p class="lead tc" style="margin:20px auto 0;">Asignamos el vehículo según el número de pasajeros, el equipaje y el tipo de viaje. Todos los modelos se mantienen bajo estándares de limpieza y seguridad.</p>

    <div class="fleet__grid">

      <div class="fleet__card">
        <div class="fleet__img-wrap">
          <img src="<?php echo $bdi; ?>/corporate-vip.webp" alt="ECONOMIC CLASS - Berlina ejecutiva" class="fleet__img" loading="lazy" decoding="async">
          <span class="fleet__badge">Hasta 3 pasajeros</span>
        </div>
        <div class="fleet__body">
          <span class="fleet__cat">Berlina ejecutiva</span>
          <h3>ECONOMIC CLASS</h3>
          <p>Cómoda y elegante para traslados de aeropuerto, hoteles, reuniones y recorridos urbanos.</p>
          <div class="fleet__specs">
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> Hasta 3 pasajeros</span>
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Equipaje según configuración</span>
          </div>
          <a href="#reservar" class="btn btn-ghost" style="text-align:center;justify-content:center;">Reservar berlina ejecutiva</a>
        </div>
      </div>

      <div class="fleet__card">
        <div class="fleet__img-wrap">
          <img src="<?php echo $img; ?>/V1.webp" alt="BUSINESS CLASS - Servicio premium" class="fleet__img" loading="lazy" decoding="async">
          <span class="fleet__badge" style="background:var(--blue);">VIP / Premium</span>
        </div>
        <div class="fleet__body">
          <span class="fleet__cat">Servicio premium</span>
          <h3>BUSINESS CLASS</h3>
          <p>Máxima privacidad y confort para clientes VIP, directivos, ocasiones especiales y servicios de representación.</p>
          <div class="fleet__specs">
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg> Hasta 2 pasajeros</span>
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg> Servicio de alta gama</span>
          </div>
          <a href="#reservar" class="btn btn-ghost" style="text-align:center;justify-content:center;">Consultar servicio premium</a>
        </div>
      </div>

      <div class="fleet__card">
        <div class="fleet__img-wrap">
          <img src="<?php echo $bdi; ?>/family-v-class.webp" alt="MINI VAN «V» Class - Minivan premium" class="fleet__img" loading="lazy" decoding="async">
          <span class="fleet__badge">Hasta 7 pasajeros</span>
        </div>
        <div class="fleet__body">
          <span class="fleet__cat">Minivan premium</span>
          <h3>MINI VAN «V» Class</h3>
          <p>Espacio amplio para familias, grupos, equipos de trabajo, tours privados y pasajeros con mayor volumen de equipaje.</p>
          <div class="fleet__specs">
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> Hasta 7 pasajeros</span>
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Capacidad máxima de equipaje</span>
          </div>
          <a href="#reservar" class="btn btn-ghost" style="text-align:center;justify-content:center;">Reservar vehículo para grupos</a>
        </div>
      </div>

      <div class="fleet__card">
        <div class="fleet__img-wrap">
          <img src="<?php echo $bdi; ?>/family-v-class.webp" alt="MINI VAN ECONOMIC - Minibús" class="fleet__img" loading="lazy" decoding="async">
          <span class="fleet__badge">Hasta 7 pasajeros</span>
        </div>
        <div class="fleet__body">
          <span class="fleet__cat">Minibús</span>
          <h3>MINI VAN ECONOMIC</h3>
          <p>Opción económica y espaciosa para familias o grupos de hasta 7 pasajeros que buscan comodidad al mejor precio.</p>
          <div class="fleet__specs">
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> Hasta 7 pasajeros</span>
            <span class="fleet__spec"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg> Capacidad estándar de equipaje</span>
          </div>
          <a href="#reservar" class="btn btn-ghost" style="text-align:center;justify-content:center;">Reservar vehículo para grupos</a>
        </div>
      </div>

    </div>
    <p class="fleet__note">El modelo concreto puede variar por otro vehículo de categoría y características equivalentes según disponibilidad.</p>
  </div>
</section>


<!-- ══════════════════════════ 6. RUTAS ══════════════════════════ -->
<section class="sp bg-white" id="destinos">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Rutas más solicitadas desde Barcelona</p>
    <h2 class="tc">Traslados privados desde Barcelona — Destinos más populares</h2>
    <p class="lead tc" style="margin:20px auto 0;">Salidas desde el Aeropuerto El Prat, cualquier hotel, estación o domicilio en Barcelona hacia tu destino. También organizamos trayectos de larga distancia.</p>

    <div class="routes__grid">
      <a href="/taxis-privado-barcelona/" class="route__item" style="text-decoration:none;color:inherit;">
        <div class="route__info"><h3>Aeropuerto de Barcelona ↔ centro</h3><p>Recogida con seguimiento de vuelo y bienvenida en llegadas.</p></div>
        <span class="route__time">25–40 min</span>
      </a>
      <div class="route__item">
        <div class="route__info"><h3>Aeropuerto de Barcelona ↔ Puerto</h3><p>Conexión directa a las terminales de cruceros.</p></div>
        <span class="route__time">Flexible</span>
      </div>
      <div class="route__item">
        <div class="route__info"><h3>Barcelona ↔ Sitges</h3><p>Traslado puerta a puerta para hoteles, viviendas y eventos.</p></div>
        <span class="route__time">35–50 min</span>
      </div>
      <a href="/taxis-barcelona-costa-brava/" class="route__item" style="text-decoration:none;color:inherit;">
        <div class="route__info"><h3>Barcelona ↔ Costa Brava</h3><p>Viajes a localidades costeras con espacio de equipaje.</p></div>
        <span class="route__time">Variable</span>
      </a>
      <a href="/taxis-barcelona-port-aventura/" class="route__item" style="text-decoration:none;color:inherit;">
        <div class="route__info"><h3>Barcelona ↔ PortAventura</h3><p>Cómodo para familias y grupos.</p></div>
        <span class="route__time">1 h 15 min</span>
      </a>
      <a href="/taxis-barcelona-salou/" class="route__item" style="text-decoration:none;color:inherit;">
        <div class="route__info"><h3>Barcelona ↔ Salou</h3><p>Traslado directo a hoteles y apartamentos turísticos.</p></div>
        <span class="route__time">1 h 15 min</span>
      </a>
      <a href="/taxis-barcelona-girona/" class="route__item" style="text-decoration:none;color:inherit;">
        <div class="route__info"><h3>Barcelona ↔ Girona</h3><p>Traslado directo a la ciudad o al aeropuerto de Girona.</p></div>
        <span class="route__time">1 h 20 min</span>
      </a>
      <div class="route__item">
        <div class="route__info"><h3>Barcelona ↔ Andorra</h3><p>Larga distancia con paradas programadas bajo petición.</p></div>
        <span class="route__time">2 h 45–3 h 15</span>
      </div>
    </div>

    <div class="tc" style="margin-top:40px;">
      <a href="/destinos/" class="btn btn-ghost">Ver todos los destinos</a>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 7. TOURS ══════════════════════════ -->
<section class="sp tours-section" id="tours">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Tours y excursiones privadas</p>
    <h2 class="tc">Descubre Cataluña a tu ritmo</h2>
    <p class="lead tc" style="margin:20px auto 0;color:rgba(255,255,255,.75);">Recogida puerta a puerta, horarios flexibles y vehículo Mercedes reservado solo para ti y tus acompañantes.</p>

    <div class="tours__grid">

      <!-- Tour principal — ocupa las 3 filas de la columna izquierda -->
      <div class="tour__card tour__main">
        <div class="tour__img" style="background-image:url('<?php echo $arts; ?>/descubre-barcelona-en-4-6-u-8-horas-elige-el-tour-en-coche-a-tu-medida-4144.jpg');"></div>
        <div class="tour__content">
          <h3>Tour privado por Barcelona</h3>
          <p>Sagrada Familia, Passeig de Gràcia, Barrio Gótico y Montjuïc. Itinerario flexible de 4, 6 u 8 horas con vehículo Mercedes.</p>
          <a href="/tour-en-barcelona/#tour-booking" class="tour__cta-btn">Más información</a>
        </div>
      </div>

      <!-- Columna derecha: 3 tours apilados -->
      <div class="tour__card">
        <div class="tour__img" style="background-image:url('<?php echo $arts; ?>/la-mejor-opcion-de-que-ver-en-montserrat-3789.jpg');"></div>
        <div class="tour__content">
          <h3>Montserrat</h3>
          <p>Monasterio y montaña mágica desde Barcelona con recogida puerta a puerta.</p>
          <a href="/tour-a-montserrat/#tour-booking" class="tour__cta-btn">Más información</a>
        </div>
      </div>

      <div class="tour__card">
        <div class="tour__img" style="background-image:url('<?php echo $arts; ?>/todo-lo-que-debes-saber-sobre-ruta-por-el-penedes-visita-las-bodegas-con-un-chofer-privado-y-disfruta-sin-preocuparte-por-conducir-2990.jpg');"></div>
        <div class="tour__content">
          <h3>Ruta por el Penedès</h3>
          <p>Visita las bodegas con un chófer privado y disfruta sin preocuparte por conducir.</p>
          <a href="/tours-privados/#tour-booking" class="tour__cta-btn">Más información</a>
        </div>
      </div>

      <div class="tour__card">
        <div class="tour__img" style="background-image:url('<?php echo $arts; ?>/la-mejor-opcion-de-ruta-en-coche-de-1-dia-por-la-costa-brava-tu-eliges-los-pueblos-nosotros-conducimos-1787.jpg');"></div>
        <div class="tour__content">
          <h3>Costa Brava</h3>
          <p>Pueblos costeros, calas y paisajes mediterráneos exclusivos con itinerario personalizado.</p>
          <a href="/tour-costa-brava/#tour-booking" class="tour__cta-btn">Más información</a>
        </div>
      </div>

    </div>

    <div class="tc" style="margin-top:40px;">
      <a href="/tours-privados/" class="btn btn-ghost-inv">Ver todos los tours</a>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 8. OPINIONES ══════════════════════════ -->
<section class="sp bg-warm" id="opiniones">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Opiniones de viajeros</p>
    <h2 class="tc">La confianza se gana en cada recogida</h2>
    <p class="lead tc" style="margin:20px auto 0;">Algunas experiencias de clientes que han reservado traslados y tours privados con MeTransfers.</p>

    <div class="tc">
      <a href="https://www.getyourguide.com/es-es/metransfers-s12737/" target="_blank" rel="noopener noreferrer" class="gyg-badge" style="text-decoration:none;display:inline-flex;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFB547"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        4,8/5 · 340 opiniones verificadas &rarr; Ver en GetYourGuide
      </a>
    </div>

    <div class="rev__grid">
      <div class="rev__card">
        <div class="rev__stars">★★★★★</div>
        <p class="rev__quote">"El chófer fue muy puntual y nos ayudó con todo el equipaje. El vehículo estaba impecable y el viaje desde el aeropuerto al hotel fue muy cómodo tras un vuelo largo. Sin duda repetiremos."</p>
        <span class="rev__author">James S.</span>
        <span class="rev__meta">Reino Unido · Agosto 2024 · Traslado aeropuerto</span>
      </div>
      <div class="rev__card">
        <div class="rev__stars">★★★★★</div>
        <p class="rev__quote">"Reservamos un traslado al puerto de cruceros para nuestra familia. Espacio de sobra en la MINI VAN «V» Class y una atención al cliente perfecta por WhatsApp para confirmar los detalles."</p>
        <span class="rev__author">María R.</span>
        <span class="rev__meta">España · Septiembre 2024 · Traslado puerto cruceros</span>
      </div>
      <div class="rev__card">
        <div class="rev__stars">★★★★★</div>
        <p class="rev__quote">"Hicimos el tour a Montserrat y fue espectacular. El conductor conocía perfectamente la ruta, nos dio consejos útiles y adaptó los tiempos a nuestro ritmo. Un servicio de 10."</p>
        <span class="rev__author">Anna J.</span>
        <span class="rev__meta">Estados Unidos · Octubre 2024 · Tour Montserrat</span>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 9. FAQ ══════════════════════════ -->
<section class="sp bg-white" id="faq">
  <div class="wrap">
    <p class="tag tc" style="justify-content:center;">Preguntas frecuentes</p>
    <h2 class="tc">Todo lo que necesitas saber antes de reservar</h2>
    <p class="lead tc" style="margin:20px auto 0;">Condiciones principales de nuestros traslados privados, chófer por horas y tours con salida desde Barcelona.</p>

    <div class="faq__wrap">
      <div class="faq__item">
        <button class="faq__q">¿Desde dónde podéis recogerme en Barcelona? <span class="faq__icon"></span></button>
        <div class="faq__a">Te recogemos en cualquier punto de Barcelona: hotel, apartamento, oficina, Aeropuerto El Prat, Puerto de Cruceros o estación de tren. Solo indícanos la dirección exacta y la hora al hacer la reserva.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Podéis llevarme desde Barcelona al Aeropuerto de El Prat? <span class="faq__icon"></span></button>
        <div class="faq__a">Sí. Puedes reservar traslados privados desde Barcelona al Aeropuerto Josep Tarradellas Barcelona-El Prat. Al confirmar la reserva, indica la hora de salida de tu vuelo para que calculemos la hora de recogida óptima.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Qué ocurre si mi vuelo se retrasa? <span class="faq__icon"></span></button>
        <div class="faq__a">Monitorizamos el estado del vuelo con el número facilitado en la reserva. Si se produce un retraso, ajustamos la hora de recogida para que el chófer te espere en función de la llegada real.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Dónde me espera el conductor? <span class="faq__icon"></span></button>
        <div class="faq__a">En el aeropuerto, el chófer te espera en la zona de llegadas con un cartel identificativo. En hoteles, viviendas, puertos y otros puntos, la ubicación exacta se confirma en los datos de la reserva.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Puedo solicitar un presupuesto a medida? <span class="faq__icon"></span></button>
        <div class="faq__a">El presupuesto y las condiciones principales se muestran antes de confirmar el pago. Cualquier cambio posterior de ruta, horario, pasajeros o equipaje que modifique el servicio deberá revisarse y confirmarse de nuevo.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Puedo cancelar mi reserva? <span class="faq__icon"></span></button>
        <div class="faq__a">MeTransfers ofrece cancelación gratuita hasta 24 horas antes del servicio, salvo condiciones diferentes indicadas en reservas especiales.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Puedo solicitar una silla infantil? <span class="faq__icon"></span></button>
        <div class="faq__a">Sí. Puedes solicitar sillas infantiles o elevadores al realizar la reserva. Indica la edad y el peso aproximado del menor para confirmar la opción adecuada y su disponibilidad.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Tenéis vehículos para grupos y equipaje voluminoso? <span class="faq__icon"></span></button>
        <div class="faq__a">Sí. La MINI VAN «V» Class permite viajar hasta siete pasajeros, dependiendo del equipaje. Para maletas especiales o material deportivo, indícalo antes de confirmar para asignar el vehículo correcto.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Puedo contratar un coche con chófer por horas? <span class="faq__icon"></span></button>
        <div class="faq__a">Sí. El servicio por horas es ideal para reuniones, cenas, compras, eventos o agendas con varias paradas. Envíanos el horario aproximado y el recorrido para preparar una propuesta.</div>
      </div>
      <div class="faq__item">
        <button class="faq__q">¿Qué formas de pago están disponibles? <span class="faq__icon"></span></button>
        <div class="faq__a">Las formas de pago disponibles se muestran durante el proceso de reserva antes de confirmar. El pago online se realiza a través de una pasarela segura.</div>
      </div>
    </div>
  </div>
</section>



<!-- ══════════════════════════ 11. CTA FINAL ══════════════════════════ -->
<section class="cta" id="cta-final">
  <div class="wrap">
    <div class="cta__inner">
      <div>
        <p class="tag">Reserva tu próximo traslado</p>
        <h2>Tu viaje comienza con una recogida bien organizada</h2>
        <p class="cta__lead">Indica el origen, el destino, la fecha y la hora. Te mostraremos las opciones disponibles para que reserves el vehículo que mejor se adapta a tu trayecto.</p>
      </div>
      <div class="cta__btns">
        <a href="#reservar" class="btn btn-solid" style="background:#fff;color:var(--deep);">Presupuestar y reservar</a>
        <a href="https://wa.me/34662024136?text=Hola,%20necesito%20informaci%C3%B3n%20para%20organizar%20un%20traslado%20privado%20con%20MeTransfers." class="btn btn-ghost-inv" target="_blank" rel="noopener">Consultar por WhatsApp</a>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════════════════ 12. CONTACTO ══════════════════════════ -->
<section class="sp bg-white" id="contacto">
  <div class="wrap">
    <p class="tag">Atención personalizada</p>
    <h2>¿Necesitas ayuda para organizar tu traslado?</h2>
    <p class="lead" style="margin-top:16px;">Cuéntanos la ruta, la fecha, los pasajeros y cualquier necesidad especial. Te respondemos lo antes posible.</p>

    <div class="contact__grid">
      <!-- Info -->
      <div>
        <ul class="contact__list">
          <li class="contact__li">
            <div class="contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
            <div><div class="contact__label">Teléfono y WhatsApp</div><a href="tel:+34662024136" class="contact__val">+34 662 02 41 36</a></div>
          </li>
          <li class="contact__li">
            <div class="contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
            <div><div class="contact__label">Email</div><a href="mailto:info@metransfers.es" class="contact__val">info@metransfers.es</a></div>
          </li>
          <li class="contact__li">
            <div class="contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="10" r="3"/><path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 7 8 11.7z"/></svg></div>
            <div><div class="contact__label">Ubicación</div><span class="contact__val" style="font-weight:600;font-size:18px;color:var(--muted);">Barcelona, España</span></div>
          </li>
          <li class="contact__li">
            <div class="contact__ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
            <div><div class="contact__label">Horario de atención</div><span class="contact__val" style="font-weight:600;font-size:18px;color:var(--muted);">24 horas, 7 días a la semana</span></div>
          </li>
        </ul>
        <div style="margin-top:48px;">
          <button class="btn js-wa-trigger" aria-label="Contactar por WhatsApp" style="background:#25d366;color:#fff;border:none;box-shadow:0 8px 24px rgba(37,211,102,.3);padding:16px 32px;font-size:16px;">
            <svg width="22" height="22" viewBox="0 0 32 32" fill="currentColor"><path d="M26.576 5.363c-2.69-2.69-6.406-4.354-10.511-4.354-8.209 0-14.865 6.655-14.865 14.865 0 2.732 0.737 5.291 2.022 7.491l-0.038-0.070-2.109 7.702 7.879-2.067c2.051 1.139 4.498 1.809 7.102 1.809h0.006c8.209-0.003 14.862-6.659 14.862-14.868 0-4.103-1.662-7.817-4.349-10.507l0 0zM16.062 28.228h-0.005c-0 0-0.001 0-0.001 0-2.319 0-4.489-0.64-6.342-1.753l0.056 0.031-0.451-0.267-4.675 1.227 1.247-4.559-0.294-0.467c-1.185-1.862-1.889-4.131-1.889-6.565 0-6.822 5.531-12.353 12.353-12.353s12.353 5.531 12.353 12.353c0 6.822-5.53 12.353-12.353 12.353h-0zM22.838 18.977c-0.371-0.186-2.197-1.083-2.537-1.208-0.341-0.124-0.589-0.185-0.837 0.187-0.246 0.371-0.958 1.207-1.175 1.455-0.216 0.249-0.434 0.279-0.805 0.094-1.15-0.466-2.138-1.087-2.997-1.852l0.010 0.009c-0.799-0.74-1.484-1.587-2.037-2.521l-0.028-0.052c-0.216-0.371-0.023-0.572 0.162-0.757 0.167-0.166 0.372-0.434 0.557-0.65 0.146-0.179 0.271-0.384 0.366-0.604l0.006-0.017c0.043-0.087 0.068-0.188 0.068-0.296 0-0.131-0.037-0.253-0.101-0.357l0.002 0.003c-0.094-0.186-0.836-2.014-1.145-2.758-0.302-0.724-0.609-0.625-0.836-0.637-0.216-0.010-0.464-0.012-0.712-0.012-0.395 0.010-0.746 0.188-0.988 0.463l-0.001 0.002c-0.802 0.761-1.3 1.834-1.3 3.023 0 0.026 0 0.053 0.001 0.079l-0-0.004c0.131 1.467 0.681 2.784 1.527 3.857l-0.012-0.015c1.604 2.379 3.742 4.282 6.251 5.564l0.094 0.043c0.548 0.248 1.25 0.513 1.968 0.74l0.149 0.041c0.442 0.14 0.951 0.221 1.479 0.221 0.303 0 0.601-0.027 0.889-0.078l-0.031 0.004c1.069-0.223 1.956-0.868 2.497-1.749l0.009-0.017c0.165-0.366 0.261-0.793 0.261-1.242 0-0.185-0.016-0.366-0.047-0.542l0.003 0.019c-0.092-0.155-0.34-0.247-0.712-0.434z"/></svg>
            Contactar por WhatsApp
          </button>
        </div>
      </div>

      <!-- Formulario -->
      <form class="cform" id="mainContactForm">
        <div class="cform__row">
          <div class="fg"><label>Nombre y apellidos</label><input type="text" name="nombre" required></div>
          <div class="fg"><label>Correo electrónico</label><input type="email" name="email" required></div>
        </div>
        <div class="cform__row">
          <div class="fg"><label>Teléfono</label><input type="tel" name="telefono"></div>
          <div class="fg">
            <label>Servicio que necesitas</label>
            <select name="servicio" required>
              <option value="">Selecciona una opción</option>
              <option value="aeropuerto">Traslado al aeropuerto</option>
              <option value="puerto">Traslado al puerto</option>
              <option value="horas">Chófer por horas</option>
              <option value="evento">Empresa o evento</option>
              <option value="tour">Tour privado</option>
              <option value="grupo">Grupo o celebración</option>
              <option value="otro">Otro servicio</option>
            </select>
          </div>
        </div>
        <div class="fg"><label>Mensaje</label><textarea name="mensaje" placeholder="Indica origen, destino, fecha, hora, pasajeros, equipaje y cualquier petición especial." required></textarea></div>
        <div class="cform__check">
          <input type="checkbox" id="gdpr" name="gdpr_aceptado" value="1" required>
          <label for="gdpr">He leído y acepto la Política de Privacidad y el tratamiento de mis datos.</label>
        </div>
        <button type="submit" class="btn btn-solid cform__submit">Enviar solicitud</button>
        <div class="cform__ok">Gracias. Hemos recibido tu solicitud y te responderemos lo antes posible.</div>
      </form>
    </div>
  </div>
</section>

</div><!-- .fp -->

<script>
/* FAQ */
document.querySelectorAll('.faq__q').forEach(function(btn){
  btn.addEventListener('click', function(){
    var item = btn.closest('.faq__item');
    var isOpen = item.classList.contains('open');
    document.querySelectorAll('.faq__item.open').forEach(function(el){ el.classList.remove('open'); });
    if(!isOpen) item.classList.add('open');
  });
});

/* Formulario contacto (AJAX) */
var cform = document.getElementById('mainContactForm');
if(cform){
  cform.addEventListener('submit', function(e){
    e.preventDefault();
    var btn = cform.querySelector('.cform__submit');
    var originalText = btn.innerHTML;
    btn.innerHTML = 'Enviando...';
    btn.disabled = true;

    var formData = new FormData(cform);
    formData.append('action', 'mt_save_lead');
    formData.append('security', mtAjax.nonce);
    formData.append('origen', 'formulario');

    fetch(mtAjax.ajaxurl, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        cform.querySelector('.cform__ok').style.display = 'block';
        cform.reset();
      } else {
        alert(data.data || 'Ocurrió un error. Inténtalo de nuevo.');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Ocurrió un error al enviar el formulario.');
    })
    .finally(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
    });
  });
}
</script>

<?php get_footer(); ?>

