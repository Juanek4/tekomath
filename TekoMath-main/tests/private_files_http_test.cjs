const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const assert=require('node:assert/strict');
(async()=>{const c=await request.newContext({baseURL:'http://localhost/TekoMath-main/'});try{
 const denied=['02_tekobot/TekoBot/Hackaton/config/assessment_bank.json','.gitignore','.htaccess','README.md','DOCUMENTACION_TECNICA.txt','Teko.pptx','docs/Informe_cambios_TEKO_Math_2026-10-28.docx','tests/pilot_accounts_http_test.cjs','01_sistema_principal_y_registro_offline/config/database.php','01_sistema_principal_y_registro_offline/sql/setup.sql','02_tekobot/TekoBot/Hackaton/.env.example','02_tekobot/TekoBot/Hackaton/database/curriculum.json','02_tekobot/TekoBot/Hackaton/services/image_service.php','03_teko_arcade/TEKOARCADE/JUEGOS/src/main.tsx','03_teko_arcade/TEKOARCADE/JUEGOS/node_modules/react/package.json'];
 for(const path of denied){const r=await c.get(path,{maxRedirects:0});assert.equal(r.status(),403,path+' should be private');}
 const publicFiles=['01_sistema_principal_y_registro_offline/index.php','01_sistema_principal_y_registro_offline/admin.php','07_diseno_y_graficos/style.css','07_diseno_y_graficos/teko-ui.css','02_tekobot/TekoBot/Hackaton/assets/js/curriculum.js','03_teko_arcade/TEKOARCADE/JUEGOS/dist/index.html','03_teko_arcade/TEKOARCADE/JUEGOS/dist/shared/image-recognition.js','03_teko_arcade/TEKOARCADE/JUEGOS/dist/tesseract/worker.min.js','03_teko_arcade/TEKOARCADE/JUEGOS/dist/tesseract/lang/spa.traineddata.gz'];
 for(const path of publicFiles){const r=await c.get(path,{maxRedirects:0});assert.equal(r.status(),200,path+' should remain accessible');}
 assert.equal((await c.get('07_diseno_y_graficos/')).status(),403,'Directory listing disabled');
 console.log('PASS: '+denied.length+' private paths denied, '+publicFiles.length+' public resources accessible, directory listing disabled.');
}finally{await c.dispose();}})().catch(e=>{console.error(e);process.exitCode=1});

