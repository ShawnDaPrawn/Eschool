import base64
import copy
import json
import os
from pathlib import Path
import subprocess
import unittest
from unittest.mock import patch

from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey

from nexencoder.ev2 import EncoderError
from nexencoder.plan import SUITE, b64, open_plan, unique_json
from tests.fixtures import PLAN, sealed, values
from tests.emulator import Card
from nexencoder.personalize import Personalizer


class EnvelopeBoundaries(unittest.TestCase):
    def setUp(self):
        self.key = X25519PrivateKey.generate()
        self.context, self.payload, self.plan, _ = values()

    def packet(self):
        return sealed(self.key, self.context, self.payload)

    def test_valid_purpose_context_full_profile_and_secret_repr(self):
        plan = open_plan(self.packet(), self.key, PLAN)
        self.assertEqual(plan.binding, self.plan.binding)
        self.assertEqual(plan.keys, self.plan.keys)
        self.assertEqual(plan.ndef, self.plan.ndef)
        self.assertNotIn(self.payload["keys"][0], repr(plan))
        self.assertNotIn(self.payload["uid"], repr(plan))

    def test_actual_php_sodium_hkdf_openssl_cross_runtime_random_packet(self):
        php = os.environ.get("CARD_INTEROP_PHP")
        if not php:
            self.skipTest("Set CARD_INTEROP_PHP to PHP8.3 for actual server interoperability; no hardware needed.")
        script = Path(__file__).resolve().parents[4] / "tests/Support/card-encoding-interop.php"
        response = subprocess.run([php, str(script)], input=json.dumps({"public_key": base64.b64encode(self.key.public_key().public_bytes_raw()).decode()}).encode(),
                                  capture_output=True, timeout=15, check=False)
        self.assertEqual(response.returncode, 0, "Actual PHP cipher bridge must run without database or diagnostic output")
        self.assertEqual(response.stderr, b"")
        result = unique_json(response.stdout)
        plan = open_plan(result["envelope"], self.key, result["envelope"]["context"]["plan_id"])
        self.assertEqual(plan.uid.hex().upper(), result["payload"]["uid"])
        self.assertEqual([k.hex() for k in plan.keys], result["payload"]["keys"])
        self.assertEqual(plan.ndef, base64.b64decode(result["payload"]["ndef"]))

    def test_actual_emulator_personalization_final_sun_accepted_by_php_verifier(self):
        php = os.environ.get("CARD_INTEROP_PHP")
        if not php:
            self.skipTest("Set CARD_INTEROP_PHP for actual final PHP SUN verification.")
        _, _, plan, old = values()
        card = Card(plan.uid, old)
        proof, counter = Personalizer(card, plan).run(old)
        script = Path(__file__).resolve().parents[4] / "tests/Support/card-encoding-sun-verify.php"
        packet = {"uid": plan.uid.hex().upper(), "metadata_key": plan.keys[2].hex(),
                  "file_read_key": plan.keys[1].hex(), **proof}
        response = subprocess.run([php, str(script)], input=json.dumps(packet).encode(),
                                  capture_output=True, timeout=15, check=False)
        self.assertEqual(response.returncode, 0, "Actual PHP must verify the coherent emulator-produced final SUN")
        self.assertEqual(response.stderr, b"")
        self.assertEqual(unique_json(response.stdout), {"counter": counter})
        self.assertEqual(card.writes, 2)
        self.assertEqual(card.changes, [1, 2, 3, 4, 0])

    def test_modified_aad_cipher_or_auth_fields_refused(self):
        for field in ("salt", "nonce", "tag", "ciphertext", "ephemeral_public_key"):
            with self.subTest(field=field):
                packet = self.packet()
                raw = bytearray(base64.b64decode(packet[field]))
                raw[0] ^= 1
                packet[field] = base64.b64encode(raw).decode()
                with self.assertRaises(EncoderError):
                    open_plan(packet, self.key, PLAN)
        packet = self.packet()
        packet["context"]["school_id"] += 1
        with self.assertRaises(EncoderError):
            open_plan(packet, self.key, PLAN)

    def test_wrong_key_purpose_plan_and_context_order_refused(self):
        for mutation in (lambda p: p.update(suite="X25519-HKDF-SHA256-A256GCM"),
                         lambda p: p.update(context=dict(reversed(list(p["context"].items())))),
                         lambda p: p.update(extra="not-allowed")):
            packet = self.packet()
            mutation(packet)
            with self.assertRaises(EncoderError):
                open_plan(packet, self.key, PLAN)
        with self.assertRaises(EncoderError):
            open_plan(self.packet(), X25519PrivateKey.generate(), PLAN)
        with self.assertRaises(EncoderError):
            open_plan(self.packet(), self.key, "00000000-0000-4000-8000-000000000000")

    def test_decrypted_school_actor_source_version_and_key_controls(self):
        mutations = [dict(school_id=True), dict(school_id=8), dict(card_revision=True), dict(card_revision=4),
                     dict(confirmed=1), dict(v=True), dict(key_version=2), dict(uid="04de5f1eacc040"),
                     dict(keys=["00" * 16] * 5), dict(keys=self.payload["keys"][:4]),
                     dict(ndef=base64.b64encode(bytes(256)).decode()), dict(settings="00" * 15),
                     dict(unexpected="no"), dict(plan_id="00000000-0000-4000-8000-000000000000")]
        for change in mutations:
            with self.subTest(fields=list(change)):
                payload = {**self.payload, **change}
                with self.assertRaises(EncoderError):
                    open_plan(sealed(self.key, self.context, payload), self.key, PLAN)

    def test_expiry_future_skew_and_exact_600_second_lifetime(self):
        packet = self.packet()
        with self.assertRaises(EncoderError):
            open_plan(packet, self.key, PLAN, now=self.context["expires_at"])
        with self.assertRaises(EncoderError):
            open_plan(packet, self.key, PLAN, now=self.context["issued_at"] - 31)
        self.context["expires_at"] += 1
        with self.assertRaises(EncoderError):
            open_plan(self.packet(), self.key, PLAN)

    def test_backwards_wall_clock_does_not_extend_opened_lease(self):
        plan = open_plan(self.packet(), self.key, PLAN)
        with patch("nexencoder.plan.time.time", return_value=self.context["issued_at"]), \
             patch("nexencoder.plan.time.monotonic", return_value=plan._deadline + 1):
            with self.assertRaises(EncoderError):
                plan.alive()

    def test_canonical_base64_duplicate_json_and_bounded_fields(self):
        for bad in ("YQ", "YQ==\n", "YR==", "A" * 17000):
            with self.assertRaises(EncoderError):
                b64(bad)
        for bad in (b'{"a":1,"a":2}', b'{"a":NaN}', b'"\xff"'):
            with self.assertRaises(EncoderError):
                unique_json(bad)


if __name__ == "__main__":
    unittest.main()
