(() => {
 const updateHeight=()=>document.documentElement.style.setProperty('--teko-viewport',`${Math.round(window.visualViewport?.height||window.innerHeight)}px`);
 updateHeight();window.visualViewport?.addEventListener('resize',updateHeight);window.addEventListener('resize',updateHeight);
 const links=[...document.querySelectorAll('.mobile-study-nav a')];
 function selectView(hash){
  const visuals=hash==='#learning-visuals';document.body.classList.toggle('study-visuals',visuals);
  links.forEach(a=>{if(a.hash===(visuals?'#learning-visuals':'#learning-chat'))a.setAttribute('aria-current','page');else a.removeAttribute('aria-current');});
  window.dispatchEvent(new Event('resize'));
 }
 links.forEach(a=>a.addEventListener('click',event=>{if(!matchMedia('(max-width:960px)').matches)return;event.preventDefault();selectView(a.hash);}));
 selectView(location.hash);
})();
