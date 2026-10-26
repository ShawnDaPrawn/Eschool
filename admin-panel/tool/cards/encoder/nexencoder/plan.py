"""Strict, purpose-separated server envelope and approved chip profile."""

import base64
import json
import re
import time
import uuid
from dataclasses import dataclass, field
from urllib.parse import parse_qsl, urlsplit

from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PublicKey
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.kdf.hkdf import HKDF

from .ev2 import EncoderError

SUITE = "NEX1-X25519-HKDF-SHA256-A256GCM"
CONTEXT = ("school_id", "device_id", "plan_id", "card_id", "revision", "issued_at", "expires_at")


def canonical(value):
    return json.dumps(value, ensure_ascii=False, separators=(",", ":"), allow_nan=False).encode("utf-8")


def unique_json(raw):
    def pairs(items):
        result = {}
        for key, value in items:
            if key in result:
                raise EncoderError("Duplicate JSON field refused.")
            result[key] = value
        return result
    try:
        return json.loads(raw, object_pairs_hook=pairs,
                          parse_constant=lambda _: (_ for _ in ()).throw(ValueError()))
    except (ValueError, UnicodeError, RecursionError):
        raise EncoderError("Invalid bounded JSON response.") from None


def uuid_text(value):
    try:
        return isinstance(value, str) and str(uuid.UUID(value)) == value
    except ValueError:
        return False


def positive(value):
    return type(value) is int and 0 < value < 2**53


def b64(value, size=None, maximum=16384):
    if not isinstance(value, str) or len(value) > maximum:
        raise EncoderError("Invalid bounded envelope field.")
    try:
        decoded = base64.b64decode(value, validate=True)
    except (ValueError, base64.binascii.Error):
        raise EncoderError("Invalid canonical base64 field.") from None
    if base64.b64encode(decoded).decode("ascii") != value or (size is not None and len(decoded) != size):
        raise EncoderError("Invalid canonical base64 field length.")
    return decoded


def hex_bytes(value, length):
    if not isinstance(value, str) or not re.fullmatch(r"[0-9A-Fa-f]{%d}" % (length * 2), value):
        raise EncoderError("Invalid bounded chip field.")
    return bytes.fromhex(value)


@dataclass(repr=False)
class Plan:
    context: dict
    uid: bytes = field(repr=False)
    keys: tuple = field(repr=False)
    ndef: bytes = field(repr=False)
    settings: bytes = field(repr=False)
    confirmed: bool
    _deadline: float = field(init=False, repr=False)

    def __post_init__(self):
        # A backwards wall-clock correction must not extend an opened lease.
        self._deadline = time.monotonic() + max(0, min(600, self.context["expires_at"] - time.time()))

    def __repr__(self):
        return "<AuthorizedEncodingPlan redacted>"

    def alive(self, now=None):
        now = time.time() if now is None else now
        if now < self.context["issued_at"] - 30 or now >= self.context["expires_at"] or time.monotonic() >= self._deadline:
            raise EncoderError("Encoding plan expired; fetch fresh authorization before continuing.")

    @property
    def binding(self):
        return {key: self.context[key] for key in CONTEXT[:5]}

    @property
    def expected_settings(self):
        # File type / option+access rights / 256-byte file size / SDM fields.
        return b"\0" + self.settings[:3] + b"\x00\x01\x00" + self.settings[3:]


