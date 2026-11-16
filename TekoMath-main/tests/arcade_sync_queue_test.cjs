const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
(async()=>{
 const storage=new Map(),posts=[],timers=[];const status={style:{},setAttribute(){},textContent:''};
 const window={addEventListener(){}};
 const sandbox={window,URL,JSON,Promise,Error,navigator:{onLine:true},localStorage:{getItem:k=>storage.get(k)??null,setItem:(k,v)=>storage.set(k,v),removeItem:k=>storage.delete(k)},document:{currentScript:{src:'http://localhost/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/account-progress.js'},createElement:()=>status,addEventListener(){},body:{appendChild(){}}},setTimeout:fn=>timers.push(fn),fetch:async(url,init)=>{if(!init||init.method!=='POST')return {ok:true,json:async()=>({user_id:7,token:'test',revision:0,snapshot:null})};return new Promise(resolve=>posts.push({init,resolve}));}};
 vm.runInNewContext(fs.readFileSync('03_teko_arcade/TEKOARCADE/JUEGOS/public/account-progress.js','utf8'),sandbox);
 await window.__TEKO_ARCADE_BOOT__;
 window.__TEKO_ARCADE_SAVE__('{"xp":1}');window.__TEKO_ARCADE_SAVE__('{"xp":2}');
 posts[0].resolve({ok:true,json:async()=>({revision:1})});await new Promise(setImmediate);
 assert.equal(status.textContent,'Guardando cambios pendientes…');assert.equal(storage.get(window.__TEKO_ARCADE_STORAGE_KEY__+':pending'),'true');
 timers.shift()();assert.equal(JSON.parse(posts[1].init.body).snapshot.xp,2);
 posts[1].resolve({ok:true,json:async()=>({revision:2})});await new Promise(setImmediate);
 assert.equal(status.textContent,'Progreso guardado');assert.equal(storage.get(window.__TEKO_ARCADE_STORAGE_KEY__+':pending'),'false');
 console.log('PASS: queued Arcade writes never announce saved before the latest snapshot is acknowledged.');
})().catch(e=>{console.error(e);process.exitCode=1});
