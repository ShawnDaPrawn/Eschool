import os
from pathlib import Path
import tempfile
import unittest

from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey

from nexencoder.ev2 import EncoderError
from nexencoder.storage import Outbox, generate_key, load_key, private_read
from tests.fixtures import PLAN, values


class PrivateStorage(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.path = Path(self.temporary.name)
        self.key = X25519PrivateKey.generate()
        self.plan = values()[2]
        self.body = dict(operation_id="a725c552-82c7-417a-acd5-5ac6a23b18dd", picc="A9" * 16, mac="B8" * 8)

    def box(self, key=None, origin="https://school.example"):
        return Outbox(self.path / "receipts", key or self.key, origin, PLAN)

    def test_encrypted_pkcs8_owner_only_no_overwrite_wrong_password_refused(self):
        path = self.path / "private.pem"
        public = generate_key(path, b"synthetic-long-passphrase")
        self.assertTrue(path.read_bytes().startswith(b"-----BEGIN ENCRYPTED PRIVATE KEY-----"))
        self.assertEqual(path.stat().st_mode & 0o777, 0o600)
        self.assertEqual(load_key(path, b"synthetic-long-passphrase").public_key().public_bytes_raw(),
                         __import__("base64").b64decode(public))
        with self.assertRaises(FileExistsError):
            generate_key(path, b"synthetic-long-passphrase")
        with self.assertRaises(EncoderError):
            load_key(path, b"wrong-password")

    def test_plaintext_pkcs8_and_group_readable_key_refused(self):
        path = self.path / "key.pem"
        path.write_bytes(self.key.private_bytes(serialization.Encoding.PEM, serialization.PrivateFormat.PKCS8,
                                                serialization.NoEncryption()))
        path.chmod(0o600)
        with self.assertRaises(EncoderError):
            load_key(path, b"passphrase")
        path.chmod(0o640)
        with self.assertRaises(EncoderError):
            private_read(path)

    def test_outbox_is_encrypted_and_exact_body_survives_restart(self):
        box = self.box()
        box.save_new(self.plan.binding, self.body)
        raw = box.path.read_bytes()
        for value in self.body.values():
            self.assertNotIn(value.encode(), raw)
        self.assertEqual(box.path.stat().st_mode & 0o777, 0o600)
        recovered = self.box().load()
        self.assertEqual(recovered, dict(binding=self.plan.binding, body=self.body))
        with self.assertRaises(EncoderError):
            box.save_new(self.plan.binding, {**self.body, "operation_id": PLAN})
        self.assertEqual(self.box().load(), recovered)

    def test_outbox_rejects_different_key_origin_ciphertext_and_permissions(self):
        box = self.box()
        box.save_new(self.plan.binding, self.body)
        for other in (self.box(X25519PrivateKey.generate()), self.box(origin="https://other.example")):
            with self.assertRaises(EncoderError):
                other.load()
        box.path.chmod(0o644)
        with self.assertRaises(EncoderError):
            box.load()
        box.path.chmod(0o600)
        raw = box.path.read_bytes()
        box.path.write_bytes(raw.replace(b'"v":1', b'"v":2'))
        with self.assertRaises(EncoderError):
            box.load()

    @unittest.skipIf(os.name == "nt", "POSIX symlink/ownership qualification")
    def test_symlinks_hardlinks_and_public_directories_refused(self):
        original = self.path / "original"
        original.write_bytes(b"synthetic")
        original.chmod(0o600)
        alias = self.path / "alias"
        alias.symlink_to(original)
        with self.assertRaises(OSError):
            private_read(alias)
        alias.unlink()
        os.link(original, alias)
        with self.assertRaises(EncoderError):
            private_read(original)
        public = self.path / "public"
        public.mkdir(mode=0o755)
        public.chmod(0o755)
        with self.assertRaises(EncoderError):
            Outbox(public, self.key, "https://school.example", PLAN)


if __name__ == "__main__":
    unittest.main()
