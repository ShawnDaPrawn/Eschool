import tempfile
import unittest
from pathlib import Path
import xml.etree.ElementTree as ET

from phpunit_shard import configurations


class ShardInventoryTest(unittest.TestCase):
    def test_every_file_once_and_environment_source_and_bootstrap_preserved(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            for suite in ("Unit", "Feature"):
                (root / suite).mkdir()
                for i in range(7):
                    (root / suite / f"Case{i}Test.php").touch()
            source = root / "phpunit.xml"
            source.write_text('<phpunit bootstrap="vendor/autoload.php"><testsuites>'
                              '<testsuite name="unit"><directory>Unit</directory></testsuite>'
                              '<testsuite name="feature"><directory>Feature</directory></testsuite>'
                              '</testsuites><source><include><directory>app</directory></include></source>'
                              '<php><env name="APP_ENV" value="testing"/></php></phpunit>')
            shards = configurations(source, 4)
            assigned = [f.text for shard in shards for f in shard.findall(".//testsuite/file")]
            self.assertEqual(len(assigned), 14)
            self.assertEqual(len(set(assigned)), 14)
            self.assertEqual(sorted(assigned), sorted(str(p.relative_to(root)) for p in root.rglob("*Test.php")))
            original = ET.parse(source).getroot()
            for shard in shards:
                self.assertEqual(shard.getroot().attrib, original.attrib)
                for node in ("source", "php"):
                    self.assertEqual(ET.tostring(shard.find(node)), ET.tostring(original.find(node)))

    def test_unsupported_discovery_and_duplicate_files_fail_closed(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            source = root / "phpunit.xml"
            (root / "Unit").mkdir()
            (root / "Unit/OneTest.php").touch()
            for entries in ('<file>Unit/OneTest.php</file>', '<directory>Unit</directory><directory>Unit</directory>'):
                source.write_text(f'<phpunit><testsuites><testsuite>{entries}</testsuite></testsuites></phpunit>')
                with self.assertRaises(ValueError):
                    configurations(source, 1)
