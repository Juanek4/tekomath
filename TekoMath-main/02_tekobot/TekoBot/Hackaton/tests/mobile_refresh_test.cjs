const {chromium}=require('C:/Users/bogad/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
for(const width of [320,390,768,1365]){
 const page=await browser.newPage({viewport:{width,height:740}});
 await page.route('**/*',r=>{const fs=require('node:fs'),path=require('node:path');const url=new URL(r.request().url());if(url.hostname!=='127.0.0.1')return r.abort();const file=path.join(process.cwd(),decodeURIComponent(url.pathname));if(url.pathname.includes('/api/'))return r.fulfill({contentType:'application/json',body:'{"success":true,"sessions":[],"history":[]}'});if(!fs.existsSync(file)||!fs.statSync(file).isFile())return r.fulfill({status:404,body:''});return r.fulfill({body:fs.readFileSync(file),contentType:file.endsWith('.css')?'text/css':file.endsWith('.js')?'text/javascript':file.endsWith('.html')?'text/html':'application/octet-stream'});});
 await page.goto('http://127.0.0.1:8131/02_tekobot/TekoBot/Hackaton/index.html');
 await page.evaluate(()=>{document.body.classList.remove('show-welcome');document.querySelector('#tutor-welcome').style.display='none';});
 assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),`overflow ${width}`);
 if(width<=960){
  assert.ok(await page.locator('.chat-input').isVisible());
  const bounds=await page.locator('#send-btn').boundingBox();assert.ok(bounds.y+bounds.height<=740,`composer clipped ${width}`);
  await page.locator('.mobile-study-nav a').last().click();assert.ok(await page.locator('.visual-panel').isVisible());assert.ok(!await page.locator('.chat-panel').isVisible());
  await page.locator('.mobile-study-nav a').first().click();assert.ok(await page.locator('.chat-panel').isVisible());
  await page.screenshot({path:`02_tekobot/TekoBot/Hackaton/tmp/mobile-refresh-${width}.png`});
 }
 await page.close();
}await browser.close();console.log('PASS: 320, 390, 768 and 1365px; no horizontal overflow; composer visible; mobile study navigation');})().catch(e=>{console.error(e);process.exit(1)});
