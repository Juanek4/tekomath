const {chromium,request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const {spawnSync}=require('node:child_process');const assert=require('node:assert/strict');
(async()=>{const base='http://localhost/TekoMath-main/';const email='assessmentflow_'+Date.now()+'@test.invalid';const api=await request.newContext({baseURL:base+'01_sistema_principal_y_registro_offline/'});let browser;
try{
 const html=await(await api.get('registro.php')).text();await api.post('registro_process.php',{form:{csrf_token:html.match(/name="csrf_token" value="([^"]+)"/)[1],nombre:'Prueba foto',email,password:'PhotoFlow-123!',password_confirm:'PhotoFlow-123!'},maxRedirects:0});
 browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});const context=await browser.newContext({storageState:await api.storageState(),viewport:{width:1365,height:900}});await context.addInitScript(()=>{localStorage.setItem('teko-language','es');localStorage.setItem('teko_math_lang','es');});const page=await context.newPage();await page.route('**/*',r=>new URL(r.request().url()).hostname==='localhost'?r.continue():r.abort());
 const bank=require('../02_tekobot/TekoBot/Hackaton/config/assessment_bank.json').questions;
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 for(const [entry,width] of [['index.php',1365],['index.html',390]]) {
  await page.setViewportSize({width,height:844});
  await page.goto(base+'02_tekobot/TekoBot/Hackaton/'+entry,{waitUntil:'networkidle'});
  await page.locator('#btn-toggle-sidebar').click();await page.locator('#btn-open-diagnostic').click();
  await page.locator('.quiz-question-card').nth(5).waitFor();
  assert.equal(await page.locator('.quiz-question-card').count(),6);
  assert.equal(await page.locator('#diagnostic-unit option').count(),4);
  if(width===1365){
   const catalog=require('../02_tekobot/TekoBot/Hackaton/database/curriculum.json').units;
   for(const unit of catalog){
    await page.locator('#diagnostic-unit').selectOption(unit.key);
    await page.waitForFunction(()=>!document.querySelector('#diagnostic-subtopic').disabled);
    for(const sub of unit.subtopics){
     await page.locator('#diagnostic-subtopic').selectOption(sub.id);
     await page.waitForFunction(()=>!document.querySelector('#btn-submit-quiz').disabled);
     assert.equal(await page.locator('.quiz-question-card').count(),6);
     const question=bank.find(q=>q.unit===unit.key&&q.subtopic===sub.id);
     assert.ok((await page.locator('#quiz-questions-list').innerText()).includes(question.question));
    }
   }
   console.log('PASS browser selection of all 62 subtopics, six questions each.');
  }
  await page.locator('#diagnostic-unit').selectOption('combinatoria');
  await page.waitForFunction(()=>!document.querySelector('#diagnostic-subtopic').disabled);
  await page.locator('#diagnostic-subtopic').selectOption('combinacion');
  await page.waitForFunction(()=>!document.querySelector('#btn-submit-quiz').disabled);
  for(const q of bank.filter(q=>q.unit==='combinatoria'&&q.subtopic==='combinacion')){
   await page.locator(`input[name="q_${q.id}"][value="${q.correct_index}"]`).check();
  }
  assert.ok(await page.locator('#diagnostic-results').isHidden());
  await page.locator('#btn-submit-quiz').click();
  await page.locator('#diagnostic-results').waitFor();
  assert.match(await page.locator('#diag-score-text').innerText(),/6 de 6.*100/);
  const overflow=await page.locator('#diagnostic-modal .modal-content').evaluate(el=>el.scrollWidth>el.clientWidth+1);
  assert.equal(overflow,false,'No modal horizontal overflow');
  await page.locator('#btn-close-diagnostic').click();
  assert.ok(await page.locator('#diagnostic-modal').isHidden());
  console.log('PASS diagnostic real save and layout '+width+'px '+entry);
 }
 assert.deepEqual(errors,[]);
}finally{if(browser)await browser.close();await api.dispose();const code='require "01_sistema_principal_y_registro_offline/config/database.php";require "02_tekobot/TekoBot/Hackaton/config/database.php";$db=getDB();$t=Database::getConnection();$email=trim(stream_get_contents(STDIN));$q=$db->prepare("SELECT id FROM usuarios WHERE email=?");$q->execute([$email]);$id=$q->fetchColumn();if($id){$q=$t->prepare("SELECT tutor_student_id FROM portal_student_links WHERE portal_user_id=?");$q->execute([$id]);$tid=$q->fetchColumn();if($tid){$q=$t->prepare("SELECT id FROM learning_sessions WHERE student_id=?");$q->execute([$tid]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $sid)$t->prepare("DELETE FROM tutor_session_states WHERE session_id=?")->execute([$sid]);$t->prepare("DELETE FROM learning_sessions WHERE student_id=?")->execute([$tid]);$t->prepare("DELETE FROM portal_student_links WHERE portal_user_id=?")->execute([$id]);$t->prepare("DELETE FROM diagnostic_tests WHERE student_id=?")->execute([$tid]);$t->prepare("DELETE FROM students WHERE id=?")->execute([$tid]);}$db->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);}';const clean=spawnSync('C:/xampp1/php/php.exe',['-r',code],{input:email,encoding:'utf8'});if(clean.status!==0)throw Error(clean.stderr);}
})().catch(e=>{console.error(e);process.exitCode=1});
