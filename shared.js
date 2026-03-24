/**
 * Slice+ Shared Components v3
 * Popup → redirect with ?flow=delivery or ?flow=pickup
 * Home page auto-detects and opens correct flow
 */
document.addEventListener('DOMContentLoaded', function(){

var MAPS='https://maps.app.goo.gl/exj8kZeiAGhVrHQN8';
var UE='https://www.ubereats.com/ca/store/sliceplus-quinpool-rd/GrAe0IXMVuGjKvZW6ssmDg?diningMode=DELIVERY';
var DD='https://www.doordash.com/store/sliceplus-halifax-37896069/?utm_campaign=gpa';
var PH='tel:9028004001';
var HOME='/index.html';

var p=location.pathname;
var isHome=p==='/'||p.indexOf('index')>-1;
var pg=p.indexOf('specials')>-1?'specials':p.indexOf('contact')>-1?'contact':p.indexOf('catering')>-1?'catering':'home';
function ac(x){return pg===x?' class="active"':''}

if(isHome) return;

// CSS
var css=document.createElement('style');
css.textContent=`
.nav-cta-call{background:transparent!important;border:2px solid #df2b2b!important;color:#fff!important;box-shadow:none!important}.nav-cta-call:hover{background:#df2b2b!important}
.sp-cart-btn{display:flex;align-items:center;justify-content:center;width:48px;height:48px;padding:0;background:rgba(255,255,255,0.05);border-radius:50%;position:relative;border:none;cursor:pointer;transition:all .3s}.sp-cart-btn:hover{background:rgba(255,255,255,0.1)}
.sp-badge{position:absolute;top:-2px;right:-2px;background:#df2b2b;color:#fff;width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;border:2px solid #0a0a0a}
.sp-popup-bg{display:none;position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:20px}.sp-popup-bg.open{display:flex}
.sp-popup{background:#111;border-radius:20px;max-width:420px;width:100%;padding:36px;border:1px solid rgba(255,255,255,.06);box-shadow:0 24px 80px rgba(0,0,0,.6);text-align:center;position:relative}
.sp-popup-x{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.05);color:#888;display:flex;align-items:center;justify-content:center;font-size:1.1rem;cursor:pointer;border:none;transition:all .3s}.sp-popup-x:hover{background:#df2b2b;color:#fff}
.sp-popup h3{font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:1px;margin-bottom:6px;color:#fff}.sp-popup p{color:#888;font-size:.9rem;margin-bottom:28px}
.sp-popup-btns{display:flex;gap:14px}
.sp-popup-btn{flex:1;padding:22px 16px;border-radius:16px;border:2px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02);cursor:pointer;transition:all .3s;text-align:center;color:#fff;text-decoration:none}
.sp-popup-btn:hover{border-color:rgba(223,43,43,.4);background:rgba(223,43,43,.05);transform:translateY(-2px)}
.sp-popup-btn svg{width:36px;height:36px;margin:0 auto 10px;display:block;stroke:#888;fill:none;transition:all .3s}.sp-popup-btn:hover svg{stroke:#df2b2b}
.sp-popup-btn strong{display:block;font-size:1rem;margin-bottom:4px}.sp-popup-btn span{font-size:.78rem;color:#666}
.sp-cart-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1400}.sp-cart-bg.open{display:block}
.sp-cart{position:fixed;top:0;right:-440px;width:420px;height:100vh;background:#111;z-index:1500;border-left:1px solid rgba(255,255,255,.06);transition:right .4s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.5)}.sp-cart.open{right:0}
.sp-cart-h{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between}.sp-cart-h h3{font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px;color:#fff}
.sp-cart-xb{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.05);color:#888;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;transition:all .3s}.sp-cart-xb:hover{background:#df2b2b;color:#fff}
.sp-cart-body{flex:1;overflow-y:auto;padding:16px 24px}.sp-cart-empty{text-align:center;color:#888;padding:60px 20px}
.sp-ci{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid rgba(255,255,255,.05)}.sp-ci-info{flex:1}.sp-ci-name{font-weight:600;font-size:.9rem;color:#fff}.sp-ci-mods{font-size:.75rem;color:#888;margin-top:2px}
.sp-ci-bot{display:flex;align-items:center;justify-content:space-between;margin-top:8px}.sp-ci-price{font-weight:700;font-size:.9rem;color:#22c55e}
.sp-ci-qty{display:flex;align-items:center;background:rgba(255,255,255,.05);border-radius:50px}.sp-ci-qty button{width:28px;height:28px;background:transparent;color:#fff;font-size:.85rem;font-weight:700;display:flex;align-items:center;justify-content:center;border-radius:50%;border:none;cursor:pointer}.sp-ci-qty button:hover{background:rgba(255,255,255,.1)}.sp-ci-qty em{width:28px;text-align:center;font-weight:700;font-size:.82rem;font-style:normal;color:#fff}
.sp-ci-rm{color:#555;font-size:.72rem;background:none;border:none;cursor:pointer;margin-top:4px}.sp-ci-rm:hover{color:#df2b2b}
.sp-cart-ft{padding:20px 24px;border-top:1px solid rgba(255,255,255,.06);background:rgba(17,17,17,.8)}
.sp-cl{display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:8px;color:#888}.sp-cl.tot{font-size:1.1rem;font-weight:800;color:#fff;margin-top:12px;padding-top:12px;border-top:1px solid rgba(255,255,255,.08)}.sp-cl.tot span:last-child{color:#22c55e}
.sp-go-btn{display:block;width:100%;margin-top:16px;background:#df2b2b;color:#fff;padding:16px;border-radius:50px;font-weight:700;font-size:1rem;text-align:center;border:none;cursor:pointer;box-shadow:0 4px 24px rgba(223,43,43,.25);text-decoration:none}.sp-go-btn:hover{background:#b91c1c}
.mob-sp-btns{display:flex;flex-direction:column;gap:12px;width:80%;max-width:300px;margin-top:20px}
.mob-sp-btns a{display:block;text-align:center;padding:14px;border-radius:50px;font-weight:700;font-size:.9rem;letter-spacing:1px;text-transform:uppercase;text-decoration:none}
.info-ticker{position:fixed!important;top:0!important;left:0!important;right:0!important;z-index:1001!important}
.header{top:42px!important}.mobile-nav{top:116px!important}
@media(max-width:768px){.sp-cart{width:100%;right:-100%}.sp-popup-btns{flex-direction:column}.header{top:36px!important}.mobile-nav{top:110px!important}}
`;
document.head.appendChild(css);

// NAV
var navEl=document.querySelector('.nav');
if(navEl){
    navEl.innerHTML=
    '<a href="/index.html"'+ac('home')+'>Home</a>'+
    '<a href="/specials.html"'+ac('specials')+'>Today Special Deal</a>'+
    '<a href="/contact.html"'+ac('contact')+'>Contact</a>'+
    '<a href="/catering.html"'+ac('catering')+'>Catering</a>'+
    '<a href="#" class="nav-cta" id="spOB">Order Now</a>'+
    '<a href="'+PH+'" class="nav-cta nav-cta-call">Call Now</a>';
}

// Cart button in header
var hi=document.querySelector('.header-inner');
var mt=document.querySelector('.mobile-toggle');
if(hi&&mt){
    var cb=document.createElement('button');cb.className='sp-cart-btn';cb.id='spCB';
    cb.innerHTML='<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg><span class="sp-badge" id="spBdg">0</span>';
    hi.insertBefore(cb,mt);
}

// Mobile nav
var mn=document.getElementById('mobileNav');
if(mn){
    mn.innerHTML=
    '<a href="/index.html">Home</a><a href="/specials.html">Today Special Deal</a><a href="/contact.html">Contact</a><a href="/catering.html">Catering</a>'+
    '<div class="mob-sp-btns"><a href="#" id="spMO" style="background:#df2b2b;color:#fff">Order Now</a><a href="'+PH+'" style="border:2px solid #df2b2b;color:#fff">Call (902) 800-4001</a></div>';
}

// Footer
var fi=document.querySelector('.footer-inner');
if(fi){
    fi.innerHTML=
    '<div class="footer-brand"><img src="assets/images/1__3_.png" alt="Slice+"><p>Halifax\'s favourite spot for gourmet pizza, authentic donairs, Indian specialties, wings, momos & more.</p></div>'+
    '<div class="footer-col"><h4>PAGES</h4><a href="/index.html">Home</a><a href="/specials.html">Today Special Deal</a><a href="/contact.html">Contact</a><a href="/catering.html">Catering</a></div>'+
    '<div class="footer-col"><h4>ORDER</h4><a href="#" id="spFO">Order Direct</a><a href="'+UE+'" target="_blank">UberEats</a><a href="'+DD+'" target="_blank">DoorDash</a><a href="'+PH+'">Call (902) 800-4001</a></div>'+
    '<div class="footer-col"><h4>VISIT US</h4><a href="'+MAPS+'" target="_blank">6169 Quinpool Rd #111, Halifax, NS</a><a href="'+PH+'">(902) 800-4001</a><a>10:30 AM \u2013 11 PM Daily</a></div>';
}

// ORDER POPUP — redirects with ?flow= parameter
var pop=document.createElement('div');pop.className='sp-popup-bg';pop.id='spPop';
pop.innerHTML=
'<div class="sp-popup"><button class="sp-popup-x" id="spPX">&times;</button>'+
'<h3>HOW WOULD YOU LIKE IT?</h3><p>Choose your preferred order method</p>'+
'<div class="sp-popup-btns">'+
'<a href="'+HOME+'?flow=delivery" class="sp-popup-btn"><svg viewBox="0 0 24 24" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg><strong>Delivery</strong><span>$3.99 fee · 30-45 min</span></a>'+
'<a href="'+HOME+'?flow=pickup" class="sp-popup-btn"><svg viewBox="0 0 24 24" stroke-width="1.8"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-16 0H3"/><path d="M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg><strong>Pickup</strong><span>Ready in 20-30 min</span></a>'+
'</div></div>';
document.body.appendChild(pop);

// CART SIDEBAR
var cbg=document.createElement('div');cbg.className='sp-cart-bg';cbg.id='spCBG';document.body.appendChild(cbg);
var csb=document.createElement('div');csb.className='sp-cart';csb.id='spCSB';
csb.innerHTML='<div class="sp-cart-h"><h3>YOUR ORDER</h3><button class="sp-cart-xb" id="spCX"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div><div class="sp-cart-body" id="spCBody"></div><div class="sp-cart-ft" id="spCFt"></div>';
document.body.appendChild(csb);

// EVENTS
function openP(){document.getElementById('spPop').classList.add('open');document.body.style.overflow='hidden'}
function closeP(){document.getElementById('spPop').classList.remove('open');document.body.style.overflow=''}
function openC(){renderC();document.getElementById('spCSB').classList.add('open');document.getElementById('spCBG').classList.add('open')}
function closeC(){document.getElementById('spCSB').classList.remove('open');document.getElementById('spCBG').classList.remove('open')}
function toggleC(){document.getElementById('spCSB').classList.contains('open')?closeC():openC()}
function closeM(){var m=document.getElementById('mobileNav');if(m)m.classList.remove('open')}

document.getElementById('spOB').addEventListener('click',function(e){e.preventDefault();openP()});
var mo=document.getElementById('spMO');if(mo)mo.addEventListener('click',function(e){e.preventDefault();closeM();openP()});
var fo=document.getElementById('spFO');if(fo)fo.addEventListener('click',function(e){e.preventDefault();openP()});
document.getElementById('spCB').addEventListener('click',toggleC);
document.getElementById('spCX').addEventListener('click',closeC);
document.getElementById('spCBG').addEventListener('click',closeC);
document.getElementById('spPX').addEventListener('click',closeP);
document.getElementById('spPop').addEventListener('click',function(e){if(e.target===this)closeP()});
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeP();closeC()}});

