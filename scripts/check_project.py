#!/usr/bin/env python3
"""Non-mutating lint and local link/asset checks."""
import html
from pathlib import Path
import re
import subprocess
from urllib.parse import unquote, urlsplit

ROOT = Path(__file__).resolve().parents[1]
php_files = [p for p in ROOT.rglob('*.php') if not any(x in p.parts for x in ['node_modules', '.git', '_unused'])]
for path in php_files:
    result = subprocess.run(['/usr/bin/php8.3', '-l', str(path)], capture_output=True, text=True)
    if result.returncode:
        raise SystemExit(result.stdout + result.stderr)
sources = list(ROOT.glob('*.php')) + list((ROOT/'includes').rglob('*.php')) + list(ROOT.glob('*.html')) + list((ROOT / 'assets/css').glob('*.css'))
links = 0
for path in sources:
    text = path.read_text()
    assert not re.search(r'/(Admin|Teacher|Student)/(FrontEnd|BackEnd)/', text), path
    refs = re.findall(r'''(?:href|src|action)=["']([^"']+)''', text)
    refs += re.findall(r'''url\(["']?([^\)"']+)''', text)
    for ref in refs:
        ref = html.unescape(ref)
        if any(marker in ref for marker in ['<?', '$', 'https:', 'http:', '#', 'data:']):
            continue
        target = unquote(urlsplit(ref).path)
        if not target:
            continue
        resolved = ROOT / target.lstrip('/') if target.startswith('/') else path.parent / target
        assert resolved.exists(), f'{path.relative_to(ROOT)}: missing {ref}'
        links += 1
print(f'PASS: {len(php_files)} PHP files linted; {links} local links/assets resolved.')
catalogue = __import__('json').loads((ROOT/'includes/catalogue.json').read_text())
assert len(catalogue)==8 and sum(map(len,catalogue.values()))==89, 'A subject was lost from the catalogue.'
assert 'csrf_token' in (ROOT/'includes/bootstrap.php').read_text(), 'Missing shared CSRF validation.'
assert 'display_errors=0' in (ROOT/'package.json').read_text(), 'Development server must hide raw errors.'
assert (ROOT/'assets/css/app.css').exists() and (ROOT/'assets/js/app.js').exists()
print('PASS: 89 subjects preserved; shared CSRF validation and friendly-error server settings present.')
