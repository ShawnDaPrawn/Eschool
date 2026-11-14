import { execFileSync } from 'node:child_process';
import path from 'node:path';

/** Explicitly enable optional modules in the guarded synthetic school only. */
export function prepareUiReworkSchool(): void {
  execFileSync('php', ['artisan', 'tinker', '--execute', "require base_path('e2e/support/ui-rework-fixture.php');"], {
    cwd: path.resolve(__dirname, '../..'), encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'],
  });
}

/** Source-authorized task links include parameterized configuration modes. */
export function panelTaskDestinations(): string[] {
  const result = execFileSync('php', ['artisan', 'tinker', '--execute', "echo json_encode(require base_path('e2e/support/ui-rework-destinations.php'));"], {
    cwd: path.resolve(__dirname, '../..'), encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'],
  });
  return (JSON.parse(result.trim()) as string[]).map(value => {
    const url = new URL(value, 'http://localhost');
    return url.pathname + url.search;
  });
}
