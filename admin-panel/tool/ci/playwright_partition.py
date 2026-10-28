#!/usr/bin/env python3
"""Run complete Playwright groups on independent seeded databases.

Four bulk shards retain serial groups; the year-changing wizard gets its own
schema and therefore does not need to rerun every dependency project.
"""
import argparse
import collections
import json
from pathlib import Path
import subprocess
import sys

PANEL = Path(__file__).resolve().parents[2]
E2E = PANEL / "e2e"
CLI = E2E / "node_modules/.bin/playwright"
PREPARED = "--config=playwright.partition.config.ts"
BULK = ("pages-1280", "pages-1024", "flows", "teacher", "removed")
PARTS = ("1", "2", "3", "4", "wizard")


def command(part):
    if part == "wizard":
        return [str(CLI), "test", PREPARED, "--project=wizard", "--no-deps"]
    return [str(CLI), "test", PREPARED, *["--project=" + p for p in BULK], "--shard=" + part + "/4"]


def inventory(cmd):
    result = subprocess.run(cmd + ["--list", "--reporter=json"], cwd=E2E,
                            text=True, capture_output=True, check=True)
    report = json.loads(result.stdout)
    found = []

    def visit(suite):
        for spec in suite.get("specs", []):
            for test in spec.get("tests", []):
                found.append((test["projectName"], spec["file"], spec["line"],
                              spec["column"], spec["title"]))
        for child in suite.get("suites", []):
            visit(child)

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
    for part in PARTS:
        selected = inventory(command(part))
        if seen & selected:
            raise RuntimeError("Playwright parts overlap: " + part)
        seen |= selected
        sizes[part] = len(selected)
    if expected != seen:
        raise RuntimeError("Playwright partition differs from full discovery: "
                           + str(len(expected - seen)) + " missing, "
                           + str(len(seen - expected)) + " extra")
    print(json.dumps({"complete": True, "disjoint": True,
                      "tests": len(expected), "parts": sizes}, sort_keys=True))


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
        return subprocess.run(command(args.part), cwd=E2E).returncode
    return 0


if __name__ == "__main__":
    sys.exit(main())
