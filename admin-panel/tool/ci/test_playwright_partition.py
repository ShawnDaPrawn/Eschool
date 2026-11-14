import contextlib
import io
import json
import subprocess
import unittest
from unittest.mock import patch

import playwright_partition as partition


class PlaywrightPartitionTest(unittest.TestCase):
    def test_commands_continue_after_failure_and_keep_separate_artifacts(self):
        for codes in ((1, 0), (0, 2), (0, 0)):
            with self.subTest(codes=codes), \
                    patch.object(partition, "commands", return_value=[["pages"], ["flows"]]), \
                    patch.object(partition.subprocess, "run", side_effect=[
                        subprocess.CompletedProcess([], code) for code in codes
                    ]) as run, contextlib.redirect_stdout(io.StringIO()):
                self.assertEqual(partition.run_part("1"), int(any(codes)))
                self.assertEqual(run.call_count, 2)
                output_directories = []
                report_directories = []
                for call in run.call_args_list:
                    self.assertEqual(call.kwargs["cwd"], partition.E2E)
                    output_directories.append(call.args[0][-1])
                    report_directories.append(call.kwargs["env"]["PLAYWRIGHT_HTML_OUTPUT_DIR"])
                self.assertEqual(len(set(output_directories)), 2)
                self.assertEqual(len(set(report_directories)), 2)
                self.assertTrue(all(value.startswith("--output=test-results/") for value in output_directories))
                self.assertTrue(all(value.startswith(str(partition.E2E / "playwright-report"))
                                    for value in report_directories))

    def test_verification_rejects_command_overlap_part_overlap_and_missing_tests(self):
        a, b, c = (("flows", name) for name in ("a", "b", "c"))
        cases = [
            ([{a, b, c}, {a}, {a}, {b, c}], "commands overlap within part"),
            ([{a, b, c}, {a}, {b}, {b, c}], "parts overlap"),
            ([{a, b, c}, {a}, {b}, set()], "1 missing, 0 extra"),
            ([{a, b}, {a}, {b}, {c}], "0 missing, 1 extra"),
        ]
        for inventories, error in cases:
            with self.subTest(error=error), \
                    patch.object(partition, "require_prepared"), \
                    patch.object(partition, "PARTS", ("1", "2")), \
                    patch.object(partition, "commands", side_effect=[[["one"], ["two"]], [["three"]]]), \
                    patch.object(partition, "inventory", side_effect=inventories):
                with self.assertRaisesRegex(RuntimeError, error):
                    partition.verify()

    def test_verification_unions_every_command_before_accepting_complete_coverage(self):
        a, b, c = (("flows", name) for name in ("a", "b", "c"))
        output = io.StringIO()
        with patch.object(partition, "require_prepared"), \
                patch.object(partition, "PARTS", ("1", "2")), \
                patch.object(partition, "commands", side_effect=[[["one"], ["two"]], [["three"]]]), \
                patch.object(partition, "inventory", side_effect=[{a, b, c}, {a}, {b}, {c}]), \
                contextlib.redirect_stdout(output):
            partition.verify()
        result = json.loads(output.getvalue())
        self.assertTrue(result["complete"] and result["disjoint"])
        self.assertEqual(result["tests"], 3)
        self.assertEqual(result["parts"], {"1": 2, "2": 1})


if __name__ == "__main__":
    unittest.main()
