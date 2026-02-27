import { readFile, writeFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const ROOT = dirname(dirname(fileURLToPath(import.meta.url)));
const pkg = JSON.parse(await readFile(join(ROOT, 'package.json'), 'utf8'));
const version = pkg.version;

const mainFile = join(ROOT, 'woo-wise-transfer.php');
let content = await readFile(mainFile, 'utf8');

// Update plugin header: " * Version: x.x.x"
content = content.replace(
  /^(\s*\*\s*Version:\s*).+$/m,
  `$1${version}`
);

// Update define: define( 'WOO_WISE_TRANSFER_VERSION', 'x.x.x' );
content = content.replace(
  /(define\(\s*'WOO_WISE_TRANSFER_VERSION',\s*')[\d.]+('\s*\))/,
  `$1${version}$2`
);

await writeFile(mainFile, content);
console.log(`  Version synced to ${version}`);
