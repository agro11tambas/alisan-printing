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
let filesModified = 0;

files.forEach(file => {
    let content = fs.readFileSync(file, 'utf8');
    const originalContent = content;

    // 1. Inline styles replacements (padding)
    content = content.replace(/padding:\s*0\.5rem\s+1rem/g, 'padding: 0.25rem 0.5rem');
    content = content.replace(/padding:\s*15px/g, 'padding: 8px');
    content = content.replace(/padding:\s*20px/g, 'padding: 10px');
    content = content.replace(/padding:\s*10px/g, 'padding: 5px');
    content = content.replace(/padding:\s*12px/g, 'padding: 6px');
    content = content.replace(/padding:\s*14px\s+16px/g, 'padding: 8px 10px');
    content = content.replace(/padding:\s*8px\s+12px/g, 'padding: 4px 8px');
    content = content.replace(/padding:\s*8px(?![\s\w])/g, 'padding: 4px');

    // 2. Inline styles replacements (margin)
    content = content.replace(/margin:\s*0\.5rem\s+1rem/g, 'margin: 0.25rem 0.5rem');
    content = content.replace(/margin:\s*15px/g, 'margin: 8px');
    content = content.replace(/margin:\s*20px/g, 'margin: 10px');
    content = content.replace(/margin:\s*10px/g, 'margin: 5px');
    content = content.replace(/margin:\s*12px/g, 'margin: 6px');
    content = content.replace(/margin:\s*14px\s+16px/g, 'margin: 8px 10px');
    content = content.replace(/margin:\s*8px\s+12px/g, 'margin: 4px 8px');
    content = content.replace(/margin:\s*8px(?![\s\w])/g, 'margin: 4px');

    // 3. Utility classes replacements (p-*, m-*, px-*, py-*, pt-*, pb-*, pl-*, pr-*, mx-*, my-*, mt-*, mb-*, ml-*, mr-*)
    // We use a regex with replacer function.
    content = content.replace(/\b([pm][xybtlr]?)-([1-5])\b/g, (match, prefix, numStr) => {
        let num = parseInt(numStr);
        if (num === 5) num = 3;
        else if (num === 4) num = 2;
        else if (num === 3) num = 2;
        else if (num === 2) num = 1;
        // if 1, we keep it 1.
        return `${prefix}-${num}`;
    });

    if (content !== originalContent) {
        fs.writeFileSync(file, content, 'utf8');
        filesModified++;
    }
});

console.log(`Successfully modified ${filesModified} blade files.`);
