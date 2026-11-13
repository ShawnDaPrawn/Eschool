import { expect, test, type Page } from '@playwright/test';

// Isolated component fixture: real shipped CSS/JS, intercepted write responses.
// No application endpoint or school record is created for these checks.
const fixturePath = '/__ui-rework-shared-form';
const submitPath = '/__ui-rework-shared-form-submit';

function markup(scope: string): string {
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="test-fixture-only">
  <link rel="stylesheet" href="/assets/css/m3-tokens.css"><link rel="stylesheet" href="/assets/m3/m3.css"><link rel="stylesheet" href="/assets/css/panel.css">
  <title>Shared form recovery fixture</title></head><body class="m3"><main class="m3-page"><h1>School contact</h1>
  <form id="contact" action="${submitPath}" method="post" data-page-form data-draft="contact" data-draft-scope="${scope}" data-context-scope="${scope}" novalidate>
    <div role="alert" data-form-summary hidden></div>
    <label for="name">Contact name</label><input id="name" name="name" value="Saved contact" required><span data-error-for="name"></span>
    <label><input type="checkbox" name="updates" value="yes" checked>Receive updates</label>
    <label for="groups">Groups</label><select id="groups" name="groups[]" multiple><option value="a" selected>Group A</option><option value="b">Group B</option></select>
    <label for="password">Password</label><input id="password" type="password" name="password">
    <label for="private-note">Private note</label><input id="private-note" name="private_note" data-no-draft>
    <label for="document">Document</label><input id="document" type="file" name="document">
    <input type="hidden" name="_token" value="original-csrf-token"><input type="hidden" name="token" value="hidden-fixture-token"><input disabled name="private_disabled" aria-label="Disabled private value" value="private-disabled-fixture-value">
    <button type="submit" name="decision" value="approve" class="m3-button m3-button--filled" data-submit>Save contact</button>
    <button type="button" id="edit-only">Edit details</button>
  </form>
  <form id="native-contact" method="post" action="/unused-native-fixture"><label for="native-note">Native private note</label><textarea id="native-note" name="private_note"></textarea></form>
  <button type="button" id="change-context" onclick="window.PanelForms.confirmLeave().then(value => { this.dataset.result = String(value); })">Change school or year</button>
  </main><dialog class="m3-dialog page-confirm" id="page-confirm" role="alertdialog" aria-modal="true" aria-labelledby="page-confirm-title" aria-describedby="page-confirm-message">
  <form method="dialog" class="m3-dialog__form" data-year-safe><h2 id="page-confirm-title" class="m3-dialog__headline">Confirm</h2><div class="m3-dialog__content"><p id="page-confirm-message"></p></div>
  <div class="m3-dialog__actions"><button type="submit" value="cancel" data-confirm-cancel>Cancel</button><button type="submit" value="confirm" data-confirm-ok>Confirm</button></div></form></dialog><script src="/assets/m3/m3.js"></script><script src="/assets/js/custom/page.js"></script></body></html>`;
}

async function loadFixture(page: Page, getScope = () => 'user-a:school-a:year-2026:contact') {
  await page.route(`**${fixturePath}`, route => route.fulfill({ contentType: 'text/html', body: markup(getScope()) }));
  await page.goto(fixturePath);
  await expect(page.getByRole('button', { name: 'Save contact' })).toBeEnabled();
}

async function storedDrafts(page: Page): Promise<string> {
  return page.evaluate(() => JSON.stringify(Object.fromEntries(Object.entries(sessionStorage).filter(([key]) => key.startsWith('page-form-draft:')))));
}

test('cleared checkboxes and multiselections stay cleared after reload', async ({ page }) => {
  await loadFixture(page);
  await page.getByLabel('Receive updates').uncheck();
  await page.getByLabel('Groups').selectOption([]);
  await page.reload();
  await expect(page.getByLabel('Receive updates')).not.toBeChecked();
  await expect(page.getByLabel('Groups')).toHaveValues([]);
});

test('draft restoration is isolated by user, school, year and form scope', async ({ page }) => {
  let scope = 'user-a:school-a:year-2026:contact';
  await loadFixture(page, () => scope);
  await page.getByLabel('Contact name').fill('Unsubmitted school A contact');
  for (const other of ['user-a:school-b:year-2026:contact', 'user-a:school-a:year-2027:contact', 'user-b:school-a:year-2026:contact']) {
    scope = other;
    await page.reload();
    await expect(page.getByLabel('Contact name')).toHaveValue('Saved contact');
  }
  scope = 'user-a:school-a:year-2026:contact';
  await page.reload();
  await expect(page.getByLabel('Contact name')).toHaveValue('Unsubmitted school A contact');
});

test('passwords, uploads, hidden values and private fields never enter draft storage', async ({ page }) => {
  await loadFixture(page);
  await page.getByLabel('Password', { exact: true }).fill('private-fixture-password');
  await page.getByLabel('Private note', { exact: true }).fill('private-fixture-reason');
  await page.getByLabel('Document', { exact: true }).setInputFiles({ name: 'private-fixture-file.txt', mimeType: 'text/plain', buffer: Buffer.from('private-fixture-document') });
  await page.getByLabel('Contact name').fill('Draft contact');
  const drafts = await storedDrafts(page);
  expect(drafts).toContain('Draft contact');
  for (const secret of ['private-fixture-password', 'private-fixture-reason', 'private-fixture-file', 'private-fixture-document', 'hidden-fixture-token', 'private-disabled-fixture-value']) expect(drafts).not.toContain(secret);
  await page.reload();
  for (const label of ['Password', 'Private note', 'Document']) await expect(page.getByLabel(label, { exact: true })).toHaveValue('');
});

test('server field validation retains input and identifies the invalid field', async ({ page }) => {
  await page.route(`**${submitPath}`, route => route.fulfill({ status: 422, json: { message: 'Review the contact name.', errors: { name: ['The contact name is not valid.'] } } }));
  await loadFixture(page);
  await page.getByLabel('Contact name').fill('Needs correction');
  await page.getByRole('button', { name: 'Save contact' }).click();
  await expect(page.getByLabel('Contact name')).toHaveValue('Needs correction');
  await expect(page.getByLabel('Contact name')).toHaveAttribute('aria-invalid', 'true');
  await expect(page.getByText('The contact name is not valid.', { exact: true })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Save contact' })).toBeEnabled();
});

for (const status of [401, 419]) {
  test(`expired session (${status}) retains the draft and explains sign-in`, async ({ page }) => {
    await page.route(`**${submitPath}`, route => route.fulfill({ status, json: { message: 'Authentication required.' } }));
    await loadFixture(page);
    await page.getByLabel('Contact name').fill('Retained contact');
    await page.getByRole('button', { name: 'Save contact' }).click();
    await expect(page.getByRole('alert')).toContainText(/session|sign in|sign-in|expired/i);
    await expect(page.getByLabel('Contact name')).toHaveValue('Retained contact');
    await expect(page.getByRole('button', { name: 'Save contact' })).toBeEnabled();
    expect(await storedDrafts(page)).toContain('Retained contact');
  });
}

test('a revision conflict preserves the draft and asks for review', async ({ page }) => {
  await page.route(`**${submitPath}`, route => route.fulfill({ status: 409, json: { message: 'This record changed since it was opened.' } }));
  await loadFixture(page);
  await page.getByLabel('Contact name').fill('My unsaved edit');
  await page.getByRole('button', { name: 'Save contact' }).click();
  await expect(page.getByRole('alert')).toContainText(/changed|conflict|review/i);
  await expect(page.getByLabel('Contact name')).toHaveValue('My unsaved edit');
});

test('a lost response is unconfirmed and never automatically retried', async ({ page }) => {
  let requests = 0;
  await page.route(`**${submitPath}`, async route => { requests += 1; await route.abort('connectionreset'); });
  await loadFixture(page);
  await page.getByLabel('Contact name').fill('Keep until outcome is checked');
  await page.getByRole('button', { name: 'Save contact' }).click();
  await expect(page.getByRole('alert')).toContainText(/confirm|outcome|check.*record/i);
  await expect(page.getByLabel('Contact name')).toHaveValue('Keep until outcome is checked');
  await page.waitForTimeout(250);
  expect(requests).toBe(1);
});

test('repeated submit events cannot send a second in-flight write', async ({ page }) => {
  let requests = 0;
  let release!: () => void;
  const gate = new Promise<void>(resolve => { release = resolve; });
  await page.route(`**${submitPath}`, async route => { requests += 1; await gate; await route.fulfill({ json: { error: false, message: 'Contact saved.' } }); });
  await loadFixture(page);
  await page.getByLabel('Contact name').fill('One save only');
  await page.getByRole('button', { name: 'Save contact' }).click();
  await expect(page.getByRole('button', { name: 'Save contact' })).toBeDisabled();
  await page.locator('#contact').evaluate(form => { (form as HTMLFormElement).requestSubmit(); (form as HTMLFormElement).requestSubmit(); });
  await expect.poll(() => requests).toBe(1);
  release();
  await expect(page.getByRole('button', { name: 'Save contact' })).toBeEnabled();
  expect(requests).toBe(1);
  expect(await storedDrafts(page)).not.toContain('One save only');
});

for (const trigger of ['click', 'enter', 'requestSubmit'] as const) {
  test(`confirmed form ${trigger} preserves the named submitter and submits once`, async ({ page }) => {
    let requests = 0;
    let body = '';
    await page.route(`**${submitPath}`, async route => {
      requests += 1;
      body = route.request().postData() || '';
      await route.fulfill({ json: { error: false, message: 'Contact saved.' } });
    });
    await loadFixture(page);
    await page.locator('#contact').evaluate(form => {
      form.setAttribute('data-confirm', 'Publish this contact for the selected school?');
      form.setAttribute('data-confirm-label', 'Publish contact');
    });
    // Editing a control inside a confirmed form must never ask to submit it.
    await page.getByRole('button', { name: 'Edit details' }).click();
    await expect(page.getByRole('alertdialog')).not.toBeVisible();
    await page.getByLabel('Contact name').fill('Approved contact');
    const submit = async () => {
      if (trigger === 'click') await page.getByRole('button', { name: 'Save contact' }).click();
      else if (trigger === 'enter') await page.getByLabel('Contact name').press('Enter');
      else await page.locator('#contact').evaluate(form => (form as HTMLFormElement).requestSubmit(form.querySelector('[data-submit]') as HTMLButtonElement));
    };
    await submit();
    await expect(page.getByRole('alertdialog')).toBeVisible();
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(page.getByRole('alertdialog')).not.toBeVisible();
    expect(requests).toBe(0);
    await expect(page.getByLabel('Contact name')).toHaveValue('Approved contact');
    await submit();
    await page.getByRole('button', { name: 'Publish contact', exact: true }).click();
    await expect.poll(() => requests).toBe(1);
    await expect(page.getByRole('button', { name: 'Save contact' })).toBeEnabled();
    expect(body).toContain('name="decision"');
    expect(body).toContain('approve');
    expect(body).toContain('Approved contact');
    expect(requests).toBe(1);
  });
}

test('native form context cancellation keeps private values only in memory', async ({ page }) => {
  await loadFixture(page);
  await page.getByLabel('Native private note').fill('Private pastoral note never persisted');
  await page.getByRole('button', { name: 'Change school or year' }).click();
  const dialog = page.getByRole('alertdialog');
  await expect(dialog).toContainText('Your changes have not been saved');
  await dialog.getByRole('button', { name: 'Cancel', exact: true }).click();
  await expect(page.locator('#change-context')).toHaveAttribute('data-result', 'false');
  await expect(page.getByLabel('Native private note')).toHaveValue('Private pastoral note never persisted');
  expect(await storedDrafts(page)).not.toContain('Private pastoral note never persisted');
  await page.locator('#native-contact').evaluate(form => form.dispatchEvent(new CustomEvent('page-form:saved', { bubbles: true })));
  await page.getByRole('button', { name: 'Change school or year' }).click();
  await expect(page.locator('#change-context')).toHaveAttribute('data-result', 'true');
  await expect(dialog).not.toBeVisible();
});

test('a confirmed custom button invokes its action once without submitting its form', async ({ page }) => {
  let writes = 0;
  await page.route(`**${submitPath}`, route => { writes += 1; return route.fulfill({ json: { error: false } }); });
  await loadFixture(page);
  const action = page.locator('#edit-only');
  await action.evaluate(button => {
    button.setAttribute('data-confirm', 'Open the contact editor?');
    button.setAttribute('data-confirm-label', 'Open editor');
    button.addEventListener('click', () => button.setAttribute('data-action-count', String(Number(button.getAttribute('data-action-count') || 0) + 1)));
  });
  await action.click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Cancel', exact: true }).click();
  await expect(action).not.toHaveAttribute('data-action-count');
  await action.click();
  await page.getByRole('alertdialog').getByRole('button', { name: 'Open editor', exact: true }).click();
  await expect(action).toHaveAttribute('data-action-count', '1');
  expect(writes).toBe(0);
});

for (const method of ['GET', 'POST']) {
  test(`submitting a separate ${method} form preserves another native form's unsaved guard`, async ({ page }) => {
    const destination = '/__ui-rework-background-form';
    await page.route(`**${destination}**`, route => route.fulfill({ contentType: 'text/html', body: '<!doctype html><title>Saved fixture</title>' }));
    await loadFixture(page);
    await page.getByLabel('Native private note').fill('Retain the first private form');
    await page.evaluate(({ destination, method }) => {
      const frame = document.createElement('iframe');
      frame.name = 'fixture-background';
      frame.title = 'Synthetic form response';
      frame.hidden = true;
      const form = document.createElement('form');
      form.id = 'independent-form';
      form.method = method;
      form.action = destination;
      form.target = frame.name;
      form.innerHTML = '<label for="independent-value">Independent value</label><input id="independent-value" name="value"><button type="submit">Submit independent form</button>';
      document.body.append(frame, form);
    }, { destination, method });
    await page.getByLabel('Independent value').fill('Separate form data');
    await Promise.all([
      page.waitForResponse(response => new URL(response.url()).pathname === destination),
      page.getByRole('button', { name: 'Submit independent form' }).click(),
    ]);
    await expect(page.getByLabel('Native private note')).toHaveValue('Retain the first private form');
    const state = await page.evaluate(() => {
      const panel = window as unknown as { PanelForms: { hasChanges: () => boolean }; panelNavigationConfirmed?: boolean };
      return { unsaved: panel.PanelForms.hasChanges(), globallyConfirmed: panel.panelNavigationConfirmed === true };
    });
    expect(state).toEqual({ unsaved: true, globallyConfirmed: false });
    expect(await storedDrafts(page)).not.toContain('Retain the first private form');
  });
}


