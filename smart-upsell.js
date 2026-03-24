/**
 * Slice+ Smart Upsell v2
 * - Context-aware suggestions based on cart contents
 * - Premium design with categories, badges
 * - Never shows items already in cart
 */
(function(){

// Smart pairing rules: if cart has X category → suggest from Y
var pairRules = [
    // Pizza → suggest drinks, sides, garlic fingers, desserts
    { if: ['Indian Pizza','Gourmet Pizzas','Build Your Own Pizza'], suggest: ['Sides','Garlic Fingers',"Dessert's",'Chicken Bites'], label: 'Goes great with pizza!' },
    // Wings/Chicken → suggest garlic fingers, sides, drinks
    { if: ['Chicken Wings','Chicken Fingers','Chicken Bites'], suggest: ['Garlic Fingers','Sides',"Dessert's"], label: 'Perfect with wings!' },
    // Subs/Wraps → suggest sides, drinks, desserts
    { if: ['Subs','Wraps'], suggest: ['Sides','Chicken Bites',"Dessert's",'Garlic Fingers'], label: 'Complete your meal!' },
    // Momos → suggest sides, chicken
    { if: ["Momo's / Dumplings"], suggest: ['Chicken Bites','Sides','Garlic Fingers',"Dessert's"], label: 'Add a side!' },
    // Salads → suggest garlic fingers, desserts
    { if: ['Salads'], suggest: ['Garlic Fingers',"Dessert's",'Sides','Chicken Bites'], label: 'Make it a feast!' },
    // Garlic Fingers → suggest desserts, chicken
    { if: ['Garlic Fingers'], suggest: ["Dessert's",'Chicken Bites','Sides'], label: 'Still hungry?' },
    // Deals → suggest sides, desserts
    { if: ['Best Deals','Weekly Special Deals'], suggest: ['Sides','Garlic Fingers',"Dessert's",'Chicken Bites'], label: 'Add to your deal!' },
];

// Fallback categories (if no rules match)
var fallbackCats = ['Garlic Fingers','Sides',"Dessert's",'Chicken Bites'];

// Premium CSS
var css = document.createElement('style');
css.textContent = `
.ups-ov{display:none;position:fixed;inset:0;z-index:2500;background:rgba(0,0,0,.7);backdrop-filter:blur(10px);align-items:center;justify-content:center;padding:20px}.ups-ov.open{display:flex}
.ups-box{background:#111;border-radius:20px;max-width:500px;width:100%;max-height:80vh;overflow-y:auto;border:1px solid rgba(255,255,255,.08);box-shadow:0 24px 80px rgba(0,0,0,.6),0 0 40px rgba(223,43,43,.05);padding:0}
.ups-box::-webkit-scrollbar{width:4px}.ups-box::-webkit-scrollbar-track{background:transparent}.ups-box::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:4px}

/* Header */
.ups-hdr{padding:28px 28px 0;text-align:center}
.ups-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(223,43,43,.1);border:1px solid rgba(223,43,43,.2);padding:6px 16px;border-radius:50px;font-size:.72rem;font-weight:700;color:#df2b2b;letter-spacing:1px;text-transform:uppercase;margin-bottom:14px}
.ups-badge svg{width:14px;height:14px;fill:none;stroke:#df2b2b;stroke-width:2}
.ups-t{font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:1.5px;margin-bottom:4px;color:#fff}
.ups-s{color:#888;font-size:.85rem;margin-bottom:0;line-height:1.5}

/* Context label */
.ups-context{display:flex;align-items:center;gap:8px;padding:12px 28px;margin-top:16px;background:rgba(245,166,35,.05);border-top:1px solid rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.04)}
.ups-context svg{width:16px;height:16px;fill:none;stroke:#f5a623;stroke-width:2;flex-shrink:0}
.ups-context span{font-size:.8rem;color:#f5a623;font-weight:600}

/* Items */
.ups-list{padding:16px 28px}
.ups-i{display:flex;gap:14px;padding:14px;border-radius:14px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);margin-bottom:10px;transition:all .3s;align-items:center}
.ups-i:hover{border-color:rgba(223,43,43,.25);background:rgba(223,43,43,.03);transform:translateX(4px)}
.ups-img{width:70px;height:70px;border-radius:12px;object-fit:cover;background:#1a1a1a;flex-shrink:0}.ups-img.hid{display:none}
.ups-ph{width:70px;height:70px;border-radius:12px;background:linear-gradient(135deg,#1a1a1a,#222);flex-shrink:0;display:flex;align-items:center;justify-content:center}
.ups-ph svg{width:28px;height:28px;stroke:#333;fill:none;stroke-width:1.5}
.ups-inf{flex:1;min-width:0}
.ups-cat{font-size:.68rem;color:#666;text-transform:uppercase;letter-spacing:.8px;font-weight:600;margin-bottom:2px}
.ups-n{font-weight:600;font-size:.9rem;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ups-p{color:#22c55e;font-weight:700;font-size:.9rem;margin-top:3px}
.ups-add{padding:10px 18px;background:#df2b2b;color:#fff;border-radius:50px;font-size:.78rem;font-weight:700;transition:all .3s;white-space:nowrap;flex-shrink:0;letter-spacing:.3px;box-shadow:0 2px 10px rgba(223,43,43,.2)}
.ups-add:hover{background:#b91c1c;transform:scale(1.05)}
.ups-add.added{background:#22c55e!important;box-shadow:0 2px 10px rgba(34,197,94,.2)}

/* Footer */
.ups-ft{padding:16px 28px 24px;border-top:1px solid rgba(255,255,255,.04)}
.ups-skip{width:100%;padding:14px;background:rgba(255,255,255,.04);color:#aaa;border-radius:50px;font-weight:600;font-size:.9rem;transition:all .3s;text-align:center;border:1px solid rgba(255,255,255,.06)}
.ups-skip:hover{background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.1)}
.ups-skip svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;vertical-align:middle;margin-right:6px}

@media(max-width:480px){.ups-box{max-height:85vh;border-radius:16px}.ups-hdr{padding:24px 20px 0}.ups-list{padding:12px 20px}.ups-ft{padding:12px 20px 20px}.ups-i{padding:12px;gap:10px}.ups-img,.ups-ph{width:60px;height:60px}}
`;
document.head.appendChild(css);

// Override showUps
window.showUps = function(){
    if(!cart.length) return;
    toggleCart();
    if(!menu){ goChk(); return; }
    
    // Find what categories are in cart
    var cartCatNames = [];
    var cartItemIds = cart.map(function(c){ return c.id; });
    
    menu.categories.forEach(function(cat){
        cat.items.forEach(function(it){
            if(cartItemIds.indexOf(it.id) > -1 && cartCatNames.indexOf(cat.name) === -1){
                cartCatNames.push(cat.name);
            }
        });
    });
    
    // Find matching pair rules
    var suggestCats = [];
    var contextLabel = 'Complete your order!';
    
    for(var r = 0; r < pairRules.length; r++){
        var rule = pairRules[r];
        for(var c = 0; c < rule.if.length; c++){
            if(cartCatNames.indexOf(rule.if[c]) > -1){
                suggestCats = rule.suggest;
                contextLabel = rule.label;
                break;
            }
        }
        if(suggestCats.length) break;
    }
    
    // Fallback
    if(!suggestCats.length) suggestCats = fallbackCats;
    
    // Remove categories already in cart from suggestions
    suggestCats = suggestCats.filter(function(sc){
        return cartCatNames.indexOf(sc) === -1;
    });
    if(!suggestCats.length) suggestCats = fallbackCats;
    
    // Pick items from suggested categories
    var upsellItems = [];
    var usedIds = {};
    
    suggestCats.forEach(function(catName){
        if(upsellItems.length >= 4) return;
        menu.categories.forEach(function(cat){
            if(cat.name !== catName || upsellItems.length >= 4) return;
            cat.items.forEach(function(it){
                if(it.available && cartItemIds.indexOf(it.id) === -1 && !usedIds[it.id] && upsellItems.length < 4){
                    upsellItems.push({ item: it, catName: catName });
                    usedIds[it.id] = true;
                }
            });
        });
    });
    
    if(!upsellItems.length){ goChk(); return; }
    
    // Build HTML
    var h = '<div class="ups-hdr">' +
        '<div class="ups-badge"><svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg> RECOMMENDED</div>' +
        '<h3 class="ups-t">COMPLETE YOUR ORDER</h3>' +
        '<p class="ups-s">Customers who ordered similar items also added these</p>' +
        '</div>';
    
    h += '<div class="ups-context"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12l2 2 4-4"/></svg><span>' + esc(contextLabel) + '</span></div>';
    
    h += '<div class="ups-list">';
    upsellItems.forEach(function(u){
        var it = u.item;
        h += '<div class="ups-i">' +
            '<img class="ups-img" src="/api/image.php?id=' + it.id + '&name=' + encodeURIComponent(it.name) + '" onerror="this.outerHTML=\'<div class=ups-ph><svg viewBox=&quot;0 0 24 24&quot;><path d=&quot;M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z&quot;/></svg></div>\'" loading="lazy">' +
            '<div class="ups-inf">' +
            '<div class="ups-cat">' + esc(u.catName) + '</div>' +
            '<div class="ups-n">' + esc(it.name) + '</div>' +
            '<div class="ups-p">$' + it.price + '</div>' +
            '</div>' +
            '<button class="ups-add" onclick="qAdd(\'' + it.id + '\',\'' + esc(it.name).replace(/'/g, "\\'") + '\',' + it.price_cents + ',this)">+ Add</button>' +
            '</div>';
    });
    h += '</div>';
    
    h += '<div class="ups-ft"><button class="ups-skip" onclick="closeUps();goChk()"><svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>No thanks, proceed to checkout</button></div>';
    
    document.getElementById('upsBox').innerHTML = h;
    document.getElementById('upsOv').classList.add('open');
    document.body.style.overflow = 'hidden';
};

// Enhanced add button
var _origQAdd = window.qAdd;
window.qAdd = function(id, n, p, b){
    if(_origQAdd) _origQAdd(id, n, p, b);
    if(b){
        b.textContent = '✓ Added';
        b.classList.add('added');
        b.disabled = true;
    }
};

function esc(s){ var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

})();