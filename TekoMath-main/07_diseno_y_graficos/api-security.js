/* Attach session-bound tokens only to TEKO's same-origin mutation endpoints. */
(()=>{
 const script=document.currentScript;const tokenUrl=new URL('../01_sistema_principal_y_registro_offline/api_csrf.php',script.src);
 const original=window.fetch.bind(window);let tokenPromise;
 window.fetch=async(input,init={})=>{
  const url=new URL(input instanceof Request?input.url:String(input),location.href);
  const method=(init.method||(input instanceof Request?input.method:'GET')).toUpperCase();
  if(url.origin===location.origin&&/\/api_(?:tareas|materiales|sala|progreso)\.php$/.test(url.pathname)&&['POST','PUT','PATCH','DELETE'].includes(method)){
   if(!tokenPromise)tokenPromise=original(tokenUrl,{credentials:'same-origin',cache:'no-store'}).then(async r=>{if(!r.ok)throw Error('Iniciá sesión nuevamente.');return (await r.json()).token;}).catch(e=>{tokenPromise=null;throw e;});
   const headers=new Headers(init.headers||(input instanceof Request?input.headers:undefined));headers.set('X-Teko-CSRF',await tokenPromise);init={...init,headers};
  }
  return original(input,init);
 };
})();
