import { test, expect } from '@playwright/test';

test('User can login and is redirected to dashboard', async ({ page }) => {
    // 1. Go to Login Page
    console.log('Navigating to login page...');
    await page.goto('http://127.0.0.1:8000/login');

    // Check initial title
    console.log('Page Title:', await page.title());
    await expect(page).toHaveTitle(/Inventra/);

    // 2. Fill Credentials
    console.log('Filling credentials...');
    await page.fill('input[name="email"]', 'admin@dev.com');
    await page.fill('input[name="password"]', 'password');

    // 2.5 Solve Captcha
    console.log('Solving Captcha...');

    // Use evaluate to find the numbers by traversing the DOM, which is often more reliable than strict selectors
    const captchaValues = await page.evaluate(() => {
        // Find the "+" symbol
        const plusSpan = Array.from(document.querySelectorAll('span')).find(el => el.textContent.trim() === '+');
        if (!plusSpan) return null;

        // The structure is: div(num1) - span(+) - div(num2)
        const num1Div = plusSpan.previousElementSibling;
        const num2Div = plusSpan.nextElementSibling;

        if (num1Div && num2Div) {
            return {
                num1: parseInt(num1Div.innerText),
                num2: parseInt(num2Div.innerText)
            };
        }
        return null;
    });

    if (!captchaValues) {
        console.log('Error: Could not find captcha numbers via DOM traversal.');
        await page.screenshot({ path: 'debug-captcha-fail.png' });
        throw new Error("Captcha extraction failed");
    }

    const { num1, num2 } = captchaValues;
    const sum = num1 + num2;

    console.log(`Captcha found: ${num1} + ${num2} = ${sum}`);

    // Fill the answer (input has placeholder "?")
    await page.fill('input[placeholder="?"]', sum.toString());

    // 3. Submit
    console.log('Filling form complete. Clicking submit...');
    await page.screenshot({ path: 'debug-before-submit.png' });

    await page.click('button[type="submit"]');

    // 4. Verify Redirect
    console.log('Waiting for navigation to dashboard...');
    try {
        await page.waitForURL('**/dashboard', { timeout: 15000 });
        console.log('Redirect successful!');
    } catch (e) {
        console.log('Tips: Redirect failed. Taking screenshot.');
        console.log('Current URL:', page.url());
        await page.screenshot({ path: 'debug-failed-redirect.png' });
        throw e;
    }

    // 5. Verify Content
    await expect(page.locator('body')).toContainText('Dashboard');
});
