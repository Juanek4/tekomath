const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');const {spawnSync}=require('node:child_process');
const base='http://localhost/TekoMath-main/01_sistema_principal_y_registro_offline/';
const prefix=`pilot_${Date.now()}_${Math.random().toString(16).slice(2)}`;
const emails=['t1','t2','s1','s2'].map(x=>`${prefix}_${x}@test.invalid`);const contexts=[];const password='PilotTest-9384!';
function field(html,name){const tag=html.match(new RegExp(`<input[^>]*name="${name}"[^>]*>`));assert.ok(tag,`Missing ${name}`);return tag[0].match(/value="([^"]*)"/)[1];}
async function ctx(){const c=await request.newContext({baseURL:base});contexts.push(c);return c;}
async function post(c,url,form){const headers={};if(/api_(tareas|materiales|sala|progreso)\.php/.test(url)){const r=await c.get("api_csrf.php");headers["X-Teko-CSRF"]=(await r.json()).token;}return c.post(url,{form,headers,maxRedirects:0});}
(async()=>{
 try{
 const admin=await ctx();let html=await(await admin.get('admin.php')).text();
 let r=await post(admin,'admin.php',{csrf:field(html,'csrf'),action:'login',username:'tekoadmin',password:process.env.TEKO_ADMIN_TEST_PASSWORD});assert.equal(r.status(),302,'Administrator login');
 const teachers=[];
 for(let i=0;i<2;i++){
  html=await(await admin.get('admin.php')).text();r=await post(admin,'admin.php',{csrf:field(html,'csrf'),action:'invite',email:emails[i]});html=await r.text();
  const path=html.match(/data-path="([^"]+)"/);assert.ok(path,'Invitation link');const invite=path[1];const teacher=await ctx();
  html=await(await teacher.get(invite)).text();r=await post(teacher,invite,{csrf:field(html,'csrf'),name:`${prefix} docente ${i}`,email:emails[i],password,confirm:password});assert.ok((await r.text()).includes('Tu cuenta docente está lista'),'Teacher registration');
  r=await post(teacher,invite,{csrf:field(html,'csrf'),name:'Replay',email:emails[i],password,confirm:password});assert.ok(!(await r.text()).includes('Tu cuenta docente está lista'),'Invitation replay denied');
  r=await post(teacher,'auth.php',{email:emails[i],password,rol:'docente'});assert.equal(r.headers().location,'docente.php','Teacher login destination');
  html=await(await teacher.get('asignaciones.php')).text();const code=html.match(/<strong[^>]*>([A-Z][0-9]{6})<\/strong>/);assert.ok(code,'Teacher assignment code');teachers.push({client:teacher,code:code[1]});
 }
 const students=[];
 for(let i=0;i<2;i++){
  const student=await ctx();html=await(await student.get('registro.php')).text();
  r=await post(student,'registro_process.php',{csrf_token:field(html,'csrf_token'),nombre:`${prefix} alumno ${i}`,email:emails[i+2],password,password_confirm:password,rol:'docente'});
  assert.equal(r.headers().location,'dashboard.php','Forged teacher role must remain student');
  html=await(await student.get('asignaciones.php')).text();r=await post(student,'asignaciones.php',{token:field(html,'token'),code:teachers[i].code});assert.ok((await r.text()).includes('Te vinculaste con tu docente'),'Student joins');students.push(student);
 }
 for(let i=0;i<2;i++){
  const data=await(await teachers[i].client.get('api_estudiantes.php')).json();assert.equal(data.success,true);assert.deepEqual(data.estudiantes.map(x=>x.email),[emails[i+2]],'Teacher student isolation');
 }
 r=await students[0].get('api_estudiantes.php',{maxRedirects:0});assert.ok(r.status()===302||r.status()===403,'Student cannot list students');
 const anonymous=await ctx();r=await anonymous.get('asignaciones.php',{maxRedirects:0});assert.equal(r.status(),302,'Anonymous denied');
 html=await(await students[0].get('asignaciones.php')).text();r=await post(students[0],'asignaciones.php',{token:'forged',code:teachers[1].code});assert.equal(r.status(),403,'Assignment CSRF rejected');
 let qdata=await(await post(students[0],'api_progreso.php',{action:'start',tema:'Trigonometría',dificultad:'facil'})).json();assert.equal(qdata.success,true);const concurrent=await(await post(students[0],'api_progreso.php',{action:'start',tema:'Trigonometría',dificultad:'medio'})).json();assert.equal(concurrent.success,true);assert.notEqual(concurrent.quiz_id,qdata.quiz_id);
 assert.ok(qdata.questions.every(q=>!('correct' in q)&&!('exp' in q)),'No answer key in quiz start');
 let expected=0;
 for(let index=0;index<qdata.questions.length;index++){
  const answer=await(await post(students[0],'api_progreso.php',{action:'answer',quiz_id:qdata.quiz_id,index,option:1})).json();assert.equal(answer.success,true);if(answer.correct)expected++;
 }
 let grade=await(await post(students[0],'api_progreso.php',{quiz_id:qdata.quiz_id,aciertos:999,total_preguntas:1})).json();assert.equal(grade.success,true);assert.equal(grade.porcentaje,Math.round(expected*100/qdata.questions.length),'Forged counters ignored');
 const replay=await post(students[0],'api_progreso.php',{quiz_id:qdata.quiz_id,aciertos:999});assert.equal(replay.status(),409,'Duplicate grade rejected');
 const otherAnswer=await(await post(students[0],'api_progreso.php',{action:'answer',quiz_id:concurrent.quiz_id,index:0,option:0})).json();assert.equal(otherAnswer.success,true,'Second quiz survives first quiz completion');
 console.log('PASS: server quiz grading, hidden answer key, manipulated counters ignored and duplicate submission blocked.');
 const resourceApi='../04_diagnostico_y_rutas_de_aprendizaje/';
 const taskIds=[],materialIds=[];
 for(let i=0;i<2;i++){
  let result=await(await post(teachers[i].client,resourceApi+'api_tareas.php',{titulo:prefix+' tarea '+i,fecha_entrega:'2027-01-31',template_key:'trig_cuadrantes'})).json();assert.equal(result.success,true,JSON.stringify(result));taskIds.push(result.tarea.id);
  result=await(await post(teachers[i].client,resourceApi+'api_materiales.php',{titulo:prefix+' material '+i})).json();assert.equal(result.success,true);materialIds.push(result.material.id);
 }
 for(let i=0;i<2;i++){
  let result=await(await students[i].get(resourceApi+'api_tareas.php')).json();assert.deepEqual(result.tareas.map(x=>Number(x.id)),[taskIds[i]],'Student task list isolation');
  result=await(await teachers[i].client.get(resourceApi+'api_materiales.php')).json();assert.deepEqual(result.materiales.map(x=>Number(x.id)),[materialIds[i]],'Teacher material list isolation');
  let denied=await students[i].get(resourceApi+'api_tareas.php?action=detalle&id='+taskIds[1-i]);assert.equal(denied.status(),404,'Foreign task detail denied');
  denied=await post(students[i],resourceApi+'api_tareas.php',{action:'guardar_progreso_mision',tarea_id:taskIds[1-i],dominio_pct:100});assert.equal(denied.status(),404,'Foreign task progress denied');
  const started=await(await post(students[i],resourceApi+'api_tareas.php',{action:'iniciar_intento_mision',tarea_id:taskIds[i]})).json();assert.equal(started.success,true,JSON.stringify(started));const reopened=await(await post(students[i],resourceApi+'api_tareas.php',{action:'iniciar_intento_mision',tarea_id:taskIds[i]})).json();assert.equal(reopened.attempt_id,started.attempt_id,'Reopen keeps active attempt');assert.equal(reopened.resumed,true,'Resume reported');assert.ok(!JSON.stringify(started.mission).includes('es_correcta'),'No mission keys');
  const definition=await(await teachers[i].client.get(resourceApi+'api_tareas.php?action=detalle&id='+taskIds[i])).json();
  const items=[];definition.mision_data.etapas.forEach((stage,si)=>{if(stage.opciones?.length)items.push({stage:si,step:-1,options:stage.opciones});(stage.pasos||[]).forEach((step,pi)=>{if(step.opciones?.length)items.push({stage:si,step:pi,options:step.opciones});});});
  const premature=await post(students[i],resourceApi+'api_tareas.php',{action:'guardar_progreso_mision',tarea_id:taskIds[i],attempt_id:started.attempt_id,dominio_pct:100});assert.equal(premature.status(),409,'Incomplete attempt rejected');
  for(const item of items){const option=item.options.find(o=>o.es_correcta);const request_id=require('node:crypto').randomUUID();const fields={action:'responder_mision',tarea_id:taskIds[i],attempt_id:started.attempt_id,stage:item.stage,step:item.step,option:option.id,request_id};const accepted=await(await post(students[i],resourceApi+'api_tareas.php',fields)).json();assert.equal(accepted.correct,true,JSON.stringify(accepted));const retry=await(await post(students[i],resourceApi+'api_tareas.php',fields)).json();assert.equal(retry.correct,true,'Idempotent response');const resumed=await(await post(students[i],resourceApi+'api_tareas.php',{action:'iniciar_intento_mision',tarea_id:taskIds[i]})).json();assert.equal(resumed.attempt_id,started.attempt_id,'Same attempt after each answer');assert.ok(resumed.answers.some(a=>a.request_id===request_id),'Persisted answer restored');assert.notEqual(resumed.next_item,item.stage+':'+item.step,'Completed item not repeated');}
  let saved=await post(students[i],resourceApi+'api_tareas.php',{action:'guardar_progreso_mision',tarea_id:taskIds[i],attempt_id:started.attempt_id,dominio_pct:0,respuestas_json:'[]'});assert.equal((await saved.json()).success,true,'Own task progress saved');
  const duplicate=await post(students[i],resourceApi+'api_tareas.php',{action:'guardar_progreso_mision',tarea_id:taskIds[i],attempt_id:started.attempt_id});assert.equal(duplicate.status(),409,'Completed attempt cannot duplicate result');
  if(i===0){
   const again=await(await post(students[i],resourceApi+'api_tareas.php',{action:'iniciar_intento_mision',tarea_id:taskIds[i]})).json();assert.notEqual(again.attempt_id,started.attempt_id,'New attempt after completion');
   for(const item of items){const option=item.options.find(o=>o.es_correcta);const result=await(await post(students[i],resourceApi+'api_tareas.php',{action:'responder_mision',tarea_id:taskIds[i],attempt_id:again.attempt_id,stage:item.stage,step:item.step,option:option.id,request_id:require('node:crypto').randomUUID()})).json();assert.equal(result.correct,true);}
   const result=await(await post(students[i],resourceApi+'api_tareas.php',{action:'guardar_progreso_mision',tarea_id:taskIds[i],attempt_id:again.attempt_id})).json();assert.equal(result.success,true);
   const both=await(await students[i].get('historial_misiones.php')).text();assert.ok(both.includes('2 intentos.'),'Both attempts retained');
  }
  const history=await(await teachers[i].client.get('historial_misiones.php')).text();assert.ok(history.includes(prefix+' tarea '+i),'Owner sees mission history');assert.ok(!history.includes(prefix+' tarea '+(1-i)),'Foreign teacher history hidden');
  const studentHistory=await(await students[i].get('historial_misiones.php')).text();assert.ok(studentHistory.includes(prefix+' tarea '+i),'Student sees own history');assert.ok(!studentHistory.includes(prefix+' tarea '+(1-i)),'Foreign student history hidden');
  denied=await post(teachers[1-i].client,resourceApi+'api_tareas.php',{action:'delete',id:taskIds[i]});assert.equal(denied.status(),404,'Foreign task delete denied');
  denied=await post(teachers[1-i].client,resourceApi+'api_materiales.php',{action:'delete',id:materialIds[i]});assert.equal(denied.status(),404,'Foreign material delete denied');
  const intact=await(await students[i].get(resourceApi+'api_tareas.php?action=detalle&id='+taskIds[i])).json();assert.equal(intact.success,true);assert.ok(intact.progreso,'Progress preserved after denied delete');
 }
 const fixturePhp='require "01_sistema_principal_y_registro_offline/config/database.php";$db=getDB();[$email,$task]=json_decode(stream_get_contents(STDIN),true);$q=$db->prepare("SELECT da.estudiante_id,t.docente_id,t.titulo FROM docente_alumnos da JOIN tareas t ON t.docente_id=da.docente_id JOIN usuarios u ON u.id=da.estudiante_id WHERE u.email=? AND t.id=?");$q->execute([$email,$task]);$x=$q->fetch();for($i=0;$i<35;$i++)$db->prepare("INSERT INTO mission_results(attempt_id,student_id,teacher_id,task_id,task_title,correct_count,total_count,percentage,completed_at) VALUES(?,?,?,?,?,1,1,100,?)")->execute([bin2hex(random_bytes(32)),$x["estudiante_id"],$x["docente_id"],$task,$x["titulo"],gmdate("Y-m-d H:i:s")]);';
 const fixture=spawnSync('C:/xampp1/php/php.exe',['-r',fixturePhp],{input:JSON.stringify([emails[2],taskIds[0]]),encoding:'utf8'});assert.equal(fixture.status,0,fixture.stderr);
 const pageOne=await(await teachers[0].client.get('historial_misiones.php')).text(),pageTwo=await(await teachers[0].client.get('historial_misiones.php?page=2')).text();
 assert.equal((pageOne.match(/<article /g)||[]).length,30,'History page size');assert.ok((pageTwo.match(/<article /g)||[]).length>0,'Second history page populated');assert.ok(!pageTwo.includes(prefix+' tarea 1'),'Other teacher excluded from second page');console.log('PASS teacher report pagination with synthetic temporary fixtures');
 for(let i=0;i<2;i++){
  assert.equal((await(await post(teachers[i].client,resourceApi+'api_tareas.php',{action:'delete',id:taskIds[i]})).json()).success,true,'Owner can delete task');
  assert.equal((await(await post(teachers[i].client,resourceApi+'api_materiales.php',{action:'delete',id:materialIds[i]})).json()).success,true,'Owner can delete material');
 }
 console.log('PASS: task/material creation, scoped listings, detail isolation, progress ownership, unauthorized deletions preserve data, owner deletion.');
 console.log('PASS: real HTTP admin login, two invitations, teacher creation, token replay rejection, teacher login, two public registrations, role tampering blocked, student assignments, teacher API isolation, anonymous/student denials and CSRF.');
 }finally{
 const php='require "01_sistema_principal_y_registro_offline/config/database.php"; $db=getDB(); $emails=json_decode(stream_get_contents(STDIN),true); foreach($emails as $email){$db->prepare("DELETE FROM teacher_account_invites WHERE email=?")->execute([$email]);$db->prepare("DELETE pm FROM progreso_misiones pm JOIN tareas t ON t.id=pm.tarea_id JOIN usuarios u ON u.id=t.docente_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE t FROM tareas t JOIN usuarios u ON u.id=t.docente_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE m FROM materiales m JOIN usuarios u ON u.id=m.docente_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE p FROM progreso_quizzes p JOIN usuarios u ON u.id=p.estudiante_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE a FROM mission_attempts a JOIN usuarios u ON u.id=a.student_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE r FROM mission_results r JOIN usuarios u ON u.id=r.student_id WHERE u.email=?")->execute([$email]);$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([$email]);} echo "Temporary accounts removed.";';
 const cleanup=spawnSync('C:/xampp1/php/php.exe',['-r',php],{input:JSON.stringify(emails),encoding:'utf8'});console.log(cleanup.stdout);if(cleanup.status!==0)throw new Error(cleanup.stderr);
 for(const c of contexts)await c.dispose();
 }
})().catch(e=>{console.error(e);process.exitCode=1;});
