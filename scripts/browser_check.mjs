/**
 * Headless browser check used during development.
 *
 * Usage: node scripts/browser_check.mjs <baseUrl> [name=path ...] [--auth=email:pass] [--vp=mobile,tablet,desktop]
 * Screenshots are written to storage/app/browser/<name>.png
 */
import puppeteer from 'puppeteer-core';
import chromium from '@sparticuz/chromium';
import { inflate, setupLambdaEnvironment } from '../node_modules/@sparticuz/chromium/build/index.js';
import { mkdir } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';

// The sandbox is not Amazon Linux, so the bundled shared libraries are
// unpacked by hand and the loader path is set before the browser starts.
if (!existsSync(path.join(tmpdir(), 'al2023', 'lib'))) {
    await inflate(path.resolve('node_modules/@sparticuz/chromium/bin/al2023.tar.br'));
}

setupLambdaEnvironment(path.join(tmpdir(), 'al2023', 'lib'));
process.env.HOME = process.env.HOME || tmpdir();

const argv = process.argv.slice(2);
const baseUrl = argv.find((a) => a.startsWith('http')) || 'http://127.0.0.1:8000';
const authArg = argv.find((a) => a.startsWith('--auth='));
const vpArg = argv.find((a) => a.startsWith('--vp='));
const targets = argv.filter((a) => !a.startsWith('--') && !a.startsWith('http'));
const outDir = path.resolve('storage/app/browser');

const allViewports = [
    { name: 'mobile', width: 390, height: 844, deviceScaleFactor: 2, isMobile: true },
    { name: 'tablet', width: 820, height: 1180, deviceScaleFactor: 2 },
    { name: 'desktop', width: 1440, height: 900, deviceScaleFactor: 1 },
];

const wanted = vpArg ? vpArg.replace('--vp=', '').split(',').map((v) => v.trim()) : allViewports.map((v) => v.name);
const viewports = allViewports.filter((v) => wanted.includes(v.name));

const pairs = targets.length
    ? targets.map((pair) => {
          const [name, url] = pair.includes('=') ? pair.split('=') : [pair.replace(/\W+/g, '-'), pair];
          return { name, url: url.startsWith('http') ? url : baseUrl + url };
      })
    : [{ name: 'home', url: baseUrl + '/' }];

await mkdir(outDir, { recursive: true });

const browser = await puppeteer.launch({
    args: [...chromium.args, '--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    executablePath: await chromium.executablePath(),
    headless: 'shell',
});

const problems = [];

if (authArg) {
    const [email, password] = authArg.replace('--auth=', '').split(':');
    const authPage = await browser.newPage();
    await authPage.goto(baseUrl + '/login', { waitUntil: 'networkidle2', timeout: 60000 });
    await authPage.type('#email', email);
    await authPage.type('#password', password);
    await Promise.all([
        authPage.waitForNavigation({ waitUntil: 'networkidle2', timeout: 60000 }).catch(() => {}),
        authPage.click('button[type="submit"]'),
    ]);
    const landed = authPage.url();
    if (landed.includes('/login')) {
        console.log('LOGIN FAILED for ' + email + ' (landed on ' + landed + ')');
        problems.push('login failed for ' + email);
    } else {
        console.log('logged in as ' + email + ' -> ' + landed);
    }
    await authPage.close();
}

for (const viewport of viewports) {
    for (const target of pairs) {
        const page = await browser.newPage();
        await page.setViewport({
            width: viewport.width,
            height: viewport.height,
            deviceScaleFactor: viewport.deviceScaleFactor,
            isMobile: viewport.isMobile,
            hasTouch: viewport.isMobile,
        });

        const consoleErrors = [];
        page.on('console', (message) => {
            if (message.type() === 'error') consoleErrors.push(message.text());
        });
        page.on('pageerror', (error) => consoleErrors.push(String(error)));

        const response = await page.goto(target.url, { waitUntil: 'networkidle2', timeout: 60000 });

        const status = response ? response.status() : 0;
        if (status >= 400) {
            problems.push(`${target.name} [${viewport.name}] returned HTTP ${status}`);
        }

        const metrics = await page.evaluate(() => {
            const doc = document.documentElement;
            const overflowing = [];
            document.querySelectorAll('body *').forEach((el) => {
                const rect = el.getBoundingClientRect();
                if (rect.width > 0 && rect.right > doc.clientWidth + 2) {
                    const style = window.getComputedStyle(el);
                    if (style.position === 'fixed' || style.overflowX === 'auto' || style.overflowX === 'scroll') return;
                    overflowing.push(el.tagName.toLowerCase() + '.' + (el.className || '').toString().split(' ').slice(0, 2).join('.'));
                }
            });

            const brokenImages = [];
            document.querySelectorAll('img').forEach((img) => {
                if (!img.complete || img.naturalWidth === 0) {
                    brokenImages.push((img.getAttribute('src') || '?') + ' alt=' + (img.getAttribute('alt') || ''));
                }
            });

            const tinyText = [];
            document.querySelectorAll('body *').forEach((el) => {
                if (el.closest('svg')) return;
                if (el.children.length === 0 && el.textContent.trim().length > 0) {
                    const size = parseFloat(window.getComputedStyle(el).fontSize);
                    if (size && size < 10) tinyText.push(el.tagName.toLowerCase() + ' ' + size + 'px');
                }
            });

            return {
                scrollWidth: doc.scrollWidth,
                clientWidth: doc.clientWidth,
                title: document.title,
                overflowing: overflowing.slice(0, 6),
                brokenImages: brokenImages.slice(0, 6),
                tinyText: tinyText.slice(0, 4),
            };
        });

        if (metrics.scrollWidth > metrics.clientWidth + 2) {
            problems.push(
                `${target.name} [${viewport.name}] horizontal overflow: ${metrics.scrollWidth} > ${metrics.clientWidth} (${metrics.overflowing.join(', ')})`
            );
        }

        if (metrics.brokenImages.length) {
            problems.push(`${target.name} [${viewport.name}] broken images: ${metrics.brokenImages.join(', ')}`);
        }

        if (metrics.tinyText.length) {
            problems.push(`${target.name} [${viewport.name}] tiny text: ${metrics.tinyText.join(', ')}`);
        }

        if (consoleErrors.length) {
            problems.push(`${target.name} [${viewport.name}] console: ${consoleErrors.slice(0, 3).join(' | ')}`);
        }

        await page.screenshot({
            path: path.join(outDir, `${target.name}-${viewport.name}.png`),
            fullPage: false,
        });

        await page.close();
    }
}

await browser.close();

if (problems.length) {
    console.log('PROBLEMS FOUND');
    problems.forEach((problem) => console.log(' - ' + problem));
    process.exitCode = 1;
} else {
    console.log('OK: no overflow, broken images or console errors detected');
}
