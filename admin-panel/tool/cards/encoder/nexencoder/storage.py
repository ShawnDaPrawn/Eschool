"""Private encrypted PKCS8 and crash-safe encrypted exact-confirm outbox.

Chip keys never enter the outbox. It contains only bound SUN/operation values,
encrypted with a key derived from the already encrypted local X25519 key.
"""

import base64
import os
from pathlib import Path
import secrets
import stat

from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.kdf.hkdf import HKDF

from .ev2 import EncoderError
from .plan import b64, canonical, unique_json


def _private_directory(directory):
    path = Path(directory)
    path.mkdir(mode=0o700, parents=True, exist_ok=True)
    status = path.lstat()
    if (not stat.S_ISDIR(status.st_mode) or status.st_mode & 0o077
            or (hasattr(os, "getuid") and status.st_uid != os.getuid())):
        raise EncoderError("The private outbox directory must be owner-only and not a symlink.")
    return path


def private_read(path, maximum=65536):
    descriptor = os.open(path, os.O_RDONLY | getattr(os, "O_NOFOLLOW", 0) | getattr(os, "O_NONBLOCK", 0))
    with os.fdopen(descriptor, "rb") as file:
        status = os.fstat(file.fileno())
        if (not stat.S_ISREG(status.st_mode) or status.st_mode & 0o077 or status.st_nlink != 1
                or status.st_size > maximum or (hasattr(os, "getuid") and status.st_uid != os.getuid())):
            raise EncoderError("Private file must be bounded, owner-only, regular and not hardlinked.")
        result = file.read(maximum + 1)
        if len(result) > maximum:
            raise EncoderError("Private file exceeds its bound.")
        return result


def _write(path, raw, replace=False):
    path = Path(path)
    if not replace:
        flags = os.O_WRONLY | os.O_CREAT | os.O_EXCL | getattr(os, "O_NOFOLLOW", 0)
        with os.fdopen(os.open(path, flags, 0o600), "wb") as file:
            file.write(raw)
            file.flush()
            os.fsync(file.fileno())
    else:
        temporary = path.parent / (".receipt-" + secrets.token_hex(16))
        try:
            _write(temporary, raw)
            os.replace(temporary, path)
        finally:
            temporary.unlink(missing_ok=True)
    if os.name != "nt":
        descriptor = os.open(path.parent, os.O_RDONLY | getattr(os, "O_DIRECTORY", 0))
        try:
            os.fsync(descriptor)
        finally:
            os.close(descriptor)


def generate_key(path, password):
    if not isinstance(password, bytes) or len(password) < 12:
        raise EncoderError("Use a private-key passphrase of at least twelve UTF-8 bytes.")
    key = X25519PrivateKey.generate()
    pem = key.private_bytes(serialization.Encoding.PEM, serialization.PrivateFormat.PKCS8,
                            serialization.BestAvailableEncryption(password))
    _write(path, pem)
    return base64.b64encode(key.public_key().public_bytes_raw()).decode("ascii")


def load_key(path, password):
    raw = private_read(path, 16384)
    if not raw.startswith(b"-----BEGIN ENCRYPTED PRIVATE KEY-----"):
        raise EncoderError("Only encrypted PKCS8 private keys are accepted.")
    try:
        key = serialization.load_pem_private_key(raw, password)
        if not isinstance(key, X25519PrivateKey):
            raise ValueError()
        return key
    except (ValueError, TypeError):
        raise EncoderError("The encrypted X25519 private key could not be unlocked.") from None


class Outbox:
    def __init__(self, directory, private_key, origin, plan):
        from .plan import uuid_text
        if not uuid_text(plan):
            raise EncoderError("Invalid outbox plan binding.")
        self.directory = _private_directory(directory)
        self.path = self.directory / (plan + ".receipt")
        self.key = private_key
        self.aad = canonical({"purpose": "NavigateEd-encoding-confirm-v1", "origin": origin, "plan_id": plan})

    def _key(self, salt):
        return HKDF(hashes.SHA256(), 32, salt, self.aad).derive(self.key.private_bytes_raw())

    def load(self):
        try:
            raw = private_read(self.path)
        except FileNotFoundError:
            return None
        try:
            packet = unique_json(raw)
            if not isinstance(packet, dict) or set(packet) != {"v", "salt", "nonce", "ciphertext"} or type(packet["v"]) is not int or packet["v"] != 1:
                raise ValueError()
            plain = AESGCM(self._key(b64(packet["salt"], 32))).decrypt(
                b64(packet["nonce"], 12), b64(packet["ciphertext"]), self.aad)
            receipt = unique_json(plain)
            if not isinstance(receipt, dict) or set(receipt) != {"binding", "body"}:
                raise ValueError()
            return receipt
        except Exception:
            raise EncoderError("Encrypted confirmation receipt failed integrity/binding checks.") from None

    def save_new(self, binding, body):
        if self.path.exists():
            raise EncoderError("An unresolved exact receipt already exists; never replace its operation.")
        salt, nonce = secrets.token_bytes(32), secrets.token_bytes(12)
        cipher = AESGCM(self._key(salt)).encrypt(nonce, canonical({"binding": binding, "body": body}), self.aad)
        packet = {"v": 1, "salt": base64.b64encode(salt).decode(), "nonce": base64.b64encode(nonce).decode(),
                  "ciphertext": base64.b64encode(cipher).decode()}
        _write(self.path, canonical(packet))

    def remove_confirmed(self):
        self.path.unlink()
        if os.name != "nt":
            descriptor = os.open(self.directory, os.O_RDONLY | getattr(os, "O_DIRECTORY", 0))
            try:
                os.fsync(descriptor)
            finally:
                os.close(descriptor)