def open_plan(envelope, private_key, expected_plan, *, now=None, allow_local_http=False):
    try:
        if not isinstance(envelope, dict) or set(envelope) != {
                "suite", "context", "ephemeral_public_key", "salt", "nonce", "tag", "ciphertext"}:
            raise EncoderError("Unexpected encoding envelope.")
        context = envelope["context"]
        if envelope["suite"] != SUITE or not isinstance(context, dict) or tuple(context) != CONTEXT:
            raise EncoderError("Wrong encoding purpose or context order.")
        if (not all(positive(context[k]) for k in ("school_id", "revision", "issued_at", "expires_at"))
                or not all(uuid_text(context[k]) for k in ("device_id", "plan_id", "card_id"))
                or context["plan_id"] != expected_plan
                or context["expires_at"] - context["issued_at"] != 600):
            raise EncoderError("Invalid encoding context binding.")
        aad = canonical({"suite": SUITE, **context})
        shared = private_key.exchange(X25519PublicKey.from_public_bytes(b64(envelope["ephemeral_public_key"], 32)))
        key = HKDF(hashes.SHA256(), 32, b64(envelope["salt"], 32), aad).derive(shared)
        plain = AESGCM(key).decrypt(b64(envelope["nonce"], 12),
                                    b64(envelope["ciphertext"]) + b64(envelope["tag"], 16), aad)
        payload = unique_json(plain)
        expected_fields = {"v", "school_id", "device_id", "plan_id", "card_id", "card_revision",
                           "uid", "keys", "key_version", "ndef", "settings", "confirmed"}
        if not isinstance(payload, dict) or set(payload) != expected_fields:
            raise EncoderError("Unexpected encoding plan fields.")
        if (type(payload["v"]) is not int or payload["v"] != 1
                or type(payload["key_version"]) is not int or payload["key_version"] != 1
                or type(payload["confirmed"]) is not bool
                or any(payload[k] != context[k] or type(payload[k]) is not type(context[k])
                       for k in ("school_id", "device_id", "plan_id", "card_id"))
                or type(payload["card_revision"]) is not int or payload["card_revision"] != context["revision"]):
            raise EncoderError("Decrypted plan does not match its authorized context.")
        if not isinstance(payload["keys"], list) or len(payload["keys"]) != 5:
            raise EncoderError("Five issued keys are required.")
        keys = tuple(hex_bytes(k, 16) for k in payload["keys"])
        if any(k == bytes(16) for k in keys) or len(set(keys)) != 5:
            raise EncoderError("Issued keys must be distinct and nonzero.")
        if not isinstance(payload["uid"], str) or not re.fullmatch("04[0-9A-F]{12}", payload["uid"]):
            raise EncoderError("Unsupported card UID encoding.")
        ndef = b64(payload["ndef"], 256)
        settings = hex_bytes(payload["settings"], 15)
        _profile(ndef, settings, context, allow_local_http)
        plan = Plan(context, bytes.fromhex(payload["uid"]), keys, ndef, settings, payload["confirmed"])
        plan.alive(now)
        return plan
    except EncoderError:
        raise
    except Exception:
        raise EncoderError("Encoding envelope authentication refused.") from None


def _profile(ndef, settings, context, allow_local_http):
    size = int.from_bytes(ndef[:2], "big")
    if (size < 5 or size > 249 or ndef[2:4] != b"\xd1\x01"
            or ndef[4] != size - 4 or ndef[5:7] != b"U\0"
            or any(ndef[2 + size:])):
        raise EncoderError("Unsupported bounded NDEF profile.")
    url = ndef[7:2 + size].decode("ascii")
    if any(ord(c) < 33 or ord(c) > 126 for c in url):
        raise EncoderError("Invalid NDEF URI.")
    parts = urlsplit(url)
    if (not parts.hostname or parts.username is not None or parts.password is not None
            or parts.query or not parts.path.endswith("/card-tap")
            or (parts.scheme != "https" and not (allow_local_http and parts.scheme == "http"
                and parts.hostname in ("localhost", "127.0.0.1", "::1")))):
        raise EncoderError("Unexpected card URI purpose.")
    expected = [("s", str(context["school_id"])), ("c", context["card_id"]), ("e", "0" * 32), ("m", "0" * 16)]
    if parse_qsl(parts.fragment, keep_blank_values=True, strict_parsing=True) != expected:
        raise EncoderError("Card URI does not bind its authorized identity.")
    picc = ndef.find(b"&e=") + 3
    proof = ndef.find(b"&m=") + 3
    expected_settings = bytes.fromhex("4000E0C1F121") + picc.to_bytes(3, "little") + proof.to_bytes(3, "little") * 2
    if settings != expected_settings or picc < 7 or picc + 32 > proof or proof + 16 > size + 2:
        raise EncoderError("SDM settings do not bind the complete NDEF template.")
