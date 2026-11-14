#!/usr/bin/env python3
"""Run complete Playwright groups on independent seeded databases.

Distribute the slower workflows across all four independent schemas, alongside
one complete page viewport per part. Playwright keeps serial groups together;
the year-changing wizard has its own schema and does not rerun dependencies.
"""
import argparse
import collections
import json
import os
from pathlib import Path
import subprocess
import sys

PANEL = Path(__file__).resolve().parents[2]
E2E = PANEL / "e2e"
CLI = E2E / "node_modules/.bin/playwright"
PREPARED = "--config=playwright.partition.config.ts"
PAGE_PROJECTS = {"1": "pages-1280", "2": "pages-1024", "3": "pages-375"}
PARTS = ("1", "2", "3", "4", "wizard")


def commands(part):
    base = [str(CLI), "test", PREPARED]
    if part == "wizard":
        return [base + ["--project=wizard", "--no-deps"]]
    pages_or_roles = (["--project=" + PAGE_PROJECTS[part]] if part in PAGE_PROJECTS
                      else ["--project=teacher", "--project=removed"])
    return [base + pages_or_roles,
            base + ["--project=flows", "--shard=" + part + "/4"]]


def run_part(part):
    failed = False
    assigned = commands(part)
    for index, cmd in enumerate(assigned, start=1):
        # Playwright clears each invocation's output directory. Keep earlier
        # failures and traces when the next assigned command still runs.
        artifact = f"part-{part}-{index}"
        env = dict(os.environ, PLAYWRIGHT_HTML_OUTPUT_DIR=str(E2E / "playwright-report" / artifact))
        print(f"Part {part}: command {index}/{len(assigned)}", flush=True)
        result = subprocess.run(cmd + ["--output=test-results/" + artifact], cwd=E2E, env=env)
        failed = result.returncode != 0 or failed
    return int(failed)


def inventory(cmd):
    result = subprocess.run(cmd + ["--list", "--reporter=json"], cwd=E2E,
                            text=True, capture_output=True, check=True)
    report = json.loads(result.stdout)
    found = []

    def visit(suite, ancestors=()):
        # Parameterized describe blocks can contain tests at the same source
        # line with the same leaf title. Their full suite ancestry is part of
        # Playwright's identity (for example, the 320px and 375px workspaces).
        ancestry = ancestors + (suite.get("title", ""),)
        for spec in suite.get("specs", []):
            for test in spec.get("tests", []):
                found.append((test["projectName"], spec["file"], spec["line"],
                              spec["column"], ancestry + (spec["title"],)))
        for child in suite.get("suites", []):
            visit(child, ancestry)

    for suite in report.get("suites", []):
        visit(suite)
    if not found:
        raise RuntimeError("Empty Playwright inventory")
    counts = collections.Counter(found)
    if any(count != 1 for count in counts.values()):
        raise RuntimeError("Duplicate test identity in Playwright inventory")
    return set(found)


def require_prepared():
    pages = json.loads((E2E / ".cache/panel-pages.json").read_text())
    if len(pages) <= 50 or not {"/home", "/search", "/new-year"}.issubset(pages):
        raise RuntimeError("Prepare the real seeded page manifest before partitioning")


def verify():
    require_prepared()
    expected = inventory([str(CLI), "test", PREPARED])
    seen = set()
    sizes = {}
    projects = {}
    for part in PARTS:
        selected = set()
        for cmd in commands(part):
            group = inventory(cmd)
            if selected & group:
                raise RuntimeError("Playwright commands overlap within part: " + part)
            selected |= group
        if seen & selected:
            raise RuntimeError("Playwright parts overlap: " + part)
        seen |= selected
        sizes[part] = len(selected)
        projects[part] = dict(collections.Counter(identity[0] for identity in selected))
    if expected != seen:
        raise RuntimeError("Playwright partition differs from full discovery: "
                           + str(len(expected - seen)) + " missing, "
                           + str(len(seen - expected)) + " extra")
    print(json.dumps({"complete": True, "disjoint": True,
                      "tests": len(expected), "parts": sizes,
                      "projects": projects}, sort_keys=True))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--prepare", action="store_true")
    parser.add_argument("--verify", action="store_true")
    parser.add_argument("--part", choices=PARTS)
    args = parser.parse_args()
    if not args.prepare and not args.verify and not args.part:
        parser.error("Choose --prepare, --verify or --part")
    if args.prepare:
        subprocess.run([str(CLI), "test", "--config=playwright.prepare.config.ts"], cwd=E2E, check=True)
        require_prepared()
    if args.verify:
        verify()
    if args.part:
        require_prepared()
        return run_part(args.part)
    return 0


if __name__ == "__main__":
    sys.exit(main())
