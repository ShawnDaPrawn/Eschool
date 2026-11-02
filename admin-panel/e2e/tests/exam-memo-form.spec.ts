import { readFileSync } from 'node:fs';
import path from 'node:path';
import { expect, test } from '@playwright/test';

// Synthetic transport regression: no account, application data or provider calls.
test('a full memo posts one field without truncating or disabling the editor', async ({ page }) => {
  await page.route('https://memo.invalid/**', route => route.fulfill({ status: 204 }));
  const controls = ['earns', 'type', 'marks', 'rule_kind', 'rule_value', 'rule_tolerance', 'rule_unit'];
  let html = '<iframe name="memo-test" hidden></iframe><form target="memo-test" method="post" action="https://memo.invalid/save" data-exam-memo-form><input name="_token" value="synthetic"><input name="expected_revision" value="7"><input type="hidden" name="memo_payload" value="">';
  for (let i = 0; i < 20; i++) {
    html += `<input name="questions[${i}][number]" value="${i + 1}">`;
    for (let j = 0; j < 20; j++) {
      for (const field of controls) html += `<input name="questions[${i}][points][${j}][${field}]" value="${field === 'marks' ? '1' : 'synthetic'}">`;
    }
  }
  html += '<button>Save</button></form>';
  await page.setContent(html);
  await page.addScriptTag({ content: readFileSync(path.resolve(process.cwd(), '../public/assets/js/custom/exam-memo-form.js'), 'utf8') });
  for (const lastValue of ['Final mark point', 'Corrected final mark point']) {
    await page.locator('[name="questions[19][points][19][earns]"]').fill(lastValue);
    const sent = page.waitForRequest(request => request.url() === 'https://memo.invalid/save');
    await page.getByRole('button', { name: 'Save', exact: true }).click();
    const body = new URLSearchParams((await sent).postData()!);
    expect([...body.keys()].sort()).toEqual(['_token', 'expected_revision', 'memo_payload']);
    expect(body.get('expected_revision')).toBe('7');
    const questions = JSON.parse(body.get('memo_payload')!);
    expect(questions).toHaveLength(20);
    expect(questions.map((question: { points: unknown[] }) => question.points.length)).toEqual(Array(20).fill(20));
    expect(questions[19].points[19].earns).toBe(lastValue);
    await expect(page.locator('[name="questions[19][points][19][earns]"]')).toBeEnabled();
  }
});
