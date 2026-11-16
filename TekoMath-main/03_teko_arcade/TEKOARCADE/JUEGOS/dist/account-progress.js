/* Account-bound Arcade boot and optimistic server synchronization. */
(()=>{
 const script=document.currentScript;const prefix=new URL(script.src).pathname.split('/03_teko_arcade/')[0];const endpoint=prefix+'/01_sistema_principal_y_registro_offline/api_arcade_progress.php';
 let revision=0,token='',userId=0,queue=null,busy=false,blocked=false;
 const status=document.createElement('div');status.id='arcade-sync-status';status.setAttribute('role','status');status.style.cssText='position:fixed;bottom:12px;right:12px;z-index:10000;max-width:calc(100vw - 24px);padding:8px 12px;background:#fffdf8;border:2px solid #1a1a1a;border-radius:12px;box-shadow:3px 3px #1a1a1a;font:12px/1.4 system-ui;color:#164d39;';document.addEventListener('DOMContentLoaded',()=>document.body.appendChild(status));
 const label=text=>{status.textContent=text;};
 function showConflict(message){
  label(message);status.style.maxWidth='min(420px,calc(100vw - 24px))';
  const note=document.createElement('p');note.textContent='No se mezclan avances automáticamente. Podés descargar una copia local y cargar la versión guardada en el servidor.';note.style.margin='8px 0';status.appendChild(note);
  const download=document.createElement('button');download.type='button';download.textContent='Descargar copia local';
  const remote=document.createElement('button');remote.type='button';remote.textContent='Cargar versión del servidor';
  for(const button of [download,remote]){button.style.cssText='min-height:44px;margin:4px;padding:8px 12px;border:2px solid #1a1a1a;border-radius:10px;background:#edf7f1;color:#164d39;font:600 12px system-ui;box-shadow:2px 2px #1a1a1a;cursor:pointer';status.appendChild(button);}
  download.addEventListener('click',()=>{
   const raw=localStorage.getItem(window.__TEKO_ARCADE_STORAGE_KEY__);const url=URL.createObjectURL(new Blob([raw||'{}'],{type:'application/json'}));const link=document.createElement('a');link.href=url;link.download='teko-arcade-copia-local.json';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);
  });
  remote.addEventListener('click',async()=>{
   remote.disabled=true;
   try{
    const response=await fetch(endpoint,{cache:'no-store'});if(!response.ok)throw Error('No se pudo recuperar la cuenta. Iniciá sesión nuevamente.');const latest=await response.json();
    if(latest.user_id!==userId)throw Error('La cuenta cambió. Cerrá esta pestaña y abrí Arcade desde tu cuenta.');
    const key=window.__TEKO_ARCADE_STORAGE_KEY__,local=localStorage.getItem(key);
    if(local!==null)localStorage.setItem(key+':backup:'+Date.now(),local);
    if(latest.snapshot===null)localStorage.removeItem(key);else localStorage.setItem(key,JSON.stringify(latest.snapshot));
    localStorage.setItem(key+':revision',String(latest.revision));localStorage.setItem(key+':pending','false');queue=null;location.reload();
   }catch(error){showConflict(error.message);}
  });
 }
 async function flush(){
  if(busy||blocked||queue===null)return;busy=true;const value=queue;queue=null;label('Guardando progreso…');
  try{const r=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json','X-Teko-CSRF':token},body:JSON.stringify({user_id:userId,revision,snapshot:JSON.parse(value)})});const data=await r.json();if(!r.ok){if(r.status===409||r.status===401||r.status===403)blocked=true;throw Error(data.error||'No se pudo sincronizar.');}revision=data.revision;localStorage.setItem(window.__TEKO_ARCADE_STORAGE_KEY__+':revision',String(revision));localStorage.setItem(window.__TEKO_ARCADE_STORAGE_KEY__+':pending',queue===null?'false':'true');label(queue===null?'Progreso guardado':'Guardando cambios pendientes…');}
  catch(e){if(queue===null)queue=value;if(blocked)showConflict(e.message);else label('Sin sincronizar · avance guardado en este navegador');}
  finally{busy=false;if(queue!==null&&!blocked&&navigator.onLine)setTimeout(flush,3000);}
 }
 window.__TEKO_ARCADE_SAVE__=value=>{if(!userId)return;localStorage.setItem(window.__TEKO_ARCADE_STORAGE_KEY__+':pending','true');queue=value;flush();};
 window.__TEKO_ARCADE_BOOT__=(async()=>{
  const r=await fetch(endpoint,{cache:'no-store'});if(!r.ok)throw Error('Iniciá sesión para acceder a tu progreso de Arcade.');const data=await r.json();userId=data.user_id;token=data.token;revision=data.revision;
  const key='teko-juegos-progress-v2:user:'+userId;window.__TEKO_ARCADE_STORAGE_KEY__=key;
  if(localStorage.getItem(key+':pending')==='true'){
   if(Number(localStorage.getItem(key+':revision'))===revision){queue=localStorage.getItem(key);flush();}else{blocked=true;showConflict('Hay dos versiones de tu progreso. Se conserva el avance local.');}
  }else{if(data.snapshot!==null)localStorage.setItem(key,JSON.stringify(data.snapshot));else localStorage.removeItem(key);localStorage.setItem(key+':revision',String(revision));label('Progreso de tu cuenta');}
 })();
 window.addEventListener('online',flush);
})();
