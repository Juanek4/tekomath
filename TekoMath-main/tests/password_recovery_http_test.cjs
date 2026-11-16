const {request,chromium}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');const {spawnSync}=require('node:child_process');
const base='http://localhost/TekoMath-main/01_sistema_principal_y_registro_offline/';
const emails=[0,1].map(i=>`recovery_${Date.now()}_${i}@test.invalid`);const contexts=[];const issuedTokens=[];const password='RecoveryOld-1938!',next='RecoveryNew-9384!';let browser;
function field(html,name){const input=html.match(new RegExp(`<input[^>]*name="${name}"[^>]*>`));assert.ok(input,name);return input[0].match(/value="([^"]*)"/)[1];}
async function ctx(){const c=await request.newContext({baseURL:base});contexts.push(c);return c;}
function sql(code,data){const r=spawnSync('C:/xampp1/php/php.exe',['-r','require "01_sistema_principal_y_registro_offline/config/database.php";require "01_sistema_principal_y_registro_offline/config/password_recovery.php";$db=getDB();PasswordRecovery::ensure($db);$v=json_decode(stream_get_contents(STDIN),true);'+code],{input:JSON.stringify(data),encoding:'utf8'});assert.equal(r.status,0,r.stderr);return r.stdout;}
(async()=>{try{
 const admin=await ctx();let html=await(await admin.get('admin.php')).text();let response=await admin.post('admin.php',{form:{csrf:field(html,'csrf'),action:'login',username:'tekoadmin',password:process.env.TEKO_ADMIN_TEST_PASSWORD},maxRedirects:0});assert.equal(response.status(),302);
 const adminPage=await(await admin.get('admin.php')).text(),csrf=field(adminPage,'csrf');
 const students=[];
 for(const email of emails){const c=await ctx();html=await(await c.get('registro.php')).text();response=await c.post('registro_process.php',{form:{csrf_token:field(html,'csrf_token'),nombre:'Recovery test',email,password,password_confirm:password},maxRedirects:0});assert.equal(response.headers().location,'dashboard.php');students.push(c);}
 // A teacher fixture tests role preservation independently from the already-covered invitation flow.
 sql('$db->prepare("UPDATE usuarios SET rol=\'docente\' WHERE email=?")->execute([$v]);',emails[1]);
 async function issue(email){const response=await admin.post('admin.php',{form:{csrf,action:'reset_account',identity_checked:'yes',reset_email:email}});const page=await response.text();const path=page.match(/id="recovery-link"[^>]*data-path="([^"]+)"/);assert.ok(path,'Admin recovery link');issuedTokens.push(path[1].split('token=')[1]);return path[1];}
 const anonymous=await ctx();html=await(await anonymous.get('admin.php')).text();response=await anonymous.post('admin.php',{form:{csrf:field(html,'csrf'),action:'reset_account',identity_checked:'yes',reset_email:emails[0]}});assert.ok(!(await response.text()).includes('id="recovery-link"'));
 response=await admin.post('admin.php',{form:{csrf:'forged',action:'reset_account',identity_checked:'yes',reset_email:emails[0]}});assert.equal(response.status(),403);
 response=await admin.post('admin.php',{form:{csrf,action:'reset_account',reset_email:emails[0]}});assert.ok(!(await response.text()).includes('id="recovery-link"'));
 const previous=await issue(emails[0]),path=await issue(emails[0]);
 let recovery=await ctx();html=await(await recovery.get(previous)).text();assert.ok(!html.includes('id="password"'),'Replaced link rejected');
 response=await recovery.get(path,{maxRedirects:0});assert.equal(response.status(),303);assert.equal(response.headers().location,'recuperar.php');html=await(await recovery.get('recuperar.php')).text();assert.ok(html.includes('id="password"'));assert.ok(!html.includes(path.split('token=')[1]),'Token absent from form');
 response=await recovery.post('recuperar.php',{form:{csrf:'forged',password:next,confirm:next}});assert.equal(response.status(),403);
 response=await recovery.post('recuperar.php',{form:{csrf:field(html,'csrf'),password:'short',confirm:'short'}});assert.ok(!(await response.text()).includes('Tu contraseña fue actualizada'));
 response=await recovery.post('recuperar.php',{form:{csrf:field(html,'csrf'),password:next,confirm:next}});assert.ok((await response.text()).includes('Tu contraseña fue actualizada'));
 response=await students[0].get('dashboard.php',{maxRedirects:0});assert.equal(response.status(),302,'Old session revoked');
 const login=await ctx();response=await login.post('auth.php',{form:{email:emails[0],password,rol:'estudiante'},maxRedirects:0});assert.equal(response.headers().location,'index.php','Old password rejected');
 response=await login.post('auth.php',{form:{email:emails[0],password:next,rol:'estudiante'},maxRedirects:0});assert.equal(response.headers().location,'dashboard.php','New password accepted');
 html=await(await (await ctx()).get(path)).text();assert.ok(!html.includes('id="password"'),'Used link rejected');
 const expired=await issue(emails[0]);sql('$db->prepare("UPDATE password_reset_links SET expires_at=? WHERE token_hash=?")->execute([time()-1,hash("sha256",$v)]);',expired.split('token=')[1]);html=await(await (await ctx()).get(expired)).text();assert.ok(!html.includes('id="password"'),'Expired link rejected');
 const teacherLink=await issue(emails[1]);const teacher=await ctx();html=await(await teacher.get(teacherLink)).text();response=await teacher.post('recuperar.php',{form:{csrf:field(html,'csrf'),password:next,confirm:next}});assert.ok((await response.text()).includes('Tu contraseña fue actualizada'));
 const teacherLogin=await ctx();response=await teacherLogin.post('auth.php',{form:{email:emails[1],password:next,rol:'docente'},maxRedirects:0});assert.equal(response.headers().location,'docente.php','Teacher role preserved');
 const simultaneous=await issue(emails[0]);const contenders=await Promise.all([ctx(),ctx()]);
 const forms=await Promise.all(contenders.map(async c=>(await c.get(simultaneous)).text()));
 const outcomes=await Promise.all(contenders.map((c,i)=>c.post('recuperar.php',{form:{csrf:field(forms[i],'csrf'),password:'ConcurrentReset-1938!',confirm:'ConcurrentReset-1938!'}}).then(r=>r.text())));
 assert.equal(outcomes.filter(html=>html.includes('Tu contraseña fue actualizada')).length,1,'Only one concurrent redemption succeeds');
 const disabled=await issue(emails[0]);sql('$q=$db->prepare("SELECT id FROM usuarios WHERE email=?");$q->execute([$v]);$db->prepare("INSERT INTO account_security(user_id,disabled) VALUES(?,1) ON DUPLICATE KEY UPDATE disabled=1")->execute([$q->fetchColumn()]);',emails[0]);html=await(await (await ctx()).get(disabled)).text();assert.ok(!html.includes('id="password"'),'Disabled account cannot reset');
 const stored=JSON.parse(sql('$q=$db->prepare("SELECT r.token_hash FROM password_reset_links r JOIN usuarios u ON u.id=r.user_id WHERE u.email=?");$q->execute([$v]);echo json_encode($q->fetchAll(PDO::FETCH_COLUMN));',emails[0]));assert.ok(stored.every(hash=>hash.length===64 && hash!==disabled.split('token=')[1]));
 console.log('PASS private admin issuance, CSRF, replaced/expired/used links, hashed tokens, password update, old-session revocation, teacher role and disabled-account denial.');
 // Browser QA of the real public pages with existing TEKO styles.
 browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
 const context=await browser.newContext();const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const visualLink=await issue(emails[1]);
 for(const width of [390,1365]){
  await page.setViewportSize({width,height:844});await page.goto(base+'index.php');await page.getByRole('link',{name:'¿Olvidaste tu contraseña?'}).click();await page.getByRole('heading',{name:'Recuperar contraseña'}).waitFor();
  await page.goto(base+visualLink);assert.ok(await page.locator('#password').isVisible());
  assert.equal(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1),false,'No horizontal overflow '+width);
  require('node:fs').mkdirSync('docs/qa-recovery',{recursive:true});await page.screenshot({path:`docs/qa-recovery/recovery-${width}.png`,fullPage:true});
 }
 assert.deepEqual(errors,[]);console.log('PASS recovery navigation and form layout at 390 and 1365px.');
}finally{
 if(browser)await browser.close();for(const c of contexts)await c.dispose();
 sql('foreach($v as $email){$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([$email]);}$db->prepare("DELETE FROM auth_failures WHERE bucket=?")->execute([AccountSecurity::bucket("login",$v[0])]);',emails);
 const crypto=require('node:crypto');const buckets=[];
 for(const ip of ['127.0.0.1','::1']){
  for(const email of emails)buckets.push(crypto.createHash('sha256').update('login|'+email+'|'+ip).digest('hex'));
  for(const token of issuedTokens)buckets.push(crypto.createHash('sha256').update('password_recovery|'+token+'|'+ip).digest('hex'));
 }
 sql('foreach($v as $bucket)$db->prepare("DELETE FROM auth_failures WHERE bucket=?")->execute([$bucket]);',buckets);
 console.log('Temporary recovery accounts removed.');
}})().catch(e=>{console.error(e);process.exitCode=1});
