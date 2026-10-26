"""Generated synthetic plans, not actual device/bootstrap/card credentials."""

import base64
import json
import time

from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.kdf.hkdf import HKDF

from nexencoder.plan import Plan, SUITE

PLAN = "b62c974b-408c-435d-862f-ff1926b76fa5"
DEVICE = "d35a917e-6f29-4e4a-91cb-485018951f80"
CARD = "a94c1d4e-f5f0-45d0-a594-53a4f0f74df4"


def values():
    now = int(time.time())
    context = dict(school_id=7, device_id=DEVICE, plan_id=PLAN, card_id=CARD,
                   revision=3, issued_at=now, expires_at=now + 600)
    uri = ("https://school.example/card-tap#s=7&c=" + CARD + "&e=" + "0" * 32 + "&m=" + "0" * 16).encode()
    message = b"\xd1\x01" + bytes([len(uri) + 1]) + b"U\0" + uri
    file = (len(message).to_bytes(2, "big") + message).ljust(256, b"\0")
    p, m = file.index(b"&e=") + 3, file.index(b"&m=") + 3
    settings = bytes.fromhex("4000E0C1F121") + p.to_bytes(3, "little") + m.to_bytes(3, "little") * 2
    new = tuple(bytes([i + 1]) * 16 for i in range(5))
    old = tuple(bytes([i + 11]) * 16 for i in range(5))
    payload = dict(v=1, school_id=7, device_id=DEVICE, plan_id=PLAN, card_id=CARD,
                   card_revision=3, uid="04DE5F1EACC040", keys=[k.hex().upper() for k in new],
                   key_version=1, ndef=base64.b64encode(file).decode(), settings=settings.hex().upper(), confirmed=False)
    return context, payload, Plan(context, bytes.fromhex(payload["uid"]), new, file, settings, False), old


def sealed(private, context, payload):
    ephemeral = X25519PrivateKey.generate()
    aad = json.dumps({"suite": SUITE, **context}, separators=(",", ":"), ensure_ascii=False).encode()
    salt, nonce = bytes(range(32)), bytes(range(12))
    shared = ephemeral.exchange(private.public_key())
    key = HKDF(hashes.SHA256(), 32, salt, aad).derive(shared)
    cipher = AESGCM(key).encrypt(nonce, json.dumps(payload, separators=(",", ":")).encode(), aad)
    encode = lambda value: base64.b64encode(value).decode()
    return dict(suite=SUITE, context=context, ephemeral_public_key=encode(ephemeral.public_key().public_bytes_raw()),
                salt=encode(salt), nonce=encode(nonce), ciphertext=encode(cipher[:-16]), tag=encode(cipher[-16:]))
