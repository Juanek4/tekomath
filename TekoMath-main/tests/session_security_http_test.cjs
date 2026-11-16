const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const {spawnSync}=require('node:child_process');const assert=require('node:assert/strict');
const php=(code,data)=>{const r=spawnSync('C:/xampp1/php/php.exe',['-r',code],{input:JSON.stringify(data),encoding:'utf8'});if(r.status!==0)throw Error(r.stderr||r.stdout);return r.stdout;};
(async()=>{const base='http://localhost/TekoMath-main/01_sistema_principal_y_registro_offline/';const email='sessions_'+Date.now()+'@test.invalid';const c=await request.newContext({baseURL:base});
try{
 const html=await(await c.get('registro.php')).text();await c.post('registro_process.php',{form:{csrf_token:html.match(/name="csrf_token" value="([^"]+)"/)[1],nombre:'Prueba sesión',email,password:'SessionTest-123!',password_confirm:'SessionTest-123!'},maxRedirects:0});
 let state=await c.storageState();const cookie=state.cookies.find(x=>x.name==='PHPSESSID');assert.ok(cookie.httpOnly);assert.equal(cookie.sameSite,'Lax');
 const mutate='require "01_sistema_principal_y_registro_offline/config/database.php";$db=getDB();$v=json_decode(stream_get_contents(STDIN),true);$q=$db->prepare("SELECT id FROM usuarios WHERE email=?");$q->execute([$v["email"]]);$id=$q->fetchColumn();$db->prepare("INSERT INTO account_security(user_id,disabled,session_version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE disabled=VALUES(disabled),session_version=session_version+1")->execute([$id,$v["disabled"]]);';
 php(mutate,{email,disabled:1});assert.equal((await c.get('api_arcade_progress.php')).status(),401,'Disabled session denied');php(mutate,{email,disabled:0});
 await c.post('auth.php',{form:{email,password:'SessionTest-123!'},maxRedirects:0});state=await c.storageState();const sid=state.cookies.find(x=>x.name==='PHPSESSID').value;assert.notEqual(sid,cookie.value,'Login rotates session');
 php('$v=json_decode(stream_get_contents(STDIN),true);session_id($v["sid"]);require "01_sistema_principal_y_registro_offline/config/session.php";$_SESSION["session_activity"]=time()-1801;session_write_close();',{sid});
 assert.equal((await c.get('api_arcade_progress.php')).status(),401,'Idle expiry denied');
 await c.post('auth.php',{form:{email,password:'SessionTest-123!'},maxRedirects:0});
 const absoluteSid=(await c.storageState()).cookies.find(x=>x.name==='PHPSESSID').value;
 php('$v=json_decode(stream_get_contents(STDIN),true);session_id($v["sid"]);require "01_sistema_principal_y_registro_offline/config/session.php";$_SESSION["session_started"]=time()-28801;$_SESSION["session_activity"]=time();session_write_close();',{sid:absoluteSid});
 assert.equal((await c.get('api_arcade_progress.php')).status(),401,'Absolute expiry denied');
 await c.post('auth.php',{form:{email,password:'SessionTest-123!'},maxRedirects:0});await c.get('logout.php');assert.equal((await c.get('api_arcade_progress.php')).status(),401,'Logout denied');
 for(let i=0;i<5;i++)await c.post('auth.php',{form:{email,password:'incorrect'},maxRedirects:0});
 const second=await request.newContext({baseURL:base});await second.post('auth.php',{form:{email,password:'SessionTest-123!'},maxRedirects:0});assert.ok((await(await second.get('index.php')).text()).includes('Demasiados intentos'),'Persistent limit across contexts');await second.dispose();
 console.log('PASS cookies, rotation, disabling, idle expiry, logout and persistent login throttling.');
}finally{php('require "01_sistema_principal_y_registro_offline/config/database.php";$db=getDB();$v=json_decode(stream_get_contents(STDIN),true);$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([$v]);foreach(["::1","127.0.0.1"] as $ip)$db->prepare("DELETE FROM auth_failures WHERE bucket=?")->execute([hash("sha256","login|".$v."|".$ip)]);',email);await c.dispose();}
})().catch(e=>{console.error(e);process.exitCode=1});
