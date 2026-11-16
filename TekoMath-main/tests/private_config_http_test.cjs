const {spawnSync}=require('node:child_process');const assert=require('node:assert/strict');
const php='C:/xampp1/php/php.exe';
(async()=>{
 const r=spawnSync(php,['-r','require "01_sistema_principal_y_registro_offline/config/database.php";require "02_tekobot/TekoBot/Hackaton/config/database.php";foreach([[getDB(),"kyhyjey_db.students"],[Database::getConnection(),"teko_math.usuarios"]] as [$db,$other]){if(str_starts_with($db->query("SELECT CURRENT_USER()")->fetchColumn(),"root@"))throw new Exception("Root runtime");try{$db->query("SELECT * FROM ".$other." LIMIT 0");throw new Exception("Cross-database access");}catch(PDOException $e){if(!in_array($e->errorInfo[1]??0,[1044,1142],true))throw $e;}}echo "SCOPED";'],{encoding:'utf8'});
 assert.equal(r.status,0,r.stderr);assert.equal(r.stdout,'SCOPED');
 for(const path of ['config/runtime.php','config/runtime.example.php','scripts/setup_local_private.php'])assert.equal((await fetch('http://localhost/TekoMath-main/'+path)).status,403,path);
 assert.equal((await fetch('http://localhost/teko-private/teko-math.php')).status,404,'Private file is outside htdocs');
 console.log('PASS real runtime accounts are not root, cannot read the other database, and configuration/provisioning scripts are not downloadable.');
})().catch(e=>{console.error(e);process.exitCode=1});
