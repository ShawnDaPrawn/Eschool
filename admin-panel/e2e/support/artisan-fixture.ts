import { execFileSync } from 'node:child_process';
import path from 'node:path';

/** Keep a synthetic fixture's actual PHP refusal in the browser failure report. */
export function artisanFixture(code: string, environment: NodeJS.ProcessEnv = {}): string {
  try {
    return execFileSync('php', ['artisan', 'tinker', '--execute', code], {
      cwd: path.resolve(__dirname, '../..'), encoding: 'utf8', env: { ...process.env, ...environment },
    }).trim();
  } catch (error) {
    const result = error as Error & { stdout?: string | Buffer; stderr?: string | Buffer; status?: number };
    const output = [result.stdout, result.stderr].filter(Boolean).map(value => String(value).trim()).join('\n');
    throw new Error(`Synthetic fixture failed (exit ${result.status ?? 'unknown'}): ${output || result.message}`, { cause: error });
  }
}
