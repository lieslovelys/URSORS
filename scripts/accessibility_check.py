#!/usr/bin/env python3
"""Read-only Chromium/axe checks. Set AXE_SCRIPT to a locally downloaded axe.min.js.

AXE_SCRIPT=/tmp/opencode/axe.min.js ADMIN_PASSWORD=... python3 scripts/accessibility_check.py
"""
import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

BASE=os.environ.get('BASE_URL','http://localhost:8000').rstrip('/')
AXE=Path(os.environ['AXE_SCRIPT'])
ROOT=Path(__file__).resolve().parents[1]
results=[]
with sync_playwright() as p:
    browser=p.chromium.launch()
    for width in [1280,360]:
        page=browser.new_page(viewport={'width':width,'height':900})
        paths=['login.php','register.php','help.php','missing-page']
        for authenticated in [False,True]:
            if authenticated:
                page.goto(BASE+'/login.php');page.get_by_label('Username',exact=True).fill('admin001');page.get_by_label('Password',exact=True).fill(os.environ['ADMIN_PASSWORD']);page.get_by_role('button',name='Log in',exact=True).click();page.wait_for_url('**/admin_home.php')
                paths=['welcome.php','course_page.php','course_page.php?program=cpe','course_page.php?course=cpe_core','course_page.php?course=cpe_core&subject=Engineering+Economy','feed.php','archived_posts.php','profile.php','notifications.php','ranking_board.php','admin_home.php','manage_accounts.php','admin_pendings.php','admin_register.php','search.php?query=admin']
            for path in paths:
                page.goto(BASE+'/'+path)
                if width==360:
                    page.get_by_role('button',name='Menu',exact=True).click()
                page.add_script_tag(path=str(AXE))
                report=page.evaluate("async () => await axe.run(document, {runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21aa']}})")
                violations=[{'id':v['id'],'impact':v['impact'],'description':v['description'],'targets':[n['target'] for n in v['nodes']]} for v in report['violations']]
                assert not violations,(width,path,json.dumps(violations,indent=2))
                assert page.evaluate('document.documentElement.scrollWidth<=innerWidth'),(width,path)
                results.append(f'{width}px: {path} — no automated WCAG A/AA violations')
        # Keyboard-only skip navigation, modal focus and native validation are covered separately.
        page.close()
    browser.close()
(ROOT/'docs/ACCESSIBILITY_RESULTS.md').write_text('# Automated accessibility checks\n\n'+f'{len(results)} page/viewport checks passed using Chromium and axe-core. Desktop: 1280px. Mobile: 360px. WCAG 2 A/AA and WCAG 2.1 AA tags; no automated violations. Open mobile navigation was included.\n\nThis is not a claim of full WCAG conformance. Human screen-reader, low-vision, and real-device usability checks are still recommended.\n\n'+'\n'.join('- '+x for x in results)+'\n')
print(f'PASS: {len(results)} axe/browser page checks; docs/ACCESSIBILITY_RESULTS.md')
