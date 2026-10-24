from pathlib import Path
import json
import os
from playwright.sync_api import sync_playwright

evidence = Path(os.environ.get('DOCS_UI_EVIDENCE', '/tmp/navigateed-docs-ui'))
evidence.mkdir(parents=True, exist_ok=True)
base = os.environ.get('DOCS_TEST_URL', 'http://127.0.0.1:3010/').rstrip('/') + '/'
errors = []
checks = []
with sync_playwright() as p:
    executable = os.environ.get('DOCS_CHROMIUM_EXECUTABLE')
    browser = p.chromium.launch(**({'executable_path': executable} if executable else {}))
    for mode in ['light', 'dark']:
        for width in [320, 390, 1024, 1440]:
            context = browser.new_context(viewport={'width': width, 'height': 1000}, color_scheme=mode)
            page = context.new_page()
            page.on('pageerror', lambda e: errors.append(str(e)))
            response = page.goto(base, wait_until='networkidle')
            assert response.status == 200
            page.evaluate('document.fonts.ready')
            assert page.locator('h1').count() == 1
            assert page.locator('html').get_attribute('data-theme') == mode
            assert page.evaluate("Array.from(document.fonts).some(font => font.family.includes('Poppins') && font.status === 'loaded')"), 'Poppins font must actually load'
            assert page.evaluate('Array.from(document.images).every(img => img.complete && img.naturalWidth > 0)')
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth'), f'Homepage overflow {width} {mode}'
            page.screenshot(path=str(evidence / f'home-{width}-{mode}.png'), full_page=True)
            if width == 1440:
                page.get_by_role('link', name='Find your guide', exact=True).click()
                assert page.url.endswith('#guides')
            if width == 390:
                page.get_by_role('button', name='Toggle navigation bar', exact=True).click()
                page.locator('.navbar-sidebar').get_by_role('link', name='Admin panel', exact=True).click()
                page.wait_for_url(base + 'admin-panel/')
            else:
                page.goto(base + 'admin-panel/', wait_until='networkidle')
            assert page.locator('main h1').count() == 1
            assert not page.evaluate('document.documentElement.scrollWidth > innerWidth'), f'Article overflow {width} {mode}'
            page.screenshot(path=str(evidence / f'article-{width}-{mode}.png'), full_page=True)
            checks.append({'width': width, 'mode': mode, 'status': 'passed'})
            context.close()
    page = browser.new_page(viewport={'width': 1440, 'height': 1000})
    page.on('pageerror', lambda e: errors.append(str(e)))
    page.goto(base, wait_until='networkidle')
    initial_theme = page.locator('html').get_attribute('data-theme')
    for _ in range(3):
        page.locator('button[aria-label*="Switch between dark and light mode"]').first.click()
        if page.locator('html').get_attribute('data-theme') != initial_theme:
            break
    assert page.locator('html').get_attribute('data-theme') != initial_theme
    for route in ['mobile-app/', 'web-page/', 'features/', 'faqs/', 'support/', 'changelog/']:
        response = page.goto(base + route, wait_until='networkidle')
        assert response.status == 200, route
        assert page.locator('main h1').count() == 1, route
    page.goto(base + 'search/?q=attendance', wait_until='networkidle')
    page.get_by_role('link', name='Attendance', exact=True).first.wait_for(state='visible')
    page.screenshot(path=str(evidence / 'search.png'), full_page=True)
    browser.close()
assert not errors, errors
print(json.dumps({'checks': checks, 'search': 'passed', 'runtime_errors': errors}))
