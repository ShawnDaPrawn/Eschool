import AxeBuilder from '@axe-core/playwright';
import type { Page } from '@playwright/test';
import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';

/**
 * Known problems on legacy pages that this phase does not fix, each with the
 * reason it is accepted. Kept in allowlist.json and checked in; never add an
 * entry without a reason. New pages (on the page pattern) must not need one.
 */
type Entry = { pages: string[]; reason: string; rule?: string; contains?: string };
type Allowlist = { axe: Entry[]; console: Entry[]; scroll: Entry[] };

export const allowlist: Allowlist = JSON.parse(readFileSync(path.join(__dirname, '..', 'allowlist.json'), 'utf8'));

function matches(pattern: string, uri: string): boolean {
  const re = new RegExp('^' + pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
  return re.test(uri);
}

export function allowedAxe(uri: string, rule: string): Entry | undefined {
  return allowlist.axe.find((e) => e.rule === rule && e.pages.some((p) => matches(p, uri)));
}

export function allowedConsole(uri: string, text: string): Entry | undefined {
  return allowlist.console.find((e) => !!e.contains && text.includes(e.contains) && e.pages.some((p) => matches(p, uri)));
}

export function allowedScroll(uri: string): Entry | undefined {
  return allowlist.scroll.find((e) => e.pages.some((p) => matches(p, uri)));
}

/** The panel pages global-setup.ts found. */
export function panelPages(): string[] {
  const file = path.join(__dirname, '..', '.cache', 'panel-pages.json');
  return existsSync(file) ? (JSON.parse(readFileSync(file, 'utf8')) as string[]) : [];
}

/**
 * Collects JavaScript errors and console errors from the panel's own origin.
 * Requests to other hosts (the jQuery Validate translations on cdnjs) are left
 * out: a sandbox without internet would otherwise fail every page.
 */
export function collectErrors(page: Page, baseURL: string): string[] {
  const origin = new URL(baseURL).origin;
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(`JavaScript error: ${error.message}`));
  page.on('console', (message) => {
    if (message.type() !== 'error') {
      return;
    }
    const where = message.location().url;
    // Failed requests are reported below with their address (own origin only).
    if ((where && !where.startsWith(origin)) || message.text().startsWith('Failed to load resource')) {
      return;
    }
    errors.push(`console: ${message.text()}${where ? ` (${where})` : ''}`);
  });
  page.on('response', (response) => {
    if (response.url().startsWith(origin) && response.status() >= 400) {
      errors.push(`HTTP ${response.status()}: ${response.url().slice(origin.length)}`);
    }
  });
  return errors;
}

/** Horizontal overflow of the page in pixels (0 when nothing scrolls sideways). */
export async function horizontalOverflow(page: Page): Promise<number> {
  return page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
}

/** Serious and critical axe violations (WCAG 2.1 A and AA). */
export async function seriousViolations(page: Page): Promise<{ id: string; impact: string; help: string; targets: string[] }[]> {
  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();

  return results.violations
    .filter((v) => v.impact === 'serious' || v.impact === 'critical')
    .map((v) => ({ id: v.id, impact: v.impact ?? '', help: v.help, targets: v.nodes.slice(0, 5).map((n) => n.target.join(' ')) }));
}
