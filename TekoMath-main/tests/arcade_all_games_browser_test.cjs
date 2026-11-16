const {chromium,request}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const {spawnSync}=require('node:child_process');const {pathToFileURL}=require('node:url');const path=require('node:path');const assert=require('node:assert/strict');
const source=path.resolve('03_teko_arcade/TEKOARCADE/JUEGOS/src');const mod=p=>import(pathToFileURL(path.join(source,p)).href);
(async()=>{const {getExercisesForTopic}=await mod('exercises/data/index.ts'),triangle=await mod('games/triangle-forge/gameLogic.ts'),{createVectorLaunchChallenges}=await mod('games/vector-launch/vectorLaunchEngine.ts'),{createGraphChallenge,formatGraphAngle}=await mod('games/graph-lab/graphEngine.ts'),{createSignalSyncDeck}=await mod('games/signal-sync/signalSyncData.ts'),{getTopicById}=await mod('data/topics.ts'),{pairMatrixCards}=await mod('games/pair-matrix/pairLogic.ts');
 const base='http://localhost/TekoMath-main/01_sistema_principal_y_registro_offline/';const email='allgames_'+Date.now()+'@test.invalid';const api=await request.newContext({baseURL:base});let browser;const results=[];
 try{
  const html=await(await api.get('registro.php')).text();await api.post('registro_process.php',{form:{csrf_token:html.match(/name="csrf_token" value="([^"]+)"/)[1],nombre:'Prueba seis juegos',email,password:'AllGames-123!',password_confirm:'AllGames-123!'},maxRedirects:0});
  browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});const ctx=await browser.newContext({storageState:await api.storageState(),viewport:{width:1365,height:900}});const page=await ctx.newPage();await page.route('**/*',r=>new URL(r.request().url()).hostname==='localhost'?r.continue():r.abort());await page.clock.install();
  const games=[['triangle-forge','sin',3],['signal-sync','cos',3],['vector-launch','tan',5],['pair-matrix','csc',3],['graph-lab','sin',4],['ratio-rush','sec',1]];
  for(const [game,topic,configuredRounds] of games){let rounds=configuredRounds;
   console.log('TEST',game);await page.goto('http://localhost/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/#/preparar/'+topic+'/'+game,{waitUntil:'networkidle'});await page.getByRole('button',{name:'Comenzar partida',exact:true}).click();await page.locator('.game-actions__primary').click();
   const stored=await page.evaluate(()=>JSON.parse(sessionStorage.getItem('teko-juegos-session')));const bank=getExercisesForTopic(topic),selected=stored.exerciseDefinition||bank.find(x=>x.id===stored.exerciseId);
   if(game==='signal-sync')rounds=stored.roundCount||4;
   let options;
   if(game==='triangle-forge')options=triangle.createTriangleForgeExerciseSet(triangle.toTriangleForgeExercise(selected),bank,rounds).map(x=>x.expectedAnswer);
   if(game==='vector-launch')options=createVectorLaunchChallenges(bank,topic,stored.difficulty,stored.sessionId,rounds,stored.exerciseId).map(x=>String(x.exercise.expectedAnswer));
   if(game==='signal-sync')options=createSignalSyncDeck(bank,getTopicById(topic),{topicId:topic,difficulty:stored.difficulty,roundCount:stored.roundCount||3,selectedExerciseId:stored.exerciseId});
   if(await page.locator('.game-session-header__pause').count()){await page.locator('.game-session-header__pause').click();assert.ok(await page.locator('.game-pause-overlay').isVisible());await page.locator('.game-pause-overlay button').click();}
   for(let round=0;round<rounds;round++){
    if(['triangle-forge','vector-launch'].includes(game)){
     const input=page.locator('.game-play-area input').first();
     if(round===0){await input.fill('99999');await page.locator('.game-actions__primary').click();assert.ok(await page.locator('.game-feedback--incorrect').isVisible());await page.locator('.game-actions__primary').click();if(game==='vector-launch')continue;}
     await input.fill(String(options[round]));
    }else if(game==='pair-matrix'){
     if(round===0){for(const name of ['sin','cos'])await page.locator('.pair-matrix__card').filter({has:page.locator('strong',{hasText:new RegExp('^'+name+'$')})}).click();await page.locator('.game-actions__primary').click();assert.ok(await page.locator('.game-feedback--incorrect').isVisible());await page.locator('.game-actions__primary').click();}
     const pair=[['sin','csc'],['cos','sec'],['tan','cot']][round];for(const id of pair){const card=pairMatrixCards.find(x=>x.id===id);await page.locator('.pair-matrix__card').filter({has:page.locator('strong',{hasText:new RegExp('^'+card.label+'$')})}).click();}
    }else if(game==='signal-sync'){
     const item=options[round];
     if(round===0){await page.locator('.signal-sync__column').nth(0).locator('button').nth(0).click();const targets=page.locator('.signal-sync__column').nth(1).locator('button');const labels=await targets.allTextContents();await targets.nth(labels.findIndex(x=>x.trim()!==item.target)).click();await page.locator('.game-actions__primary').click();assert.ok(await page.locator('.game-feedback--incorrect').isVisible());await page.locator('.game-actions__primary').click();}await page.locator('.signal-sync__column').nth(0).locator('button').nth(round).click();await page.locator('.signal-sync__column').nth(1).locator('button:not([disabled])').filter({hasText:item.target}).click();
    }else if(game==='graph-lab'){
     const challenge=createGraphChallenge(topic,round+1);
     if(round===0){const incorrect=challenge.answerOptions.find(x=>String(x.value)!==challenge.expectedAnswer);await page.locator('.graph-lab__answers button').filter({hasText:incorrect.label}).click();await page.locator('.game-actions__primary').click();assert.ok(await page.locator('.game-feedback--incorrect').isVisible());await page.locator('.game-actions__primary').click();continue;}
     if(challenge.kind==='value'){const label=challenge.answerOptions.find(x=>Number(x.value)===Number(challenge.expectedAnswer))?.label||challenge.expectedAnswer;await page.locator('.graph-lab__answers button').filter({hasText:new RegExp('^'+label.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+'$')}).click();}
     else await page.locator('.graph-lab__point-options button').filter({hasText:'x = '+formatGraphAngle(Number(challenge.expectedAnswer))}).click();
    }else{await page.locator('.ratio-rush__option').filter({hasText:'1.25'}).click();}
    await page.locator('.game-actions__primary').click();assert.ok(await page.locator('.game-feedback--correct').isVisible(),game+' round '+round);
    if(round===0){await page.reload({waitUntil:'networkidle'});assert.ok(await page.locator('.game-feedback--correct').isVisible(),'Feedback restored after mid-game reload '+game);await page.goto('http://localhost/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/#/',{waitUntil:'networkidle'});await page.locator('.resume-game-link').click();await page.locator('.game-feedback--correct').waitFor();assert.ok(await page.locator('.game-feedback--correct').isVisible(),'Resume from home '+game);}
    if(game==='ratio-rush'){await page.clock.fastForward(61000);}else await page.locator('.game-actions__primary').click();
   }
   await page.locator('.game-result-panel').waitFor();await page.waitForFunction(()=>document.getElementById('arcade-sync-status')?.textContent==='Progreso guardado');
   const before=await(await api.get('api_arcade_progress.php')).json();const history=before.snapshot.gameHistory;assert.equal(history.filter(x=>x.gameId===game).length,1,'One completed record '+game);assert.ok(history.find(x=>x.gameId===game).correctAnswers>=1,'Recorded answers '+game);
   await page.reload({waitUntil:'networkidle'});const after=await(await api.get('api_arcade_progress.php')).json();assert.equal(after.snapshot.gameHistory.length,history.length,'No duplication after reload');assert.equal(after.revision,before.revision,'No rewrite after reload');
   results.push(game);console.log('PASS',game);
  }
  const beforeReward=await(await api.get('api_arcade_progress.php')).json();
  const claim=page.locator('.right-panel__card--quest button');assert.equal(await claim.isEnabled(),true,'Daily quest completed by sine game');await claim.click();await page.waitForFunction(()=>document.getElementById('arcade-sync-status')?.textContent==='Progreso guardado');
  const rewarded=await(await api.get('api_arcade_progress.php')).json();assert.equal(rewarded.snapshot.xp,beforeReward.snapshot.xp+30,'Daily reward added once');assert.equal(await claim.isDisabled(),true);
  const second=await browser.newContext({storageState:await api.storageState(),viewport:{width:1365,height:900}});const remote=await second.newPage();await remote.route('**/*',r=>new URL(r.request().url()).hostname==='localhost'?r.continue():r.abort());await remote.goto('http://localhost/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/',{waitUntil:'networkidle'});assert.equal(await remote.locator('.right-panel__card--quest button').isDisabled(),true,'Second device sees claimed reward');await second.close();console.log('PASS daily reward and second device');
  await page.goto('http://localhost/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/#/preparar/cot/graph-lab',{waitUntil:'networkidle'});await page.waitForURL('**/#/tema/cot');assert.ok(page.url().endsWith('/tema/cot'),'Unsupported graph topic redirected');
  console.log('PASS ALL',results);
 }finally{if(browser)await browser.close();await api.dispose();const cleanup=spawnSync('C:/xampp1/php/php.exe',['-r','require "01_sistema_principal_y_registro_offline/config/database.php";$db=getDB();$db->prepare("DELETE FROM usuarios WHERE email=?")->execute([trim(stream_get_contents(STDIN))]);'],{input:email,encoding:'utf8'});if(cleanup.status!==0)throw Error(cleanup.stderr);}
})().catch(e=>{console.error(e);process.exitCode=1});
