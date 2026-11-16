const fs=require('node:fs');const path=require('node:path');const crypto=require('node:crypto');
const {spawnSync}=require('node:child_process');const assert=require('node:assert/strict');
const source=path.resolve(process.argv[2]||'');const parent=path.dirname(source);
const manifest=JSON.parse(fs.readFileSync(path.join(source,'manifest.json'),'utf8'));
const directory=path.join(parent,'validation-probe-'+crypto.randomBytes(5).toString('hex'));
fs.mkdirSync(directory);
try{
 fs.writeFileSync(path.join(directory,'manifest.json'),JSON.stringify(manifest));
 fs.writeFileSync(path.join(directory,'main.sql'),'INTENTIONALLY ALTERED TEST FILE');
 const result=spawnSync('C:/xampp1/php/php.exe',['scripts/verify_restoration.php',directory],{encoding:'utf8'});
 assert.notEqual(result.status,0);
 assert.ok((result.stdout+result.stderr).includes('El respaldo SQL cambió'),'Reject altered SQL before creating temporary databases');
 console.log('PASS altered backup rejected before restoration; real backup unchanged.');
}finally{
 fs.unlinkSync(path.join(directory,'main.sql'));fs.unlinkSync(path.join(directory,'manifest.json'));fs.rmdirSync(directory);
}
