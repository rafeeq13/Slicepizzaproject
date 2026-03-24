/**
 * Slice+ Track Page — Uber Direct Live Updates
 * Add to track.html before </body>
 * Changes polling to use uber-status.php for live Uber tracking
 */
(function(){

// Override fetch URLs to use uber-status.php (polls Uber in real-time)
var _origTrack = window.trackOrder;
window.trackOrder = function(){
    var num = document.getElementById('orderInput').value.trim().replace('#','');
    if(!num){ alert('Please enter order number'); return; }
    
    window.currentOrder = num;
    show('loadingSection');
    
    fetch('/api/uber-status.php?order='+encodeURIComponent(num))
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(!data.success){
            document.getElementById('errorMsg').textContent = data.message || 'Order not found';
            show('errorSection');
            return;
        }
        renderOrder(data.order);
        show('orderSection');
        
        if(window.refreshTimer) clearInterval(window.refreshTimer);
        window.refreshTimer = setInterval(function(){ silentRefresh(num); }, 15000); // 15s for Uber updates
    })
    .catch(function(err){
        document.getElementById('errorMsg').textContent = 'Connection error. Please try again.';
        show('errorSection');
    });
};

window.silentRefresh = function(num){
    fetch('/api/uber-status.php?order='+encodeURIComponent(num))
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success) renderOrder(data.order);
    }).catch(function(){});
};

// Enhanced renderOrder with courier info
var _origRender = window.renderOrder;
window.renderOrder = function(o){
    _origRender(o);
    
    // Add courier card if available
    if(o.uber_delivery && o.uber_delivery.courier && o.uber_delivery.courier.name){
        var c = o.uber_delivery.courier;
        var courierHtml = '<div class="t-card" style="border-color:rgba(34,197,94,0.2)">'+
            '<div class="t-card-hdr"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/></svg> Your Delivery Rider</div>'+
            '<div class="t-card-body">'+
            '<div class="t-row"><span>Name</span><span style="color:#22c55e;font-weight:700">'+escH(c.name)+'</span></div>'+
            (c.vehicle ? '<div class="t-row"><span>Vehicle</span><span>'+escH(c.vehicle)+'</span></div>' : '')+
            (c.phone ? '<div class="t-row"><span>Contact</span><span><a href="tel:'+escH(c.phone)+'" style="color:#3b82f6">Call Rider</a></span></div>' : '')+
            '</div></div>';
        
        // Insert after progress timeline
        var orderSection = document.getElementById('orderSection');
        var cards = orderSection.querySelectorAll('.t-card');
        if(cards.length > 0){
            cards[0].insertAdjacentHTML('afterend', courierHtml);
        }
    }
    
    // Add Uber tracking link if available
    if(o.uber_delivery && o.uber_delivery.tracking_url && o.status === 'out_for_delivery'){
        var uberLink = '<a href="'+o.uber_delivery.tracking_url+'" target="_blank" style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);border-radius:50px;color:#22c55e;font-weight:700;font-size:.9rem;margin:12px 0;transition:all .3s"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg> Track Rider on Map</a>';
        
        var eta = document.querySelector('.t-eta');
        if(eta) eta.insertAdjacentHTML('afterend', uberLink);
    }
};

function escH(s){var d=document.createElement('div');d.textContent=s||'';return d.innerHTML}

})();