const {request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');const {spawnSync}=require('node:child_process');
const base='http://localhost/TekoMath-main/01_sistema_principal_y_registro_offline/';
const prefix=`pilot_${Date.now()}_${Math.random().toString(16).slice(2)}`;
const emails=['t1','t2','s1','s2'].map(x=>`${prefix}_${x}@test.invalid`);const contexts=[];const password='PilotTest-9384!';
function field(html,name){const tag=html.match(new RegExp(`<input[^>]*name="${name}"[^>]*>`));assert.ok(tag,`Missing ${name}`);return tag[0].match(/value="([^"]*)"/)[1];}
async function ctx(){const c=await request.newContext({baseURL:base});contexts.push(c);return c;}
async function post(c,url,form){return c.post(url,{form,maxRedirects:0});}
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
 console.log('PASS: real HTTP admin login, two invitations, teacher creation, token replay rejection, teacher login, two public registrations, role tampering blocked, student assignments, teacher API isolation, anonymous/student denials and CSRF.');
 }finally{
 const php='require "01_sistema_principal_y_registro_offline/config/database.php"; $db=getDB(); $emails=json_decode(stream_get_contents(STDIN),true); foreach($emails as $email){$db->prepare("DELETE FROM teacher_account_invites WHERE email=?")->execute([$email]);$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([$email]);} echo "Temporary accounts removed.";';
 const cleanup=spawnSync('C:/xampp1/php/php.exe',['-r',php],{input:JSON.stringify(emails),encoding:'utf8'});console.log(cleanup.stdout);if(cleanup.status!==0)throw new Error(cleanup.stderr);
 for(const c of contexts)await c.dispose();
 }
})().catch(e=>{console.error(e);process.exitCode=1;});
