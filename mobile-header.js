/**
 * Slice+ Mobile Header Fix
 * Hamburger: LEFT | Logo: CENTER | Cart: RIGHT
 * Only affects mobile — desktop unchanged
 */
(function(){
var css = document.createElement('style');
css.textContent = `
@media(max-width:1024px){
    .header-inner{
        display:flex!important;
        align-items:center!important;
        justify-content:space-between!important;
        position:relative!important;
    }
    
    /* Hamburger — LEFT */
    .mobile-toggle{
        order:-1!important;
        position:relative!important;
        z-index:2!important;
    }
    
    /* Logo — CENTER (absolute center) */
    .header-inner .logo{
        position:absolute!important;
        left:50%!important;
        transform:translateX(-50%)!important;
        z-index:1!important;
    }
    
    /* Cart — RIGHT */
    .cart-btn,.btn-cart,.sp-cart-btn{
        order:99!important;
        position:relative!important;
        z-index:2!important;
        margin-left:auto!important;
    }
    
    /* Hide nav links on mobile (already hidden but just in case) */
    .nav{display:none!important}
}

/* Fine-tune for small screens */
@media(max-width:480px){
    .header-inner .logo img{
        height:40px!important;
    }
    .cart-btn,.btn-cart,.sp-cart-btn{
        width:42px!important;
        height:42px!important;
    }
}
`;
document.head.appendChild(css);
})();