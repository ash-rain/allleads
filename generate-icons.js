#!/usr/bin/env node
/**
 * generate-icons.js
 * Generates PWA icons from a source SVG/PNG using the `sharp` library.
 *
 * Usage:
 *   node generate-icons.js public/icons/source.png
 *
 * Install once: npm install --save-dev sharp
 */
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const src = process.argv[2] || path.join(__dirname, 'public/icons/source.png');
const dest = path.join(__dirname, 'public/icons');

if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
}

async function generate() {
    await sharp(src).resize(192, 192).toFile(path.join(dest, 'icon-192.png'));
    await sharp(src).resize(512, 512).toFile(path.join(dest, 'icon-512.png'));

    // Maskable: add 20 % safe-zone padding on a brand-navy background
    await sharp({
        create: {
            width: 512,
            height: 512,
            channels: 4,
            background: { r: 0, g: 30, b: 90, alpha: 1 },
        },
    })
        .composite([{
            input: await sharp(src).resize(410, 410).toBuffer(),
            gravity: 'centre',
        }])
        .toFile(path.join(dest, 'icon-maskable-512.png'));

    console.log('✅  PWA icons generated in', dest);
}

generate().catch((err) => {
    console.error('❌  Icon generation failed:', err.message);
    process.exit(1);
});
