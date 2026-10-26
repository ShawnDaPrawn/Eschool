"""Partition the configured PHPUnit suites without sharing database or file state."""

import argparse
import copy
from pathlib import Path
import xml.etree.ElementTree as ET


def configurations(source: Path, count: int):
    if count < 1:
        raise ValueError("Shard count must be positive")
    tree = ET.parse(source)
    suites = tree.getroot().find("testsuites")
    if suites is None:
        raise ValueError("No PHPUnit suites configured")
    files = []
    for suite in suites:
        for entry in suite:
            if entry.tag != "directory":
                raise ValueError("Update shard discovery for this PHPUnit suite entry")
            directory = source.parent / entry.text.strip()
            if not directory.is_dir():
                raise ValueError(f"Missing test directory: {directory}")
            suffix = entry.get("suffix", "Test.php")
            files.extend(str(p.relative_to(source.parent)) for p in directory.rglob(f"*{suffix}"))
    files.sort()
    if not files or len(set(files)) != len(files) or len(files) < count:
        raise ValueError("Empty, overlapping or insufficient test inventory")
    partitions = [files[i::count] for i in range(count)]
    if sorted(f for partition in partitions for f in partition) != files:
        raise ValueError("Incomplete test partition")
    results = []
    for partition in partitions:
        root = copy.deepcopy(tree.getroot())
        target = root.find("testsuites")
        target.clear()
        suite = ET.SubElement(target, "testsuite", {"name": "CI shard"})
        for filename in partition:
            ET.SubElement(suite, "file").text = filename
        results.append(ET.ElementTree(root))
    return results


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--shard", type=int, required=True)
    parser.add_argument("--count", type=int, required=True)
    parser.add_argument("--output", type=Path, required=True)
    args = parser.parse_args()
    source = Path("phpunit.xml").resolve()
    if not 1 <= args.shard <= args.count:
        parser.error("Shard must be between 1 and count")
    if args.output.resolve().parent != source.parent or args.output.resolve() == source:
        parser.error("Write the disposable configuration beside, without replacing, phpunit.xml")
    shards = configurations(source, args.count)
    selected = shards[args.shard - 1]
    ET.indent(selected, space="    ")
    selected.write(args.output, encoding="utf-8", xml_declaration=True)
    print(f"Shard {args.shard}/{args.count}: {len(selected.findall('.//testsuite/file'))} test files")


if __name__ == "__main__":
    main()
