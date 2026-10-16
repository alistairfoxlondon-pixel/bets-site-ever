import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';
import { inflate, setupLambdaEnvironment } from '../node_modules/@sparticuz/chromium/build/index.js';
import { existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

if (!existsSync(path.join(tmpdir(), 'al2023', 'lib'))) {
    await inflate(path.resolve('node_modules/@sparticuz/chromium/bin/al2023.tar.br'));
}
setupLambdaEnvironment(path.join(tmpdir(), 'al2023', 'lib'));
process.env.HOME = process.env.HOME || tmpdir();

const browser = await puppeteer.launch({
    args: [...chromium.args, '--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    executablePath: await chromium.executablePath(),
    headless: 'shell',
});

const page = await browser.newPage();
page.on('console', (m) => console.log('CONSOLE', m.type(), m.text()));

const minimal = `data:image/svg+xml;base64,${Buffer.from(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="red"/></svg>'
).toString('base64')}`;

await page.setContent(`<img id="a" src="${minimal}"><img id="b" src="http://127.0.0.1:8000/brand/adready-word.svg">`);
await new Promise((r) => setTimeout(r, 1500));

const info = await page.evaluate(() => ({
    a: { w: document.getElementById('a').naturalWidth, h: document.getElementById('a').naturalHeight },
    b: { w: document.getElementById('b').naturalWidth, h: document.getElementById('b').naturalHeight },
}));

console.log('minimal', info.a, 'brand', info.b);

// Try the brand SVG as a document
const page2 = await browser.newPage();
const response = await page2.goto('http://127.0.0.1:8000/brand/adready-word.svg', { waitUntil: 'load' });
console.log('document status', response.status(), response.headers()['content-type']);
const box = await page2.evaluate(() => ({
    body: document.body ? document.body.innerHTML.slice(0, 120) : 'no body',
    svg: !!document.querySelector('svg'),
    rect: document.querySelector('svg')?.getBoundingClientRect().toJSON(),
    parserError: document.querySelector('parsererror')?.textContent ?? null,
}));
console.log(JSON.stringify(box, null, 2));

await browser.close();