test('expired form refreshes only its token and requires an explicit save', async ({ page }) => {
  let requests = 0;
  let body = '';
  await page.route(`**${submitPath}`, route => {
    requests++;
    body = route.request().postData() || '';
    return route.fulfill({ status: requests === 1 ? 419 : 200, json: requests === 1 ? { message: 'Expired.' } : { error: false, message: 'Saved.' } });
  });
  await loadFixture(page);
  await page.getByLabel('Contact name').fill('Retained after sign-in');
  await page.getByLabel('Private note', { exact: true }).fill('Private in-memory note');
  await page.getByLabel('Document', { exact: true }).setInputFiles({ name: 'evidence.txt', mimeType: 'text/plain', buffer: Buffer.from('Retained attachment') });
  await page.getByRole('button', { name: 'Save contact' }).click();
  await page.route(`**${fixturePath}`, route => route.fulfill({ contentType: 'text/html', body: markup('user-a:school-a:year-2026:contact').replace('original-csrf-token', 'refreshed-csrf-token') }));
  await page.getByRole('button', { name: 'Refresh sign-in for this form' }).click();
  await expect(page.getByRole('alert')).toContainText('Sign-in refreshed.');
  expect(requests).toBe(1);
  await expect(page.getByLabel('Contact name')).toHaveValue('Retained after sign-in');
  await expect(page.getByLabel('Private note', { exact: true })).toHaveValue('Private in-memory note');
  await expect(page.getByLabel('Document', { exact: true })).toHaveValue(/evidence.txt$/);
  expect(await storedDrafts(page)).not.toContain('Private in-memory note');
  await page.getByRole('button', { name: 'Save contact' }).click();
  await expect.poll(() => requests).toBe(2);
  expect(body).toContain('refreshed-csrf-token');
  expect(body).toContain('Retained after sign-in');
  expect(body).toContain('Retained attachment');
});

for (const changedScope of ['user-b:school-a:year-2026:contact', 'user-a:school-b:year-2026:contact', 'user-a:school-a:year-2027:contact']) {
  test(`expired form refuses token refresh after context change: ${changedScope}`, async ({ page }) => {
    let requests = 0;
    await page.route(`**${submitPath}`, route => { requests++; return route.fulfill({ status: 419, json: { message: 'Expired.' } }); });
    let scope = 'user-a:school-a:year-2026:contact';
    await loadFixture(page, () => scope);
    await page.getByLabel('Contact name').fill('Original context entries');
    await page.getByRole('button', { name: 'Save contact' }).click();
    scope = changedScope;
    await page.getByRole('button', { name: 'Refresh sign-in for this form' }).click();
    await expect(page.getByRole('alert')).toContainText('Sign-in could not be refreshed');
    expect(requests).toBe(1);
    await expect(page.locator('#contact input[name="_token"]')).toHaveValue('original-csrf-token');
    await expect(page.getByLabel('Contact name')).toHaveValue('Original context entries');
  });
}
