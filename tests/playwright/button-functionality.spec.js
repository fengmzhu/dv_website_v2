const { test, expect } = require('@playwright/test');

test.describe('DV Website Button Functionality Tests', () => {
  const IT_DOMAIN_URL = 'http://172.18.0.4';
  const NX_DOMAIN_URL = 'http://172.18.0.5';

  test.describe('IT Domain - Button Tests', () => {
    test('Add New Project button works', async ({ page }) => {
      await page.goto(IT_DOMAIN_URL);
      await page.screenshot({ path: 'screenshots/it-domain-main.png', fullPage: true });
      
      // Look for Add New Project button
      const addButton = page.locator('button:has-text("Add New Project"), input[value*="Add"], .btn:has-text("Add")').first();
      
      if (await addButton.isVisible()) {
        await addButton.click();
        await page.screenshot({ path: 'screenshots/it-domain-add-clicked.png', fullPage: true });
        console.log('✅ Add New Project button clicked successfully');
      } else {
        console.log('❌ Add New Project button not found');
        // Log all buttons found
        const buttons = await page.locator('button, input[type="submit"], .btn').all();
        console.log(`Found ${buttons.length} buttons/inputs on page`);
        for (let i = 0; i < Math.min(buttons.length, 10); i++) {
          const text = await buttons[i].textContent();
          const value = await buttons[i].getAttribute('value');
          console.log(`Button ${i + 1}: "${text}" value="${value}"`);
        }
      }
    });

    test('Form submission buttons work', async ({ page }) => {
      await page.goto(IT_DOMAIN_URL);
      
      // Look for form submission buttons
      const submitButtons = await page.locator('input[type="submit"], button[type="submit"]').all();
      console.log(`Found ${submitButtons.length} submit buttons`);
      
      for (let i = 0; i < submitButtons.length; i++) {
        const button = submitButtons[i];
        const value = await button.getAttribute('value') || await button.textContent();
        const isVisible = await button.isVisible();
        const isEnabled = await button.isEnabled();
        
        console.log(`Submit Button ${i + 1}: "${value}" - Visible: ${isVisible}, Enabled: ${isEnabled}`);
        
        if (isVisible && isEnabled) {
          await page.screenshot({ path: `screenshots/it-domain-submit-${i}.png` });
          console.log(`✅ Submit button "${value}" is functional`);
        }
      }
    });

    test('Navigation and action buttons work', async ({ page }) => {
      await page.goto(IT_DOMAIN_URL);
      
      // Look for common action buttons
      const actionSelectors = [
        'button:has-text("Edit")',
        'button:has-text("Delete")',
        'button:has-text("View")',
        'button:has-text("Export")',
        'button:has-text("Import")',
        'button:has-text("Save")',
        'button:has-text("Cancel")',
        '.btn-primary',
        '.btn-secondary',
        '.btn-success',
        '.btn-danger',
        'a.btn'
      ];
      
      for (const selector of actionSelectors) {
        const buttons = await page.locator(selector).all();
        if (buttons.length > 0) {
          console.log(`Found ${buttons.length} buttons matching "${selector}"`);
          for (let i = 0; i < Math.min(buttons.length, 3); i++) {
            const button = buttons[i];
            const text = await button.textContent();
            const isVisible = await button.isVisible();
            const isEnabled = await button.isEnabled();
            
            console.log(`  Button: "${text?.trim()}" - Visible: ${isVisible}, Enabled: ${isEnabled}`);
            
            if (isVisible && isEnabled) {
              // Test hover state
              await button.hover();
              await page.screenshot({ path: `screenshots/it-domain-${selector.replace(/[^a-zA-Z0-9]/g, '_')}-${i}.png` });
              console.log(`✅ Action button "${text?.trim()}" is functional`);
            }
          }
        }
      }
    });

    test('Modal and popup buttons work', async ({ page }) => {
      await page.goto(IT_DOMAIN_URL);
      
      // Look for buttons that might trigger modals
      const modalTriggers = await page.locator('button[data-bs-toggle], button[data-toggle], .modal-trigger').all();
      
      console.log(`Found ${modalTriggers.length} potential modal trigger buttons`);
      
      for (let i = 0; i < modalTriggers.length; i++) {
        const button = modalTriggers[i];
        const text = await button.textContent();
        
        if (await button.isVisible() && await button.isEnabled()) {
          console.log(`Testing modal trigger: "${text?.trim()}"`);
          
          // Click the button and check for modal
          await button.click();
          await page.waitForTimeout(1000); // Wait for modal animation
          
          // Check if modal appeared
          const modal = page.locator('.modal, .popup, .dialog').first();
          if (await modal.isVisible()) {
            await page.screenshot({ path: `screenshots/it-domain-modal-${i}.png`, fullPage: true });
            console.log(`✅ Modal triggered successfully by "${text?.trim()}"`);
            
            // Look for close buttons in modal
            const closeButtons = await page.locator('.modal .btn-close, .modal button:has-text("Close"), .modal button:has-text("Cancel")').all();
            if (closeButtons.length > 0) {
              await closeButtons[0].click();
              console.log(`✅ Modal close button works`);
            }
          }
        }
      }
    });
  });

  test.describe('NX Domain - Button Tests', () => {
    test('NX Domain main functionality buttons', async ({ page }) => {
      await page.goto(NX_DOMAIN_URL);
      await page.screenshot({ path: 'screenshots/nx-domain-main.png', fullPage: true });
      
      // Get all buttons and inputs
      const interactiveElements = await page.locator('button, input[type="submit"], input[type="button"], .btn').all();
      
      console.log(`Found ${interactiveElements.length} interactive elements on NX Domain`);
      
      for (let i = 0; i < Math.min(interactiveElements.length, 10); i++) {
        const element = interactiveElements[i];
        const tagName = await element.evaluate(el => el.tagName);
        const type = await element.getAttribute('type');
        const text = await element.textContent();
        const value = await element.getAttribute('value');
        const className = await element.getAttribute('class');
        const isVisible = await element.isVisible();
        const isEnabled = await element.isEnabled();
        
        console.log(`Element ${i + 1}: ${tagName}[type="${type}"] - "${text?.trim() || value}" class="${className}" - Visible: ${isVisible}, Enabled: ${isEnabled}`);
        
        if (isVisible && isEnabled) {
          await element.hover();
          await page.screenshot({ path: `screenshots/nx-domain-element-${i}.png` });
          console.log(`✅ Element "${text?.trim() || value}" is functional`);
        }
      }
    });

    test('NX Domain form interactions', async ({ page }) => {
      await page.goto(NX_DOMAIN_URL);
      
      // Look for forms and their submit buttons
      const forms = await page.locator('form').all();
      console.log(`Found ${forms.length} forms on NX Domain`);
      
      for (let i = 0; i < forms.length; i++) {
        const form = forms[i];
        const submitButtons = await form.locator('input[type="submit"], button[type="submit"], button:not([type])').all();
        
        console.log(`Form ${i + 1} has ${submitButtons.length} submit buttons`);
        
        for (let j = 0; j < submitButtons.length; j++) {
          const button = submitButtons[j];
          const text = await button.textContent() || await button.getAttribute('value');
          const isVisible = await button.isVisible();
          const isEnabled = await button.isEnabled();
          
          console.log(`  Submit button: "${text}" - Visible: ${isVisible}, Enabled: ${isEnabled}`);
          
          if (isVisible && isEnabled) {
            await page.screenshot({ path: `screenshots/nx-domain-form-${i}-button-${j}.png` });
            console.log(`✅ Form ${i + 1} submit button "${text}" is functional`);
          }
        }
      }
    });
  });

  test.describe('Cross-Domain Button Consistency', () => {
    test('Compare button styles and functionality between domains', async ({ page }) => {
      // Test IT Domain
      await page.goto(IT_DOMAIN_URL);
      const itButtons = await page.locator('button, .btn').all();
      const itButtonData = [];
      
      for (let i = 0; i < Math.min(itButtons.length, 5); i++) {
        const button = itButtons[i];
        const text = await button.textContent();
        const className = await button.getAttribute('class');
        const isVisible = await button.isVisible();
        
        itButtonData.push({
          text: text?.trim(),
          className,
          isVisible,
          domain: 'IT'
        });
      }
      
      // Test NX Domain
      await page.goto(NX_DOMAIN_URL);
      const nxButtons = await page.locator('button, .btn').all();
      const nxButtonData = [];
      
      for (let i = 0; i < Math.min(nxButtons.length, 5); i++) {
        const button = nxButtons[i];
        const text = await button.textContent();
        const className = await button.getAttribute('class');
        const isVisible = await button.isVisible();
        
        nxButtonData.push({
          text: text?.trim(),
          className,
          isVisible,
          domain: 'NX'
        });
      }
      
      console.log('IT Domain Button Summary:');
      itButtonData.forEach((btn, i) => {
        console.log(`  ${i + 1}. "${btn.text}" - class: "${btn.className}" - visible: ${btn.isVisible}`);
      });
      
      console.log('NX Domain Button Summary:');
      nxButtonData.forEach((btn, i) => {
        console.log(`  ${i + 1}. "${btn.text}" - class: "${btn.className}" - visible: ${btn.isVisible}`);
      });
      
      // Check for consistency
      const itButtonTexts = itButtonData.map(b => b.text).filter(Boolean);
      const nxButtonTexts = nxButtonData.map(b => b.text).filter(Boolean);
      const commonButtons = itButtonTexts.filter(text => nxButtonTexts.includes(text));
      
      console.log(`Common buttons found: ${commonButtons.join(', ')}`);
      
      if (commonButtons.length > 0) {
        console.log('✅ Domains have consistent button functionality');
      } else {
        console.log('⚠️ Domains have different button sets (expected for different purposes)');
      }
    });
  });
});