// FIX EXISTING DEAL ALERT
var da=document.querySelector('.deal-alert-body');
if(da){da.href='#';da.addEventListener('click',function(e){e.preventDefault();var d=document.getElementById('dealAlert');if(d)d.classList.remove('show');openP()})}

// FIX ALL "Order Now" BUTTONS ON PAGE (specials page deal cards etc)
document.querySelectorAll('a').forEach(function(a){
    var href=a.getAttribute('href')||'';
    var txt=a.textContent.trim();
    // Catch any link that goes to clover ordering or says "Order Now"
    if(href.indexOf('clover.com/online-ordering')>-1 || (txt==='Order Now' && !a.classList.contains('nav-cta') && !a.classList.contains('da-cta') && !a.classList.contains('deal-alert-cta'))){
        a.href='#';
        a.addEventListener('click',function(e){e.preventDefault();openP()});
    }
});

// TICKER POSITION
var tk=document.querySelector('.info-ticker');var hd=document.querySelector('.header');
if(tk&&hd){hd.parentNode.insertBefore(tk,hd)}

window.toggleCart=toggleC;

// CART
function getC(){try{return JSON.parse(localStorage.getItem('sp_cart')||'[]')}catch(e){return[]}}
function saveC(c){localStorage.setItem('sp_cart',JSON.stringify(c));renderC()}
function esc(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML}

