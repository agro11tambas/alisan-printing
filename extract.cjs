const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = dir + '/' + file;
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(file));
        } else if(file.endsWith('.blade.php')) {
            results.push(file);
        }
    });
    return results;
}

const files = walk('resources/views');
const paddings = new Set();
const margins = new Set();

files.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    const pMatches = content.match(/padding:\s*[^;"']+/g);
    if(pMatches) pMatches.forEach(m => paddings.add(m));
    const mMatches = content.match(/margin:\s*[^;"']+/g);
    if(mMatches) mMatches.forEach(m => margins.add(m));
    
    // Also capture Bootstrap classes like p-4, m-4, py-5 etc.
    // Wait, let's just output the inline styles first.
});

console.log('Paddings:');
console.log(Array.from(paddings).join('\n'));
console.log('---');
console.log('Margins:');
console.log(Array.from(margins).join('\n'));
