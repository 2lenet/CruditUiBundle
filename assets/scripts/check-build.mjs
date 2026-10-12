// Fails when assets/dist/ui.css is not the compilation of assets/styles: dist/ is committed, because the
// bundle ships the compiled CSS (no build step in the projects).
import {readFileSync} from 'node:fs';
import {execFileSync} from 'node:child_process';

const compiled = execFileSync('npx', ['sass', '--no-source-map', '--style=compressed', 'styles/ui.scss'], {encoding: 'utf8'});

if (compiled.trim() !== readFileSync('dist/ui.css', 'utf8').trim()) {
    console.error('dist/ui.css is out of date: run `npm run build` in assets/ and commit the result.');
    process.exit(1);
}
console.log('dist/ui.css is up to date.');
