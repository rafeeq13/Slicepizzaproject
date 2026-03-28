/**
 * Slice+ Bundle v1.0
 * Combined: fixes + cat-images + patch2 + confirmation + mobile-header
 * Replaces: fixes.js, cat-images.js, patch2.js, confirmation.js, mobile-header.js
 */
(function () {
    'use strict';

    // =============== FLOW DETECTION (instant, before render) ===============
    var params = new URLSearchParams(window.location.search);
    var flow = params.get('flow');
    if (flow) { document.documentElement.style.opacity = '0'; document.documentElement.style.transition = 'none'; window.history.replaceState({}, document.title, window.location.pathname) }

    // =============== ALL CSS IN ONE BLOCK ===============
    var css = document.createElement('style');
    css.textContent = `
.btn-cart{display:flex!important;align-items:center!important;justify-content:center!important;width:48px!important;height:48px!important;padding:0!important;background:rgba(255,255,255,0.05)!important;border-radius:50%!important;position:relative!important;transition:all .3s!important}
.btn-cart:hover{background:rgba(255,255,255,0.1)!important}
.btn-cart svg{width:22px!important;height:22px!important;stroke:var(--white)!important}
.cart-badge{position:absolute!important;top:-2px!important;right:-2px!important;background:var(--red)!important;color:var(--white)!important;width:20px!important;height:20px!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;font-size:.7rem!important;font-weight:800!important;border:2px solid var(--black)!important}
.info-ticker{margin-top:0!important;position:fixed!important;top:0!important;left:0!important;right:0!important;z-index:1001!important;padding:10px 0!important}
.header{top:42px!important}
.mobile-nav{top:116px!important}
.hero{padding-top:140px!important}
.s2{padding-top:150px!important}
.s3{padding-top:130px!important}
.on-popup-ov{display:none;position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:20px}.on-popup-ov.open{display:flex}
.on-popup{background:#111;border-radius:20px;max-width:420px;width:100%;padding:36px;border:1px solid rgba(255,255,255,.06);box-shadow:0 24px 80px rgba(0,0,0,.6);text-align:center;position:relative}
.on-popup-x{position:absolute;top:14px;right:14px;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.05);color:#888;display:flex;align-items:center;justify-content:center;font-size:1.1rem;transition:all .3s;cursor:pointer;border:none}.on-popup-x:hover{background:var(--red);color:#fff}
.on-popup h3{font-family:'Bebas Neue',sans-serif;font-size:2rem;letter-spacing:1px;margin-bottom:6px}
.on-popup p{color:#888;font-size:.9rem;margin-bottom:28px}
.on-popup-btns{display:flex;gap:14px}
.on-popup-btn{flex:1;padding:22px 16px;border-radius:16px;border:2px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02);cursor:pointer;transition:all .3s;text-align:center}
.on-popup-btn:hover{border-color:rgba(223,43,43,.4);background:rgba(223,43,43,.05);transform:translateY(-2px)}
.on-popup-btn svg{width:36px;height:36px;margin:0 auto 10px;display:block;stroke:#888;transition:all .3s;fill:none}.on-popup-btn:hover svg{stroke:var(--red)}
.on-popup-btn strong{display:block;font-size:1rem;margin-bottom:4px}.on-popup-btn span{font-size:.78rem;color:#666}
.hero-info a svg,.info-bar svg,.info-bar a svg,.info-bar span svg{width:18px!important;height:18px!important;fill:none!important;stroke:var(--red)!important;stroke-width:2!important;flex-shrink:0!important}
.item-card,.itm-info{overflow:visible!important}
.itm-bot,.item-bot{display:flex!important;align-items:center!important;justify-content:space-between!important;gap:8px!important;margin-top:8px!important;flex-wrap:nowrap!important}
.itm-price,.item-price{font-size:1rem!important;white-space:nowrap!important;flex-shrink:0!important}
.itm-add,.item-add{width:34px!important;height:34px!important;min-width:34px!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:center!important}
.sold-out-tag{background:var(--red,#df2b2b)!important;color:#fff!important;font-size:.7rem!important;font-weight:700!important;padding:4px 12px!important;border-radius:50px!important;letter-spacing:.5px!important;text-transform:uppercase!important;display:inline-block!important}
.mg-error{color:#ef4444;font-size:.75rem;font-weight:600;margin-top:4px;display:none}.mg-error.show{display:block}
.mg.has-error{border-color:rgba(239,68,68,.3)!important;background:rgba(239,68,68,.03)!important;border-radius:8px;padding:12px!important}
.mg.has-error .mg-t,.mg.has-error .mg-title{color:#ef4444!important}
@media(max-width:1024px){.header-inner{display:flex!important;align-items:center!important;justify-content:space-between!important;position:relative!important}.mobile-toggle{order:-1!important;position:relative!important;z-index:2!important}.header-inner .logo{position:absolute!important;left:50%!important;transform:translateX(-50%)!important;z-index:1!important}.cart-btn,.btn-cart,.sp-cart-btn{order:99!important;position:relative!important;z-index:2!important;margin-left:auto!important}.nav{display:none!important}}
@media(max-width:768px){.hero{padding-top:120px!important}.info-ticker{padding:8px 0!important}.header{top:36px!important}.mobile-nav{top:110px!important}.on-popup-btns{flex-direction:column}.item-card{height:auto!important;min-height:110px!important}.itm-img,.item-img{width:110px!important;min-width:110px!important}.itm-price,.item-price{font-size:.95rem!important}}
@media(max-width:480px){.btn-cart,.cart-btn{width:42px!important;height:42px!important}.cart-badge{width:18px!important;height:18px!important;font-size:.65rem!important}.header-inner .logo img{height:40px!important}.itm-img,.item-img{width:100px!important;min-width:100px!important}.itm-price,.item-price{font-size:.9rem!important}}
`;
    document.head.appendChild(css);

    // =============== CART BUTTON FIX ===============
    var cartBtn = document.querySelector('.btn-cart');
    if (cartBtn) { cartBtn.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg><span class="cart-badge" id="cartBadge">0</span>'; try { var c = JSON.parse(localStorage.getItem('sp_cart') || '[]'); document.getElementById('cartBadge').textContent = c.reduce(function (s, i) { return s + i.quantity }, 0) } catch (e) { } }

    // =============== TICKER POSITION ===============
    var ticker = document.querySelector('.info-ticker'); var header = document.querySelector('.header');
    if (ticker && header) { header.parentNode.insertBefore(ticker, header) }

    // =============== ORDER NOW POPUP ===============
    if (!document.getElementById('onPopup')) {
        document.body.insertAdjacentHTML('beforeend', '<div class="on-popup-ov" id="onPopup"><div class="on-popup"><button class="on-popup-x" onclick="closeOnPopup()">&times;</button><h3>HOW WOULD YOU LIKE IT?</h3><p>Choose your preferred order method</p><div class="on-popup-btns"><div class="on-popup-btn" onclick="closeOnPopup();openDf()"><svg viewBox="0 0 24 24" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg><strong>Delivery</strong><span>$7.99 fee · 30-45 min</span></div><div class="on-popup-btn" onclick="closeOnPopup();startPickup()"><svg viewBox="0 0 24 24" stroke-width="1.8"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-16 0H3"/><path d="M9 7h1m-1 4h1m4-4h1m-1 4h1"/></svg><strong>Pickup</strong><span>Ready in 20-30 min</span></div></div></div></div>');
        document.getElementById('onPopup').addEventListener('click', function (e) { if (e.target === this) closeOnPopup() });
    }
    window.openOnPopup = function () { document.getElementById('onPopup').classList.add('open'); document.body.style.overflow = 'hidden' };
    window.closeOnPopup = function () { document.getElementById('onPopup').classList.remove('open'); document.body.style.overflow = '' };

    // Nav buttons
    document.querySelectorAll('.nav-cta').forEach(function (a) { if (a.textContent.trim() === 'Order Now') { a.href = '#'; a.onclick = function (e) { e.preventDefault(); openOnPopup() } } });
    document.querySelectorAll('.mob-btns a, .mobile-nav a').forEach(function (a) { if (a.textContent.trim().indexOf('Order Now') > -1) { a.href = '#'; a.onclick = function (e) { e.preventDefault(); if (typeof closeMob === 'function') closeMob(); openOnPopup() } } });

    // Deal banners (home) → open real deal item (syncs with Best Deals)
    document.querySelectorAll('.deal-card').forEach(function (c) {
        c.onclick = function () {
            var nm = c.getAttribute('value') || c.getAttribute('data-deal') || '';
            if (nm && typeof window.orderDealFromHome === 'function') { window.orderDealFromHome(nm); return; }
            if (typeof scrollHero === 'function') scrollHero();
        };
    });

    // Deal alert → popup
    var daB = document.querySelector('.deal-alert-body');
    if (daB) { daB.href = '#'; daB.onclick = function (e) { e.preventDefault(); if (typeof closeDealAlert === 'function') closeDealAlert(); openOnPopup() } }

    // =============== OUTLINE ICONS ===============
    var oPin = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    var oClock = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    document.querySelectorAll('svg').forEach(function (svg) { var ps = svg.querySelectorAll('path'); ps.forEach(function (p) { var d = p.getAttribute('d') || ''; var par = svg.parentNode; if (!par) return; var nh = null; if (d.indexOf('M12 2C8.13') > -1) nh = oPin; if (d.indexOf('M11.99 2C6.47') > -1 || d.indexOf('11.99 2') > -1) nh = oClock; if (nh) { var w = document.createElement('span'); w.innerHTML = nh; var ns = w.querySelector('svg'); if (svg.getAttribute('width')) ns.setAttribute('width', svg.getAttribute('width')); if (svg.getAttribute('height')) ns.setAttribute('height', svg.getAttribute('height')); ns.style.fill = 'none'; ns.style.stroke = 'currentColor'; try { par.replaceChild(ns, svg) } catch (e) { } } }) });

    // =============== CATEGORY IMAGES ===============
    var catMap = { 'best deals': 'best-deals', 'weekly special deals': 'weekly-special-deals', 'indian pizza': 'indian-pizza', 'gourmet pizzas': 'gourmet-pizzas', 'build your own pizza': 'build-your-own-pizza', 'subs': 'subs', 'wraps': 'wraps', "momo's / dumplings": 'momos-dumplings', 'chicken fingers': 'chicken-fingers', 'chicken wings': 'chicken-wings', 'garlic fingers': 'garlic-fingers', 'chicken bites': 'chicken-bites', 'salads': 'salads', 'sides': 'sides', "dessert's": 'desserts' };
    function catSlug(name) { var key = (name || '').toLowerCase().trim(); return catMap[key] || key.replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') }
    function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML }

    var _origRCG = window.renderCatGrid;
    window.renderCatGrid = function (cats) {
        var g = document.getElementById('catGrid'), h = '';
        cats.forEach(function (c, i) {
            var slug = catSlug(c.name), img = '/assets/images/categories/' + slug + '.webp';
            h += '<div class="cat-card" onclick="openCat(\'' + c.id + '\')" style="animation-delay:' + (i * .04) + 's">';
            h += '<img class="cat-card-img" src="' + img + '" alt="' + esc(c.name) + '" onerror="this.onerror=null;var fi=null;try{var cats=menu.categories;for(var j=0;j<cats.length;j++){if(cats[j].id===\'' + c.id + '\'){fi=cats[j].items[0];break;}}}catch(e){};if(fi){this.src=\'/api/image.php?id=\'+fi.id+\'&name=\'+encodeURIComponent(fi.name)}else{this.outerHTML=\'<div class=cat-card-ph></div>\'}" loading="lazy">';
            h += '<div class="cat-card-body"><div class="cat-card-name">' + esc(c.name) + '</div><div class="cat-card-count">' + c.items_count + ' items</div></div></div>';
        }); g.innerHTML = h;
    };

    // =============== SOLD OUT TAG FIX ===============
    var _origOC = window.openCat;
    if (_origOC) { window.openCat = function (id) { _origOC(id); setTimeout(function () { document.querySelectorAll('.item-card').forEach(function (card) { card.querySelectorAll('span').forEach(function (el) { if (el.textContent.trim() === 'Sold out') el.outerHTML = '<span class="sold-out-tag">Sold Out</span>' }) }) }, 50) } }

    // =============== REQUIRED MODIFIER VALIDATION ===============
    var _origAC = window.addCart;
    window.addCart = function () {
        if (!window.curItem) return; var mods = window.curItem.modifier_groups || [], hasErr = false;
        mods.forEach(function (mg) {
            var sel = (window.selMods || {})[mg.id] || []; var ee = document.getElementById('mg-err-' + mg.id); var ge = document.getElementById('mg-grp-' + mg.id);
            if (mg.min > 0 && sel.length < mg.min) { hasErr = true; if (ee) { ee.textContent = 'Please select at least ' + mg.min + ' option' + (mg.min > 1 ? 's' : ''); ee.classList.add('show') } if (ge) ge.classList.add('has-error') } else { if (ee) ee.classList.remove('show'); if (ge) ge.classList.remove('has-error') }
        });
        if (hasErr) { var fe = document.querySelector('.mg.has-error'); if (fe) fe.scrollIntoView({ behavior: 'smooth', block: 'center' }); return }
        if (_origAC) _origAC();
    };
    var _origRM = window.renderMo;
    window.renderMo = function () { if (_origRM) _origRM(); setTimeout(function () { var item = window.curItem; if (!item) return; var mgs = item.modifier_groups || []; document.querySelectorAll('.mg').forEach(function (el, idx) { if (mgs[idx]) { el.id = 'mg-grp-' + mgs[idx].id; if (!el.querySelector('.mg-error')) { var ed = document.createElement('div'); ed.className = 'mg-error'; ed.id = 'mg-err-' + mgs[idx].id; el.appendChild(ed) } if (mgs[idx].min > 0) { var t = el.querySelector('.mg-t') || el.querySelector('.mg-title'); if (t && t.textContent.indexOf('*') === -1) t.innerHTML = t.textContent + ' <span style="color:#ef4444">*</span>' } } }) }, 50) };

    // =============== DEAL ALERT — KILL ONCE USER LEAVES SCREEN1 ===============
    var alertKilled = false;
    function killAlert() { if (alertKilled) return; alertKilled = true; var da = document.getElementById('daEl') || document.getElementById('dealAlert'); if (da) { da.classList.remove('show'); da.style.display = 'none' } window.showDA = function () { }; window.hideDA = function () { }; window.closeDealAlert = function () { } }
    function reviveAlert() {
        if (!alertKilled) return; alertKilled = false; var da = document.getElementById('daEl') || document.getElementById('dealAlert'); if (da) da.style.display = ''; var pb = da ? da.querySelector('.da-prog-bar') || document.getElementById('daPB') || document.getElementById('alertProgress') : null; var ht2 = null, nt2 = null;
        window.showDA = function () { if (alertKilled) return; clearTimeout(ht2); clearTimeout(nt2); if (da) da.classList.add('show'); if (pb) { pb.style.animation = 'none'; pb.offsetHeight; pb.style.animation = 'progShrink 30s linear forwards' } ht2 = setTimeout(function () { window.hideDA() }, 30000) };
        window.hideDA = function () { clearTimeout(ht2); if (da) da.classList.remove('show'); if (pb) pb.style.animation = 'none'; nt2 = setTimeout(function () { window.showDA() }, 40000) }; window.closeDealAlert = window.hideDA; setTimeout(function () { window.showDA() }, 2000)
    }
    function checkKill() { var s1 = document.getElementById('screen1'); var on = s1 && s1.classList.contains('active'); if (!on && !alertKilled) killAlert(); else if (on && alertKilled) reviveAlert() }
    ['showScr', 'goMenu', 'openCat', 'openIt', 'openDf', 'startPickup', 'goChk', 'toggleC'].forEach(function (fn) { var o = window[fn]; if (o && typeof o === 'function') { window[fn] = function () { var r = o.apply(this, arguments); setTimeout(checkKill, 100); return r } } });
    var mo = new MutationObserver(function () { setTimeout(checkKill, 100) });
    document.querySelectorAll('.screen').forEach(function (s) { mo.observe(s, { attributes: true, attributeFilter: ['class'] }) });
    setTimeout(checkKill, 500);

    // =============== ENHANCED CONFIRMATION ===============
    var ccss = document.createElement('style');
    ccss.textContent = `.ec{text-align:center;padding:20px;max-width:560px;margin:0 auto;animation:ecF .6s ease}@keyframes ecF{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}.ec-check{width:100px;height:100px;margin:0 auto 28px;position:relative}.ec-circle{width:100px;height:100px;border-radius:50%;background:rgba(34,197,94,.1);display:flex;align-items:center;justify-content:center;animation:ecP .5s cubic-bezier(.34,1.56,.64,1)}@keyframes ecP{from{transform:scale(0)}to{transform:scale(1)}}.ec-circle svg{width:48px;height:48px;stroke:#22c55e;stroke-width:2.5;fill:none}.ec-circle svg path{stroke-dasharray:60;stroke-dashoffset:60;animation:ecD .6s ease .3s forwards}@keyframes ecD{to{stroke-dashoffset:0}}.ec-confetti{position:absolute;width:8px;height:8px;border-radius:50%;animation:ecB 1s ease forwards}@keyframes ecB{0%{opacity:1;transform:translate(0,0) scale(1)}100%{opacity:0;transform:translate(var(--tx),var(--ty)) scale(0)}}.ec h2{font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:2px;margin-bottom:6px;color:#fff}.ec-order-num{display:inline-block;background:rgba(34,197,94,.12);color:#22c55e;padding:8px 24px;border-radius:50px;font-weight:700;font-size:1rem;letter-spacing:1px;margin-bottom:8px}.ec-sub{color:#888;font-size:.92rem;margin-bottom:32px;line-height:1.6}.ec-type{display:inline-flex;align-items:center;gap:8px;padding:10px 24px;border-radius:50px;font-weight:600;font-size:.85rem;letter-spacing:.5px;margin-bottom:28px}.ec-type-pickup{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2)}.ec-type-delivery{background:rgba(223,43,43,.1);color:#df2b2b;border:1px solid rgba(223,43,43,.2)}.ec-type svg{width:18px;height:18px;stroke:currentColor;fill:none}.ec-card{background:rgba(26,26,26,.6);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.06);border-radius:16px;overflow:hidden;margin-bottom:16px;text-align:left}.ec-card-hdr{padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:10px;font-weight:700;font-size:.82rem;color:#888;text-transform:uppercase;letter-spacing:1.5px}.ec-card-hdr svg{width:18px;height:18px;stroke:#df2b2b;fill:none;stroke-width:2}.ec-card-body{padding:20px 24px}.ec-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;font-size:.9rem;border-bottom:1px solid rgba(255,255,255,.04)}.ec-row:last-child{border:none}.ec-row span:first-child{color:#888}.ec-row span:last-child{font-weight:600;color:#fff}.ec-row.ec-total{border-top:1px solid rgba(255,255,255,.08)!important;border-bottom:none!important;margin-top:8px;padding-top:16px;font-size:1.1rem;font-weight:800}.ec-row.ec-total span:first-child{color:#fff}.ec-row.ec-total span:last-child{color:#22c55e;font-family:'Bebas Neue',sans-serif;font-size:1.4rem;letter-spacing:1px}.ec-timeline{display:flex;justify-content:space-between;padding:24px 20px;position:relative;margin-top:4px}.ec-timeline::before{content:'';position:absolute;top:38px;left:15%;right:15%;height:2px;background:rgba(255,255,255,.06)}.ec-timeline::after{content:'';position:absolute;top:38px;left:15%;width:33%;height:2px;background:#22c55e;animation:ecL 1.5s ease .5s forwards}@keyframes ecL{from{width:0}to{width:33%}}.ec-step{text-align:center;position:relative;z-index:1;flex:1}.ec-step-dot{width:28px;height:28px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800}.ec-step-dot.done{background:#22c55e;color:#fff}.ec-step-dot.active{background:rgba(223,43,43,.15);color:#df2b2b;border:2px solid #df2b2b;animation:ecPu 2s ease infinite}@keyframes ecPu{0%,100%{box-shadow:0 0 0 0 rgba(223,43,43,.3)}50%{box-shadow:0 0 12px 4px rgba(223,43,43,.15)}}.ec-step-dot.pending{background:rgba(255,255,255,.05);color:#555;border:2px solid rgba(255,255,255,.08)}.ec-step-label{font-size:.72rem;color:#888;font-weight:500}.ec-step.active .ec-step-label{color:#df2b2b;font-weight:700}.ec-eta{background:rgba(223,43,43,.06);border:1px solid rgba(223,43,43,.15);border-radius:12px;padding:20px;display:flex;align-items:center;gap:16px;margin-bottom:20px;text-align:left}.ec-eta-icon{width:50px;height:50px;border-radius:50%;background:rgba(223,43,43,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0}.ec-eta-icon svg{width:24px;height:24px;stroke:#df2b2b;fill:none;stroke-width:2}.ec-eta-text h4{font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:1px;color:#fff;margin-bottom:2px}.ec-eta-text p{font-size:.82rem;color:#888}.ec-email{display:flex;align-items:center;gap:10px;justify-content:center;color:#888;font-size:.82rem;margin:20px 0 12px;padding:14px 20px;background:rgba(255,255,255,.02);border-radius:50px;border:1px solid rgba(255,255,255,.04)}.ec-email svg{width:16px;height:16px;stroke:#22c55e;fill:none;stroke-width:2;flex-shrink:0}.ec-track-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);border-radius:50px;color:#22c55e;font-weight:700;font-size:.95rem;letter-spacing:.5px;margin:16px 0 24px;transition:all .3s}.ec-track-btn:hover{background:rgba(34,197,94,.2);transform:translateY(-2px)}.ec-track-btn svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:2}.ec-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}.ec-btn-new{background:rgba(255,255,255,.05);color:#fff;padding:14px 32px;border-radius:50px;font-weight:600;font-size:.9rem;transition:all .3s;border:1px solid rgba(255,255,255,.08)}.ec-btn-new:hover{background:rgba(255,255,255,.1)}.ec-btn-call{background:#df2b2b;color:#fff;padding:14px 32px;border-radius:50px;font-weight:600;font-size:.9rem;transition:all .3s;display:flex;align-items:center;gap:8px;box-shadow:0 4px 20px rgba(223,43,43,.25)}.ec-btn-call:hover{background:#b91c1c}.ec-btn-call svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2}`;
    document.head.appendChild(ccss);

    window.rConf = function (w) {
        var o = (window.chkData || {}).conf; if (!o) { if (typeof closeChk === 'function') closeChk(); return }
        var isDel = o.type === 'delivery', eta = isDel ? '30-45 minutes' : '20-30 minutes', etaL = isDel ? 'Estimated Delivery' : 'Estimated Ready', tUrl = o.tracking_url || ('/track.html?order=' + o.number);
        var conf = '', cols = ['#df2b2b', '#f5a623', '#22c55e', '#3b82f6', '#fff']; for (var i = 0; i < 12; i++) { var a = (i / 12) * 360, d = 40 + Math.random() * 30; conf += '<div class="ec-confetti" style="background:' + cols[i % 5] + ';top:46px;left:46px;animation-delay:' + (i * .05) + 's;--tx:' + Math.cos(a * Math.PI / 180) * d + 'px;--ty:' + Math.sin(a * Math.PI / 180) * d + 'px"></div>' }
        w.innerHTML = '<div class="ec"><div class="ec-check"><div class="ec-circle"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg></div>' + conf + '</div><h2>ORDER CONFIRMED!</h2><div class="ec-order-num">#' + esc(o.number) + '</div><p class="ec-sub">Thank you! We\'re preparing your food with love.</p><div class="ec-type ec-type-' + (isDel ? 'delivery' : 'pickup') + '">' + (isDel ? '<svg viewBox="0 0 24 24" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>' : '<svg viewBox="0 0 24 24" stroke-width="1.8"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2m-16 0H3"/></svg>') + (isDel ? 'Delivery Order' : 'Pickup Order') + '</div><div class="ec-eta"><div class="ec-eta-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div class="ec-eta-text"><h4>' + etaL + '</h4><p>' + eta + ' — We\'ll have it ready!</p></div></div><div class="ec-card"><div class="ec-card-hdr"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg> Order Progress</div><div class="ec-timeline"><div class="ec-step"><div class="ec-step-dot done">✓</div><div class="ec-step-label">Placed</div></div><div class="ec-step active"><div class="ec-step-dot active">2</div><div class="ec-step-label">Preparing</div></div><div class="ec-step"><div class="ec-step-dot pending">3</div><div class="ec-step-label">' + (isDel ? 'On the way' : 'Ready') + '</div></div><div class="ec-step"><div class="ec-step-dot pending">4</div><div class="ec-step-label">' + (isDel ? 'Delivered' : 'Picked up') + '</div></div></div></div><div class="ec-card"><div class="ec-card-hdr"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> Customer</div><div class="ec-card-body"><div class="ec-row"><span>Name</span><span>' + esc(o.customer.name || '') + '</span></div><div class="ec-row"><span>Phone</span><span>' + esc(o.customer.phone || '') + '</span></div>' + (isDel ? '<div class="ec-row"><span>Address</span><span>' + esc((o.customer.address || '') + ', ' + (o.customer.city || '') + ' ' + (o.customer.postal || '')) + '</span></div>' : '<div class="ec-row"><span>Pickup</span><span>6169 Quinpool Rd #111</span></div>') + '</div></div><div class="ec-card"><div class="ec-card-hdr"><svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg> Payment</div><div class="ec-card-body"><div class="ec-row"><span>Subtotal</span><span>$' + o.subtotal + '</span></div><div class="ec-row"><span>HST</span><span>$' + o.tax + '</span></div>' + (o.delivery_fee !== '0.00' ? '<div class="ec-row"><span>Delivery</span><span>$' + o.delivery_fee + '</span></div>' : '') + '<div class="ec-row ec-total"><span>Total</span><span>$' + o.total + '</span></div>' + (o.payment && o.payment.brand ? '<div class="ec-row"><span>Card</span><span>' + o.payment.brand + ' ****' + o.payment.last4 + '</span></div>' : '') + '</div></div><div class="ec-email"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg> Confirmation sent to <strong style="color:#fff;margin-left:4px">' + esc(o.customer.email || '') + '</strong></div><a href="' + tUrl + '" class="ec-track-btn"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Track Your Order</a><div class="ec-actions"><button class="ec-btn-new" onclick="closeChk();location.reload()">Place Another Order</button><a href="tel:9028004001" class="ec-btn-call"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72"/></svg> Call Store</a></div></div>';
    };

    // =============== SCROLL & KEYBOARD ===============
    window.addEventListener('scroll', function () { var h = document.querySelector('.header'); if (h) h.classList.toggle('scrolled', window.scrollY > 50) });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeOnPopup() });

    // =============== FLOW DETECTION ===============
    if (flow) {
        var chk = setInterval(function () {
            var ready = (flow === 'delivery' && typeof openDf === 'function') || (flow === 'pickup' && typeof startPickup === 'function') || (flow === 'checkout' && typeof startPickup === 'function');
            if (ready) {
                clearInterval(chk); if (flow === 'delivery') openDf(); else if (flow === 'pickup') startPickup(); else if (flow === 'checkout') { startPickup(); setTimeout(function () { if (typeof toggleCart === 'function') toggleCart() }, 200) }
                setTimeout(function () { document.documentElement.style.transition = 'opacity .3s ease'; document.documentElement.style.opacity = '1' }, 50)
            }
        }, 20);
        setTimeout(function () { clearInterval(chk); document.documentElement.style.transition = 'opacity .3s ease'; document.documentElement.style.opacity = '1' }, 2000);
    }

})();
