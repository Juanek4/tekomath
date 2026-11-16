const {spawnSync,spawn}=require('node:child_process');const assert=require('node:assert/strict');const crypto=require('node:crypto');const fs=require('node:fs');
const php='C:/xampp1/php/php.exe';const fixture=[{user:'teko_probe_m_'+crypto.randomBytes(3).toString('hex'),password:crypto.randomBytes(24).toString('hex'),db:'teko_math'},{user:'teko_probe_t_'+crypto.randomBytes(3).toString('hex'),password:crypto.randomBytes(24).toString('hex'),db:'kyhyjey_db'}];
function run(code,env={}){return spawnSync(php,['-r',code],{env:{...process.env,...env},encoding:'utf8'});}
function accountSQL(cleanup=false){const code='$db=new PDO("mysql:host=127.0.0.1;charset=utf8mb4","root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);foreach(json_decode(stream_get_contents(STDIN),true) as $v){$account=$db->quote($v["user"])."@\'127.0.0.1\'";'+(cleanup?'$db->exec("DROP USER IF EXISTS ".$account);':'$db->exec("CREATE USER ".$account." IDENTIFIED BY ".$db->quote($v["password"]));$db->exec("GRANT SELECT,INSERT,UPDATE,DELETE ON `".$v["db"]."`.* TO ".$account);')+'}';const r=spawnSync(php,['-r',code],{input:JSON.stringify(fixture),encoding:'utf8'});assert.equal(r.status,0,r.stderr);}
const flagFile=require('node:path').resolve('tests/.maintenance-probe-'+crypto.randomBytes(5).toString('hex')+'.flag');
const production={TEKO_ENV:'production',TEKO_PUBLIC_URL:'https://teko.example/piloto/',TEKO_MAIN_DB_USER:fixture[0].user,TEKO_MAIN_DB_PASSWORD:fixture[0].password,TEKO_TUTOR_DB_USER:fixture[1].user,TEKO_TUTOR_DB_PASSWORD:fixture[1].password};
(async()=>{let server;try{
 accountSQL();
 const probe='require "01_sistema_principal_y_registro_offline/config/database.php";require "02_tekobot/TekoBot/Hackaton/config/database.php";require "01_sistema_principal_y_registro_offline/config/password_recovery.php";require "01_sistema_principal_y_registro_offline/config/admin_invitations.php";require "01_sistema_principal_y_registro_offline/config/mission_attempts.php";$main=getDB();$tutor=Database::getConnection();if(!teko_production()||teko_schema_setup()||ini_get("display_errors")!="0")throw new Exception("Production flags");PasswordRecovery::ensure($main);AdminInvitations::ensure($main);missionAttemptTable($main);foreach([$main,$tutor] as $db){$db->query("SELECT 1");try{$db->exec("CREATE TABLE teko_forbidden_probe(id INT)");throw new Exception("DDL unexpectedly allowed");}catch(PDOException $e){if(($e->errorInfo[1]??0)!==1142)throw $e;}}$credentials=AdminInvitations::credentials($main);if((int)$credentials["initial_password"]!==0)throw new Exception("Console credential must have been rotated");echo "PASS";';
 let r=run(probe,production);assert.equal(r.status,0,r.stderr);assert.equal(r.stdout,'PASS');
 const validate='try{require "config/runtime.php";echo "ACCEPTED";}catch(Throwable $e){echo "REJECTED";}';
 for(const invalid of [{TEKO_MAIN_DB_USER:'root'},{TEKO_TUTOR_DB_PASSWORD:''},{TEKO_PUBLIC_URL:'http://teko.example/'},{TEKO_CONFIG_FILE:process.cwd()+'/config/runtime.example.php'}]){
  r=run(validate,{...production,...invalid});assert.equal(r.stdout,'REJECTED');
 }
 const sqlite='02_tekobot/TekoBot/Hackaton/database/kyhyjey.sqlite';const before=fs.existsSync(sqlite)?fs.statSync(sqlite).mtimeMs:null;
 r=run('require "02_tekobot/TekoBot/Hackaton/config/database.php";try{Database::getConnection();echo "UNEXPECTED";}catch(RuntimeException $e){echo "NO_FALLBACK";}',{...production,TEKO_TUTOR_DB_PORT:'1'});assert.equal(r.stdout,'NO_FALLBACK');assert.equal(fs.existsSync(sqlite)?fs.statSync(sqlite).mtimeMs:null,before);
 server=spawn(php,['-S','127.0.0.1:18197','-t',process.cwd()],{env:{...process.env,...production,TEKO_MAINTENANCE_FILE:flagFile},stdio:'ignore'});
 let response;for(let i=0;i<40;i++){try{response=await fetch('http://127.0.0.1:18197/01_sistema_principal_y_registro_offline/index.php');break;}catch{await new Promise(resolve=>setTimeout(resolve,100));}}
 assert.ok(response);assert.equal(response.status,503);assert.ok((await response.text()).includes('acceso seguro'));
 fs.writeFileSync(flagFile,'isolated maintenance probe');
 const maintenance=await fetch('http://127.0.0.1:18197/01_sistema_principal_y_registro_offline/index.php');
 assert.equal(maintenance.status,503);assert.equal(maintenance.headers.get('retry-after'),'60');assert.ok((await maintenance.json()).error.includes('mantenimiento'));
 fs.unlinkSync(flagFile);
 console.log('PASS production with DML-only SQL users, DDL denied, debug hidden, root/empty password/HTTP URL/public config rejected, no SQLite fallback, chosen admin credential accepted and insecure HTTP refused and maintenance flag returns controlled 503.');
}finally{if(server)server.kill();if(fs.existsSync(flagFile))fs.unlinkSync(flagFile);accountSQL(true);}})().catch(e=>{console.error(e);process.exitCode=1});
