const {chromium,request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const {spawnSync}=require('node:child_process');const assert=require('node:assert/strict');
const root='http://localhost/TekoMath-main/';const main='01_sistema_principal_y_registro_offline/';const tutor='02_tekobot/TekoBot/Hackaton/';
const emails=[0,1].map(i=>`cacheguard_${Date.now()}_${i}@test.invalid`);const clients=[];const identities=[];let browser;
(async()=>{try{
 for(let i=0;i<2;i++){
  const client=await request.newContext({baseURL:root});clients.push(client);
  const html=await(await client.get(main+'registro.php')).text();
  const response=await client.post(main+'registro_process.php',{form:{csrf_token:html.match(/name="csrf_token" value="([^"]+)"/)[1],nombre:'Cache test',email:emails[i],password:'CacheTest-1938!',password_confirm:'CacheTest-1938!'},maxRedirects:0});
  assert.equal(response.headers().location,'dashboard.php');identities.push(await(await client.get(tutor+'api/security.php')).json());
 }
 browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 const context=await browser.newContext({storageState:await clients[0].storageState(),viewport:{width:1365,height:900}});
 const page=await context.newPage();await page.route('**/*',route=>new URL(route.request().url()).hostname==='localhost'?route.continue():route.abort());
 await page.goto(root+tutor+'index.php',{waitUntil:'networkidle'});
 await page.evaluate(ids=>{
  const session=(i)=>({session_uuid:'local_private_'+i,topic:'funciones',topic_label:'Funciones',language:'es',interactions:1,preview:'PRIVATE_ACCOUNT_'+i+' <img src=x onerror=alert(1)>',date_formatted:'30/10'});
  ids.forEach((id,i)=>localStorage.setItem(`teko-tutor:user:${id}:kyhyjey_sessions_store`,JSON.stringify([session(i)])));
  localStorage.setItem('kyhyjey_sessions_store',JSON.stringify([{...session(9),preview:'LEGACY_UNOWNED_SECRET'}]));
 },identities.map(x=>x.user_id));
 for(let i=0;i<2;i++){
  if(i)await context.addCookies((await clients[i].storageState()).cookies);
  await page.reload({waitUntil:'networkidle'});await page.locator('#btn-toggle-chats').click();
  await page.waitForFunction(()=>document.querySelector('#chats-list-container')?.textContent.includes('PRIVATE_ACCOUNT_'));
  const list=await page.locator('#chats-list-container').innerText();
  assert.equal(await page.locator('#chats-list-container img').count(),0,'Untrusted preview is rendered as text');
  assert.ok(list.includes('<img src=x onerror=alert(1)>'));
  assert.ok(list.includes('PRIVATE_ACCOUNT_'+i));assert.ok(!list.includes('PRIVATE_ACCOUNT_'+(1-i)));assert.ok(!list.includes('LEGACY_UNOWNED_SECRET'));
 }
 // A rejected write must report the rejection and must not become a fake local response.
 await page.locator('#btn-close-drawer').click();await page.locator('#btn-toggle-sidebar').click();
 if(!await page.locator('.curriculum-unit').first().evaluate(el=>el.open))await page.locator('.curriculum-unit').first().locator('summary').click();
 await page.route('**/api/sessions.php',async route=>route.request().method()==='POST'?route.fulfill({status:403,contentType:'application/json',body:JSON.stringify({success:false,error:'SECURITY_REJECTION_TEST'})}):route.continue());
 await page.locator('#app-sidebar .curriculum-subtopic').first().click();
 await page.getByText('SECURITY_REJECTION_TEST',{exact:true}).waitFor();
 const localFlags=await page.evaluate(id=>Object.keys(localStorage).filter(key=>key.startsWith(`teko-tutor:user:${id}:kyhyjey_local_`)),identities[1].user_id);
 assert.equal(localFlags.length,0);
 console.log('PASS same browser switching accounts isolates caches, legacy cache ignored, rejected creation shown without offline fallback.');
}finally{
 if(browser)await browser.close();for(const client of clients)await client.dispose();
 const php='require "01_sistema_principal_y_registro_offline/config/database.php";require "02_tekobot/TekoBot/Hackaton/config/database.php";$db=getDB();$t=Database::getConnection();foreach(json_decode(stream_get_contents(STDIN),true) as $email){$q=$db->prepare("SELECT id FROM usuarios WHERE email=?");$q->execute([$email]);$id=$q->fetchColumn();if($id){$q=$t->prepare("SELECT tutor_student_id FROM portal_student_links WHERE portal_user_id=?");$q->execute([$id]);$tid=$q->fetchColumn();if($tid){$t->prepare("DELETE FROM portal_student_links WHERE portal_user_id=?")->execute([$id]);$t->prepare("DELETE FROM students WHERE id=?")->execute([$tid]);}$db->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);}}';
 const clean=spawnSync('C:/xampp1/php/php.exe',['-r',php],{input:JSON.stringify(emails),encoding:'utf8'});assert.equal(clean.status,0,clean.stderr);
}})().catch(error=>{console.error(error);process.exitCode=1});
