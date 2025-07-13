const { test, expect } = require('@playwright/test');

test.describe('DV Website Debug Tests', () => {
  test('IT Domain - PHP Application loads', async ({ page }) => {
    await page.goto('http://localhost:8080');
    
    // Take screenshot for debugging
    await page.screenshot({ path: 'screenshots/it-domain-php-app.png', fullPage: true });
    
    // Check if page loads (expect database error since no DB is connected)
    await expect(page.locator('body')).toContainText('Database connection failed', { timeout: 10000 });
    
    // Debug: Log page content
    const content = await page.content();
    console.log('IT Domain PHP app response:', content);
  });

  test('NX Domain - PHP Application loads', async ({ page }) => {
    await page.goto('http://localhost:8081');
    
    // Take screenshot for debugging
    await page.screenshot({ path: 'screenshots/nx-domain-php-app.png', fullPage: true });
    
    // Check if page loads (expect database error since no DB is connected)
    await expect(page.locator('body')).toContainText('Database connection failed', { timeout: 10000 });
    
    // Debug: Log page content
    const content = await page.content();
    console.log('NX Domain PHP app response:', content);
  });

  test('Debug both domains - Interactive session', async ({ page }) => {
    console.log('Starting interactive debugging session...');
    
    // Visit IT Domain
    await page.goto('http://localhost:8080');
    await page.screenshot({ path: 'screenshots/debug-it-domain.png', fullPage: true });
    
    const itTitle = await page.title();
    console.log('IT Domain title:', itTitle);
    
    // Check for any forms or interactive elements
    const forms = await page.$$('form');
    console.log('IT Domain forms found:', forms.length);
    
    const links = await page.$$('a');
    console.log('IT Domain links found:', links.length);
    
    // Visit NX Domain
    await page.goto('http://localhost:8081');
    await page.screenshot({ path: 'screenshots/debug-nx-domain.png', fullPage: true });
    
    const nxTitle = await page.title();
    console.log('NX Domain title:', nxTitle);
    
    // Check for any forms or interactive elements
    const nxForms = await page.$$('form');
    console.log('NX Domain forms found:', nxForms.length);
    
    const nxLinks = await page.$$('a');
    console.log('NX Domain links found:', nxLinks.length);
    
    // Pause for manual debugging (will keep browser open)
    console.log('Pausing for manual inspection...');
    await page.pause();
  });
});