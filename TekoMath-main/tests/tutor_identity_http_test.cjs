const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const assert=require('node:assert/strict');const {spawnSync}=require('node:child_process');
const root='http://localhost/TekoMath-main/';const main='01_sistema_principal_y_registro_offline/';const api='02_tekobot/TekoBot/Hackaton/api/';const emails=[0,1].map(i=>`tutorcheck_${Date.now()}_${i}@test.invalid`);const clients=[];const sessions=[];const rawPosts=[];const tokens=[];
(async()=>{try{
 for(let i=0;i<2;i++){
  const c=await request.newContext({baseURL:root});clients.push(c);let html=await(await c.get(main+'registro.php')).text();const csrf=html.match(/name="csrf_token" value="([^"]+)"/)[1];
  let r=await c.post(main+'registro_process.php',{form:{csrf_token:csrf,nombre:'VerificaciÃ³n tutor',email:emails[i],password:'TutorTest-1938!',password_confirm:'TutorTest-1938!'},maxRedirects:0});assert.equal(r.headers().location,'dashboard.php');
  const security=await(await c.get(api+'security.php')).json();assert.ok(security.token);tokens.push(security.token);
  const originalPost=c.post.bind(c);rawPosts.push(originalPost);
  c.post=(url,options={})=>originalPost(url,{...options,headers:{'X-Teko-CSRF':security.token,...options.headers}});
  const uuid='verify_'+Date.now()+'_'+i;r=await c.post(api+'sessions.php',{data:{action:'create',session_uuid:uuid,topic:'funciones',subtopic:'lineal',language:'es',student_id:1}});let data=await r.json();assert.equal(data.success,true,JSON.stringify(data));sessions.push(data.session);
  r=await c.post(api+'chat.php',{data:{session_uuid:uuid,message:'Â¿QuÃ© es funciÃ³n lineal?',language:'es',student_id:1}});data=await r.json();assert.equal(data.success,true,JSON.stringify(data));
  const fromImage=await(await c.post(api+'chat.php',{data:{session_uuid:uuid,message:'3 + 5',action:'image_problem',language:'es'}})).json();assert.equal(fromImage.success,true,JSON.stringify(fromImage));assert.equal(fromImage.tutor_state.workshop.stage,'confirm');assert.equal(fromImage.tutor_state.workshop.statement,'3 + 5','Confirmed OCR arrives in new workshop');
  data=await(await c.get(api+'history.php?session_uuid='+uuid)).json();assert.equal(data.success,true);assert.ok(data.history.length>=3,'Messages persisted');
 }
 const routes=['sessions.php','history.php','chat.php','exercises.php','progress.php','diagnostic.php','security.php'];
 for(const route of routes){
  const method=route==='chat.php'?'GET':'PUT';
  assert.equal((await clients[0].fetch(api+route,{method})).status(),405,route+' wrong method');
 }
 for(const route of ['sessions.php','chat.php','diagnostic.php']){
  assert.equal((await rawPosts[0](api+route,{data:{}})).status(),403,route+' missing CSRF');
  assert.equal((await rawPosts[0](api+route,{data:{},headers:{'X-Teko-CSRF':tokens[1]}})).status(),403,route+' foreign token');
 }
 assert.equal((await clients[0].post(api+'chat.php',{data:'{broken',headers:{'Content-Type':'application/json'}})).status(),400);
 assert.equal((await clients[0].post(api+'chat.php',{data:{message:[],session_uuid:sessions[0].session_uuid}})).status(),422);
 assert.equal((await clients[0].post(api+'chat.php',{data:{message:'x'.repeat(12001),session_uuid:sessions[0].session_uuid}})).status(),422);
 assert.equal((await clients[0].post(api+'chat.php',{data:{message:'x',session_uuid:sessions[0].session_uuid,language:[]}})).status(),422);
 assert.equal((await clients[0].post(api+'chat.php',{data:{message:'x'.repeat(66000),session_uuid:sessions[0].session_uuid}})).status(),413);
 assert.equal((await clients[0].post(api+'sessions.php',{data:[]})).status(),400);
 assert.equal((await clients[0].post(api+'sessions.php',{data:{action:'create',topic:[],subtopic:'lineal'}})).status(),422);
 assert.equal((await clients[0].post(api+'sessions.php',{data:{action:'unexpected'}})).status(),422);
 assert.equal((await clients[0].post(api+'sessions.php',{form:{action:'delete'}})).status(),415);
 const exercisesResponse=await clients[0].get(api+'exercises.php');assert.equal(exercisesResponse.status(),200);
 const exercises=(await exercisesResponse.json()).exercises;assert.ok(exercises.length>0);
 assert.ok(exercises.every(q=>!('expected_value' in q)&&!('hint_level_4' in q)),'Exercise answers hidden');
 assert.equal((await clients[0].get(api+'exercises.php?difficulty[]=1')).status(),422);
 assert.equal((await clients[0].get(api+'history.php?session_id[]=1')).status(),422);
 const ownerBefore=await(await clients[0].get(api+'history.php?session_uuid='+sessions[0].session_uuid)).json();
 assert.equal((await clients[1].post(api+'sessions.php',{data:{action:'delete',session_id:sessions[0].id}})).status(),404);
 assert.equal((await clients[1].post(api+'sessions.php',{data:{action:'create',session_uuid:sessions[0].session_uuid,topic:'funciones',subtopic:'lineal'}})).status(),404);
 assert.equal((await clients[1].post(api+'chat.php',{data:{message:'alterar',session_uuid:sessions[0].session_uuid}})).status(),404);
 assert.equal((await clients[1].get(api+'history.php?session_uuid='+sessions[0].session_uuid)).status(),404);
 const ownerAfter=await(await clients[0].get(api+'history.php?session_uuid='+sessions[0].session_uuid)).json();assert.deepEqual(ownerAfter,ownerBefore,'Denied operations preserve owner state/history');
 console.log('PASS all active route method guards, CSRF, input validation, answer filtering and cross-account create/write/delete/history isolation.');
 const anon=await request.newContext({baseURL:root});
 for(const route of routes)assert.equal((await anon.get(api+route)).status(),401,route+' anonymous');
 assert.equal((await anon.get(api+'progress.php?student_id=1')).status(),401);
 assert.equal((await anon.get(api+'diagnostic.php')).status(),401);await anon.dispose();
 const a=await(await clients[0].get(api+'progress.php?student_id=1')).json();
 const b=await(await clients[1].get(api+'progress.php?student_id='+a.data.student_id)).json();
 assert.notEqual(a.data.student_id,b.data.student_id,'Progress uses session identity');
 const bank=require('../02_tekobot/TekoBot/Hackaton/config/assessment_bank.json').questions;
 const diagnostic=await(await clients[0].get(api+'diagnostic.php')).json();
 assert.equal(diagnostic.questions.length,6);assert.ok(diagnostic.questions.every(q=>!('correct_index' in q)));
 const payload={attempt_token:diagnostic.attempt_token,student_id:b.data.student_id,correct_answers:999,total_questions:1,details:Object.fromEntries(diagnostic.questions.map(q=>[q.id,(bank.find(item=>item.id===q.id).correct_index+1)%4]))};
 assert.equal((await clients[1].post(api+'diagnostic.php',{data:payload})).status(),403,'Token belongs to session');
 assert.equal((await clients[0].post(api+'diagnostic.php',{data:{...payload,details:{1:0}}})).status(),422);
 const graded=await(await clients[0].post(api+'diagnostic.php',{data:payload})).json();
 assert.equal(graded.success,true,JSON.stringify(graded));assert.equal(graded.score,0);assert.equal(graded.total,6);
 assert.equal((await clients[0].post(api+'diagnostic.php',{data:payload})).status(),403,'Replay rejected');
 const ownProgress=await(await clients[0].get(api+'progress.php')).json();
 const otherProgress=await(await clients[1].get(api+'progress.php')).json();
 assert.equal(Number(ownProgress.data.diagnostics[0].score),0);assert.equal(otherProgress.data.diagnostics.length,0);
 const next=await(await clients[0].get(api+'diagnostic.php?unit=combinatoria&subtopic=combinacion')).json();
 assert.equal(next.questions.length,6);assert.ok(next.questions.every(q=>q.unit==='combinatoria'&&q.subtopic==='combinacion'));
 const allCorrect=Object.fromEntries(next.questions.map(q=>[q.id,bank.find(item=>item.id===q.id).correct_index]));
 const full=await(await clients[0].post(api+'diagnostic.php',{data:{attempt_token:next.attempt_token,details:allCorrect}})).json();
 assert.equal(full.score,100);assert.equal(full.correct,6);
 assert.equal((await clients[0].get(api+'diagnostic.php?unit=unknown&subtopic=unknown')).status(),422);
 console.log('PASS per-subtopic selection and six correct answers graded 100%.');
 console.log('PASS diagnostic server grading, session identity, anonymous denial, hidden key, incomplete answers, session token and replay protection.');
 let chatTest=await(await clients[0].post(api+'chat.php',{data:{session_uuid:sessions[0].session_uuid,message:'Comprobar comprensión',action:'assessment',language:'es'}})).json();
 assert.equal(chatTest.success,true);assert.equal(chatTest.tutor_state.assessment.subtopic,'lineal');
 for(const q of bank.filter(q=>q.unit==='funciones'&&q.subtopic==='lineal')) {
  chatTest=await(await clients[0].post(api+'chat.php',{data:{session_uuid:sessions[0].session_uuid,message:String(q.correct_index+1),language:'es'}})).json();
  assert.equal(chatTest.success,true,JSON.stringify(chatTest));
 }
 const result=chatTest.tutor_state.assessment_results.at(-1);
 assert.equal(result.total,6);assert.equal(result.direct_correct,6);assert.equal(result.subtopic,'lineal');
 const restored=await(await clients[0].post(api+'chat.php',{data:{session_uuid:sessions[0].session_uuid,message:'__init__',language:'es'}})).json();
 assert.deepEqual(restored.tutor_state.assessment_results.at(-1),result);
 console.log('PASS six-question chat assessment graded and restored from server with subtopic.');
 const own=sessions[0];let other=await(await clients[1].get(api+'history.php?session_id='+own.id+'&student_id=1')).json();assert.equal(other.success,false,'Other student history denied');
 const list=await(await clients[1].get(api+'sessions.php?student_id=1')).json();assert.ok(!JSON.stringify(list).includes(own.session_uuid),'Session listing isolation');
 const deleted=await clients[1].post(api+'sessions.php',{data:{action:'delete',session_id:sessions[1].id}});assert.equal(deleted.status(),200);
 assert.equal((await clients[1].get(api+'history.php?session_uuid='+sessions[1].session_uuid)).status(),404);
 console.log('PASS owner can delete own conversation.');
 console.log('PASS: two new accounts create chats, persist and retrieve messages; forged student_id ignored; cross-account history and listing isolated.');
}finally{
 const code='require "01_sistema_principal_y_registro_offline/config/database.php";require "02_tekobot/TekoBot/Hackaton/config/database.php";$db=getDB();$t=Database::getConnection();foreach(json_decode(stream_get_contents(STDIN),true) as $email){$q=$db->prepare("SELECT id FROM usuarios WHERE email=?");$q->execute([$email]);$id=$q->fetchColumn();if($id){$q=$t->prepare("SELECT tutor_student_id FROM portal_student_links WHERE portal_user_id=?");$q->execute([$id]);$tid=$q->fetchColumn();if($tid){$q=$t->prepare("SELECT id FROM learning_sessions WHERE student_id=?");$q->execute([$tid]);foreach($q->fetchAll(PDO::FETCH_COLUMN) as $sid){$t->prepare("DELETE FROM tutor_session_states WHERE session_id=?")->execute([$sid]);}$t->prepare("DELETE FROM learning_sessions WHERE student_id=?")->execute([$tid]);$t->prepare("DELETE FROM portal_student_links WHERE portal_user_id=?")->execute([$id]);$t->prepare("DELETE FROM diagnostic_tests WHERE student_id=?")->execute([$tid]);$t->prepare("DELETE FROM students WHERE id=?")->execute([$tid]);}}$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([$email]);}';
 const r=spawnSync('C:/xampp1/php/php.exe',['-r',code],{input:JSON.stringify(emails),encoding:'utf8'});console.log(r.stdout);if(r.status!==0)console.error('Cleanup:',r.stderr);for(const c of clients)await c.dispose();
}})().catch(e=>{console.error(e);process.exitCode=1});
