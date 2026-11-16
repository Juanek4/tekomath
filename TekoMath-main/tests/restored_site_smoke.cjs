const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');
const root=process.env.TEKO_RESTORE_TEST_URL;
if(!root || new URL(root).hostname!=='127.0.0.1')throw Error('This test requires the isolated localhost restoration server.');
const main='01_sistema_principal_y_registro_offline/',api='02_tekobot/TekoBot/Hackaton/api/';
const clients=[];const stamp=Date.now();const password='RestorationFixture-1938!';
function field(html,name){const tag=html.match(new RegExp(`<input[^>]*name="${name}"[^>]*>`));assert.ok(tag,name);return tag[0].match(/value="([^"]*)"/)[1];}
async function client(){const c=await request.newContext({baseURL:root});clients.push(c);return c;}
(async()=>{try{
 const admin=await client();let html=await(await admin.get(main+'admin.php')).text();
 let response=await admin.post(main+'admin.php',{form:{csrf:field(html,'csrf'),action:'login',username:'tekoadmin',password:process.env.TEKO_ADMIN_TEST_PASSWORD},maxRedirects:0});assert.equal(response.status(),302,'Restored console login');
 html=await(await admin.get(main+'admin.php')).text();const teacherEmail=`restore_teacher_${stamp}@test.invalid`;
 html=await(await admin.post(main+'admin.php',{form:{csrf:field(html,'csrf'),action:'invite',email:teacherEmail}})).text();
 const invitation=html.match(/data-path="(docente_registro.php\?token=[a-f0-9]+)"/)[1];
 const teacher=await client();html=await(await teacher.get(main+invitation)).text();
 html=await(await teacher.post(main+invitation,{form:{csrf:field(html,'csrf'),name:'Restoration teacher',email:teacherEmail,password,confirm:password}})).text();assert.ok(html.includes('Tu cuenta docente está lista'));
 response=await teacher.post(main+'auth.php',{form:{email:teacherEmail,password,rol:'docente'},maxRedirects:0});assert.equal(response.headers().location,'docente.php');
 html=await(await teacher.get(main+'asignaciones.php')).text();
 html=await(await teacher.post(main+'asignaciones.php',{form:{token:field(html,'token'),action:'save_class',colegio:'Colegio de prueba',curso:'1',bachillerato:'Científico · Ciencias Básicas',seccion:'A'}})).text();
 const code=html.match(/<strong[^>]*>([A-Z][0-9]{6})<\/strong>/)[1];
 const student=await client();html=await(await student.get(main+'registro.php')).text();
 response=await student.post(main+'registro_process.php',{form:{csrf_token:field(html,'csrf_token'),nombre:'Restoration student',email:`restore_student_${stamp}@test.invalid`,password,password_confirm:password},maxRedirects:0});assert.equal(response.headers().location,'dashboard.php');
 html=await(await student.get(main+'asignaciones.php')).text();html=await(await student.post(main+'asignaciones.php',{form:{token:field(html,'token'),code}})).text();assert.ok(html.includes('Te vinculaste con tu docente'));
 const assigned=await(await teacher.get(main+'api_estudiantes.php')).json();assert.equal(assigned.success,true);assert.equal(assigned.estudiantes.length,1);
 const token=(await(await student.get(api+'security.php')).json()).token;const headers={'X-Teko-CSRF':token};
 const uuid='restore_'+stamp;
 let data=await(await student.post(api+'sessions.php',{headers,data:{action:'create',session_uuid:uuid,topic:'funciones',subtopic:'lineal',language:'es'}})).json();assert.equal(data.success,true,JSON.stringify(data));
 data=await(await student.post(api+'chat.php',{headers,data:{session_uuid:uuid,message:'¿Qué es una función lineal?',language:'es'}})).json();assert.equal(data.success,true,JSON.stringify(data));
 const history=await(await student.get(api+'history.php?session_uuid='+uuid)).json();assert.ok(history.history.length>=3);
 const diagnostic=await(await student.get(api+'diagnostic.php?unit=funciones&subtopic=lineal')).json();assert.equal(diagnostic.questions.length,6);
 const bank=require('../02_tekobot/TekoBot/Hackaton/config/assessment_bank.json').questions;
 const details=Object.fromEntries(diagnostic.questions.map(q=>[q.id,bank.find(item=>item.id===q.id).correct_index]));
 data=await(await student.post(api+'diagnostic.php',{headers,data:{attempt_token:diagnostic.attempt_token,details}})).json();assert.equal(data.score,100);
 const progress=await(await student.get(api+'progress.php')).json();assert.equal(Number(progress.data.diagnostics[0].score),100);
 if(process.env.TEKO_RESTORE_LOAD==='1'){
  const readers=await Promise.all(Array.from({length:25},()=>client()));
  for(const [index,reader] of readers.entries()){
   const email=`load_${stamp}_${index}@test.invalid`;
   const page=await(await reader.get(main+'registro.php')).text();
   const registration=await reader.post(main+'registro_process.php',{form:{csrf_token:field(page,'csrf_token'),nombre:'Load fixture',email,password,password_confirm:password},maxRedirects:0});
   assert.equal(registration.headers().location,'dashboard.php');
  }
  for(const concurrency of [1,5,10,25]){
   const results=await Promise.allSettled(readers.slice(0,concurrency).map(async reader=>{
    const started=performance.now();
    const response=await reader.get(api+'progress.php',{timeout:15000});
    assert.equal(response.status(),200);
    assert.equal((await response.json()).success,true);
    return performance.now()-started;
   }));
   const timings=results.filter(result=>result.status==='fulfilled').map(result=>result.value).sort((a,b)=>a-b);
   const errors=results.length-timings.length;
   console.log(JSON.stringify({route:'progress',concurrency,errors,p95_ms:timings.length?Math.round(timings[Math.ceil(timings.length*.95)-1]):null}));
   assert.equal(errors,0,'Isolated progress burst');
  }
  if(process.env.TEKO_RESTORE_MIXED==='1'){
   const samples=[];
   async function measured(route,operation){
    const started=performance.now();
    try{
     const response=await operation();
     assert.equal(response.status(),200,route);
     const result=await response.json();
     assert.equal(result.success,true,JSON.stringify(result));
     samples.push({route,ms:performance.now()-started,error:false});
     return result;
    }catch(error){
     samples.push({route,ms:performance.now()-started,error:true});
     throw error;
    }
   }
   const actors=[];
   for(const [index,reader] of readers.entries()){
    const security=await(await reader.get(api+'security.php')).json();
    const actor={reader,headers:{'X-Teko-CSRF':security.token},uuid:`mixed_${stamp}_${index}`};
    await measured('session-create',()=>reader.post(api+'sessions.php',{headers:actor.headers,data:{action:'create',session_uuid:actor.uuid,topic:'funciones',subtopic:'lineal',language:'es'}}));
    actors.push(actor);
   }
   const started=performance.now();
   const results=await Promise.allSettled(actors.map(async actor=>{
    for(let round=0;round<10;round++){
     await measured('chat',()=>actor.reader.post(api+'chat.php',{headers:actor.headers,timeout:30000,data:{session_uuid:actor.uuid,message:round===0?'¿Qué es una función lineal?':'Necesito otro ejemplo de función lineal',language:'es'}}));
     await measured('history',()=>actor.reader.get(api+'history.php?session_uuid='+actor.uuid));
     const assessment=await measured('diagnostic-get',()=>actor.reader.get(api+'diagnostic.php?unit=funciones&subtopic=lineal'));
     const answers=Object.fromEntries(assessment.questions.map(question=>[question.id,bank.find(item=>item.id===question.id).correct_index]));
     const graded=await measured('diagnostic-submit',()=>actor.reader.post(api+'diagnostic.php',{headers:actor.headers,data:{attempt_token:assessment.attempt_token,details:answers}}));
     assert.equal(graded.score,100);
     const saved=await measured('progress-mixed',()=>actor.reader.get(api+'progress.php'));
     assert.ok(saved.data.diagnostics.some(item=>Number(item.score)===100));
    }
   }));
   for(const route of new Set(samples.map(sample=>sample.route))){
    const subset=samples.filter(sample=>sample.route===route);
    const timings=subset.map(sample=>sample.ms).sort((a,b)=>a-b);
    console.log(JSON.stringify({route,requests:subset.length,errors:subset.filter(sample=>sample.error).length,p95_ms:Math.round(timings[Math.ceil(timings.length*.95)-1]),max_ms:Math.round(timings.at(-1))}));
   }
   console.log(JSON.stringify({scenario:'mixed',accounts:actors.length,rounds:10,duration_ms:Math.round(performance.now()-started),failed_actors:results.filter(result=>result.status==='rejected').length}));
   for(const result of results)if(result.status==='rejected')throw result.reason;
  }
 }
 console.log('PASS restored site: chosen admin password, teacher invitation/login, student registration, assignment, teacher isolation, tutor chat/history and diagnostic persistence.');
}finally{for(const c of clients)await c.dispose();}})().catch(error=>{console.error(error);process.exitCode=1});
