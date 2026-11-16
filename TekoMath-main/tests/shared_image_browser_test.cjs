const {chromium}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');const assert=require('node:assert/strict');
(async()=>{const fs=require('node:fs'),path=require('node:path'),http=require('node:http');const server=http.createServer((req,res)=>{if(req.url==='/'){res.setHeader('Content-Type','text/html');return res.end('<html><body>OCR test</body></html>');}const file=path.resolve(process.cwd(),decodeURIComponent(req.url.split('?')[0]).replace(/^\/TekoMath-main\//,''));if(!file.startsWith(process.cwd()+path.sep)||!fs.existsSync(file)){res.statusCode=404;return res.end();}res.setHeader('Content-Type',file.endsWith('.js')?'text/javascript':file.endsWith('.wasm')?'application/wasm':'application/octet-stream');res.end(fs.readFileSync(file));});await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));const port=server.address().port;const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});try{
 const page=await browser.newPage();const external=[];await page.route('**/*',r=>{const u=new URL(r.request().url());if((u.hostname==='localhost'||u.hostname==='127.0.0.1')||u.protocol==='blob:'||u.protocol==='data:')return r.continue();external.push(u.hostname);return r.abort();});
 await page.goto('http://127.0.0.1:'+port+'/',{waitUntil:'domcontentloaded'});
 const results=await page.evaluate(async()=>{
  const engine=await import('/TekoMath-main/03_teko_arcade/TEKOARCADE/JUEGOS/dist/shared/image-recognition.js');
  const canvas=document.createElement('canvas');canvas.width=1200;canvas.height=360;const ctx=canvas.getContext('2d');ctx.fillStyle='white';ctx.fillRect(0,0,1200,360);ctx.fillStyle='black';ctx.font='44px Arial';ctx.fillText('Calcular coseno del angulo.',40,100);ctx.fillText('Cateto adyacente 3, hipotenusa 5.',40,180);const image=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));
  const text=await engine.recognizeImageText(image);
  ctx.fillStyle='white';ctx.fillRect(0,0,1200,360);const blank=await new Promise(resolve=>canvas.toBlob(resolve,'image/png'));let emptyRejected=false,brokenRejected=false;
  try{await engine.recognizeImageText(blank)}catch{emptyRejected=true}
  try{await engine.recognizeImageText(new Blob(['not an image'],{type:'image/png'}))}catch{brokenRejected=true}
  await engine.terminateImageTextRecognition();return {text,emptyRejected,brokenRejected};
 });
 assert.match(results.text,/coseno/i);assert.match(results.text,/hipotenusa\s*5/i);assert.equal(results.emptyRejected,true);assert.equal(results.brokenRejected,true);console.log('PASS: real local OCR reads a printed exercise, rejects blank/corrupt images.');console.log(results.text);
}finally{await browser.close();await new Promise(resolve=>server.close(resolve));}})().catch(e=>{console.error(e);process.exitCode=1});
