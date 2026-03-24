/**
 * Slice+ Form AJAX Submit
 * No redirect to Formspree — shows inline success/error message
 * Works on both Contact and Catering forms
 * Add to contact.html and catering.html before </body>
 */
(function(){

var css = document.createElement('style');
css.textContent = `
.form-msg{padding:16px 24px;border-radius:12px;font-size:.9rem;font-weight:600;margin-top:16px;display:none;align-items:center;gap:10px;animation:fmFade .4s ease}
@keyframes fmFade{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.form-msg.success{display:flex;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.25);color:#22c55e}
.form-msg.error{display:flex;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#ef4444}
.form-msg svg{width:20px;height:20px;flex-shrink:0;fill:currentColor}
.form-sending{opacity:.6;pointer-events:none}
`;
document.head.appendChild(css);

document.querySelectorAll('form[action*="formspree.io"]').forEach(function(form){
    // Add message div after submit button
    var msgDiv = document.createElement('div');
    msgDiv.className = 'form-msg';
    form.appendChild(msgDiv);

    form.addEventListener('submit', function(e){
        e.preventDefault();
        
        var submitBtn = form.querySelector('button[type="submit"],.form-submit,.btn-primary');
        var origText = submitBtn ? submitBtn.innerHTML : '';
        
        // Loading state
        form.classList.add('form-sending');
        if(submitBtn) submitBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite"><circle cx="12" cy="12" r="10"/></svg> Sending...';
        msgDiv.className = 'form-msg';
        
        var formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r){ return r.json().then(function(d){ return {ok: r.ok, data: d}; }); })
        .then(function(res){
            form.classList.remove('form-sending');
            if(submitBtn) submitBtn.innerHTML = origText;
            
            if(res.ok){
                msgDiv.className = 'form-msg success';
                msgDiv.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg> Thank you! We have received your inquiry. Our team will contact you shortly.';
                form.reset();
                
                // Auto hide after 8 seconds
                setTimeout(function(){
                    msgDiv.className = 'form-msg';
                }, 8000);
            } else {
                msgDiv.className = 'form-msg error';
                msgDiv.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Something went wrong. Please call us at (902) 800-4001.';
            }
        })
        .catch(function(){
            form.classList.remove('form-sending');
            if(submitBtn) submitBtn.innerHTML = origText;
            msgDiv.className = 'form-msg error';
            msgDiv.innerHTML = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Connection error. Please try again or call (902) 800-4001.';
        });
    });
});

})();