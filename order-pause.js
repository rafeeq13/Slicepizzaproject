/**
 * ORDERING PAUSED
 * Add to index.html before </body> to disable ordering
 * DELETE this file to resume ordering
 */
(function(){
var css=document.createElement('style');
css.textContent=`.order-paused-banner{position:fixed;top:0;left:0;right:0;z-index:99999;background:linear-gradient(90deg,#b91c1c,#df2b2b);color:#fff;text-align:center;padding:12px 20px;font-weight:700;font-size:.85rem;letter-spacing:.5px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 20px rgba(0,0,0,.3)}.order-paused-banner svg{width:18px;height:18px;fill:none;stroke:#fff;stroke-width:2;flex-shrink:0;animation:opSpin 2s linear infinite}@keyframes opSpin{to{transform:rotate(360deg)}}.info-ticker{top:42px!important}.header{top:84px!important}.mobile-nav{top:158px!important}.hero{padding-top:230px!important}.s2{padding-top:190px!important}.s3{padding-top:170px!important}@media(max-width:768px){.header{top:78px!important}.mobile-nav{top:152px!important}.hero{padding-top:210px!important}}`;
document.head.appendChild(css);

// Banner
var banner=document.createElement('div');
banner.className='order-paused-banner';
banner.innerHTML='<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg> Online ordering is temporarily paused. We will be back shortly! Call <a href="tel:9028004001" style="color:#fff;text-decoration:underline">(902) 800-4001</a> to order.';
document.body.insertBefore(banner,document.body.firstChild);

// Disable ALL order functions
window.openDf=function(){alert('Online ordering is temporarily paused. Please call (902) 800-4001 to place your order.')};
window.startPickup=function(){alert('Online ordering is temporarily paused. Please call (902) 800-4001 to place your order.')};
window.openOnPopup=function(){alert('Online ordering is temporarily paused. Please call (902) 800-4001 to place your order.')};
window.openOP=function(){alert('Online ordering is temporarily paused. Please call (902) 800-4001 to place your order.')};

// Disable hero buttons
document.querySelectorAll('.hero-btn,.hero-btn-del,.hero-btn-pick,.op-btn').forEach(function(b){
    b.style.opacity='.5';b.style.pointerEvents='none';
});

// Disable deal card clicks
document.querySelectorAll('.deal-card').forEach(function(c){c.onclick=null;c.style.cursor='default'});
})();