function renderC(){
    var cart=getC();
    var bg=document.getElementById('spBdg');if(bg)bg.textContent=cart.reduce(function(s,i){return s+(i.quantity||1)},0);
    var body=document.getElementById('spCBody'),ft=document.getElementById('spCFt');if(!body||!ft)return;
    if(!cart.length){
        body.innerHTML='<div class="sp-cart-empty"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg><p style="margin-top:16px;color:#fff">Your cart is empty</p></div>';
        ft.innerHTML='<a href="'+HOME+'" class="sp-go-btn">Browse Menu</a>';return}
    var h='',sub=0;
    cart.forEach(function(it,i){var t=it.price_cents||0;if(it.modifiers)it.modifiers.forEach(function(m){t+=(m.price_cents||0)});t*=(it.quantity||1);sub+=t;
        var mn=it.modifiers?it.modifiers.map(function(m){return m.name}).join(', '):'';
        h+='<div class="sp-ci"><div class="sp-ci-info"><div class="sp-ci-name">'+esc(it.name)+'</div>';if(mn)h+='<div class="sp-ci-mods">'+esc(mn)+'</div>';
        h+='<div class="sp-ci-bot"><span class="sp-ci-price">$'+(t/100).toFixed(2)+'</span><div class="sp-ci-qty"><button onclick="window._spU('+i+',-1)">\u2212</button><em>'+(it.quantity||1)+'</em><button onclick="window._spU('+i+',1)">+</button></div></div>';
        h+='<button class="sp-ci-rm" onclick="window._spR('+i+')">Remove</button></div></div>'});
    body.innerHTML=h;var tax=Math.round(sub*.15);
    ft.innerHTML='<div class="sp-cl"><span>Subtotal</span><span>$'+(sub/100).toFixed(2)+'</span></div><div class="sp-cl"><span>HST (15%)</span><span>$'+(tax/100).toFixed(2)+'</span></div><div class="sp-cl tot"><span>Total</span><span>$'+((sub+tax)/100).toFixed(2)+'</span></div><a href="'+HOME+'?flow=checkout" class="sp-go-btn">View Order & Checkout</a>'}
window._spU=function(i,d){var c=getC();if(c[i]){c[i].quantity=Math.max(1,Math.min(20,(c[i].quantity||1)+d));saveC(c)}};
window._spR=function(i){var c=getC();c.splice(i,1);saveC(c)};

renderC();
window.addEventListener('scroll',function(){var h=document.querySelector('.header');if(h)h.classList.toggle('scrolled',window.scrollY>50)});
});