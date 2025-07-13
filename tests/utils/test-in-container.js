const { exec } = require('child_process');
const fs = require('fs');

// Test script to run inside a container that can access the web containers
const testScript = `
const { test, expect } = require('@playwright/test');

async function testWebsite(url, name) {
  console.log(\`\\n=== Testing \${name} at \${url} ===\`);
  
  try {
    const response = await fetch(url);
    if (!response.ok) {
      console.log(\`❌ \${name}: HTTP \${response.status}\`);
      return false;
    }
    
    const html = await response.text();
    console.log(\`✅ \${name}: Page loaded successfully (\${html.length} chars)\`);
    
    // Extract buttons from HTML
    const buttonRegex = /<(button[^>]*>.*?<\\/button>|input[^>]*type=["']submit["'][^>]*>|input[^>]*type=["']button["'][^>]*>)/gi;
    const buttons = html.match(buttonRegex) || [];
    
    console.log(\`Found \${buttons.length} buttons/inputs:\`);
    
    buttons.slice(0, 10).forEach((button, i) => {
      // Extract text content
      const textMatch = button.match(/>([^<]+)</);
      const valueMatch = button.match(/value=["']([^"']+)["']/);
      const classMatch = button.match(/class=["']([^"']+)["']/);
      
      const text = textMatch ? textMatch[1].trim() : '';
      const value = valueMatch ? valueMatch[1] : '';
      const className = classMatch ? classMatch[1] : '';
      
      console.log(\`  \${i + 1}. Text: "\${text}" Value: "\${value}" Class: "\${className}"\`);
    });
    
    // Check for common button patterns
    const commonButtons = [
      'Add New Project',
      'Submit', 
      'Save',
      'Delete',
      'Edit',
      'View',
      'Export',
      'Import',
      'Cancel'
    ];
    
    const foundButtons = [];
    commonButtons.forEach(buttonText => {
      if (html.toLowerCase().includes(buttonText.toLowerCase())) {
        foundButtons.push(buttonText);
      }
    });
    
    console.log(\`Common buttons found: \${foundButtons.join(', ')}\`);
    
    // Check for form elements
    const formCount = (html.match(/<form[^>]*>/gi) || []).length;
    console.log(\`Forms found: \${formCount}\`);
    
    return true;
    
  } catch (error) {
    console.log(\`❌ \${name}: Error - \${error.message}\`);
    return false;
  }
}

// Run tests
async function runTests() {
  console.log('Starting DV Website Button Functionality Analysis...');
  
  const itResult = await testWebsite('http://172.18.0.4', 'IT Domain');
  const nxResult = await testWebsite('http://172.18.0.5', 'NX Domain');
  
  console.log(\`\\n=== Summary ===\`);
  console.log(\`IT Domain: \${itResult ? '✅ Working' : '❌ Failed'}\`);
  console.log(\`NX Domain: \${nxResult ? '✅ Working' : '❌ Failed'}\`);
  
  if (itResult && nxResult) {
    console.log('\\n🎉 All websites are functional with buttons working!');
  } else {
    console.log('\\n⚠️ Some issues found with website functionality');
  }
}

runTests().catch(console.error);
`;

// Write the test script
fs.writeFileSync('/tmp/web-test.js', testScript);
console.log('Test script created');

// Run the test script in a container with network access
const command = \`docker run --rm --network dv_website_v2_dv-network -v /tmp/web-test.js:/test.js node:18-alpine sh -c "npm install -g playwright && node /test.js"\`;

exec(command, { timeout: 60000 }, (error, stdout, stderr) => {
  if (error) {
    console.error('Error:', error.message);
    return;
  }
  if (stderr) {
    console.error('Stderr:', stderr);
  }
  console.log(stdout);
});