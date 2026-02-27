import { createWriteStream } from 'fs';
import { readdir, readFile, stat, unlink } from 'fs/promises';
import { join, dirname, relative } from 'path';
import { fileURLToPath } from 'url';
import archiver from 'archiver';

const PLUGIN_NAME = 'woo-wise-transfer-unofficial';
const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));

const pkg = JSON.parse(await readFile(join(ROOT, 'package.json'), 'utf8'));
const VERSION = pkg.version;
const FILENAME = `${PLUGIN_NAME}-${VERSION}.zip`;
const OUTPUT = join(ROOT, FILENAME);

const IGNORE = new Set([
  '.git',
  '.claude',
  'node_modules',
  'scripts',
  '.gitignore',
  '.DS_Store',
  'CLAUDE.md',
  'package.json',
  'package-lock.json',
  'pnpm-lock.yaml',
]);

try {
  await unlink(OUTPUT);
} catch {}

const output = createWriteStream(OUTPUT);
const archive = archiver('zip', { zlib: { level: 9 } });

archive.pipe(output);

let fileCount = 0;
const filesByType = {};

async function addDir(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    if (IGNORE.has(entry.name) || entry.name.endsWith('.zip')) continue;
    const fullPath = join(dir, entry.name);
    const zipPath = join(PLUGIN_NAME, relative(ROOT, fullPath));
    if (entry.isDirectory()) {
      await addDir(fullPath);
    } else {
      archive.file(fullPath, { name: zipPath });
      fileCount++;
      const ext = entry.name.split('.').pop().toLowerCase();
      filesByType[ext] = (filesByType[ext] || 0) + 1;
    }
  }
}

await addDir(ROOT);
await archive.finalize();

output.on('close', async () => {
  const zipSize = (archive.pointer() / 1024).toFixed(1);

  // Calculate original size of included files
  let originalSize = 0;
  async function sumDir(dir) {
    const entries = await readdir(dir, { withFileTypes: true });
    for (const entry of entries) {
      if (IGNORE.has(entry.name) || entry.name.endsWith('.zip')) continue;
      const fullPath = join(dir, entry.name);
      if (entry.isDirectory()) {
        await sumDir(fullPath);
      } else {
        const s = await stat(fullPath);
        originalSize += s.size;
      }
    }
  }
  await sumDir(ROOT);
  const originalKB = (originalSize / 1024).toFixed(1);
  const ratio = ((1 - archive.pointer() / originalSize) * 100).toFixed(0);

  const types = Object.entries(filesByType)
    .sort((a, b) => b[1] - a[1])
    .map(([ext, count]) => `  .${ext}  ${count}`)
    .join('\n');

  console.log('');
  console.log('  Build complete');
  console.log('  ─────────────────────────────────');
  console.log(`  Plugin     ${PLUGIN_NAME}`);
  console.log(`  Version    ${VERSION}`);
  console.log(`  Files      ${fileCount}`);
  console.log(`  Original   ${originalKB} KB`);
  console.log(`  Compressed ${zipSize} KB (${ratio}% smaller)`);
  console.log('  ─────────────────────────────────');
  console.log(types);
  console.log('  ─────────────────────────────────');
  console.log(`  Output     ${FILENAME}`);
  console.log('');
});
