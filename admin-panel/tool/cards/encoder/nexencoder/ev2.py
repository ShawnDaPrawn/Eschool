"""NTAG 424 DNA EV2 secure messaging, NXP AN12196 Rev. 2.0 tables 14–26.

The transport owns reset/reselection. Every failed/ambiguous exchange retires
the session; callers must authenticate again, never guess a command counter.
"""

import hmac
import secrets
import zlib

from cryptography.hazmat.primitives import cmac
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes


class EncoderError(Exception):
    """Deliberately bounded error: never include APDUs, keys or HTTP bodies."""


class AuthenticationFailed(EncoderError):
    pass


def aes(key, data, iv=bytes(16), decrypt=False):
    if len(key) != 16 or len(iv) != 16 or not data or len(data) % 16:
        raise EncoderError("Invalid AES block input.")
    transform = Cipher(algorithms.AES(key), modes.CBC(iv))
    worker = transform.decryptor() if decrypt else transform.encryptor()
    return worker.update(data) + worker.finalize()


def mac(key, data):
    worker = cmac.CMAC(algorithms.AES(key))
    worker.update(data)
    return worker.finalize()


def truncated_mac(key, data):
    return mac(key, data)[1::2]


def pad(data):
    return data + b"\x80" + bytes(15 - len(data) % 16)


def unpad(data):
    candidate = data.rstrip(b"\0")
    if not candidate or candidate[-1:] != b"\x80" or len(data) - len(candidate) > 15:
        raise EncoderError("Invalid authenticated response padding.")
    return candidate[:-1]


def derive(key, a, b):
    if len(a) != 16 or len(b) != 16:
        raise EncoderError("Invalid authentication nonce.")
    tail = a[:2] + bytes(x ^ y for x, y in zip(a[2:8], b[:6])) + b[6:] + a[8:]
    return (mac(key, bytes.fromhex("A55A00010080") + tail),
            mac(key, bytes.fromhex("5AA500010080") + tail))


def key_data(index, old, new):
    if index not in range(5) or len(old) != 16 or len(new) != 16:
        raise EncoderError("Invalid key change input.")
    if index == 0:
        return new + b"\x01"
    # NXP CRC initial/final convention: complement the standard CRC32.
    return bytes(x ^ y for x, y in zip(old, new)) + b"\x01" + (
        zlib.crc32(new) ^ 0xFFFFFFFF).to_bytes(4, "little")


def native_apdu(ins, data):
    if len(data) > 255:
        raise EncoderError("APDU exceeds short transport bound.")
    if not data:
        return bytes([0x90, ins, 0, 0, 0])
    return bytes([0x90, ins, 0, 0, len(data)]) + data + b"\0"


class EV2:
    def __init__(self, transport, random_bytes=secrets.token_bytes, before_command=lambda: None):
        self.transport = transport
        self.random_bytes = random_bytes
        self.before_command = before_command
        self.retire()

    def retire(self):
        self.enc = self.auth_mac = self.ti = None
        self.counter = 0

    def _exchange(self, ins, data, expected=b"\x91\x00"):
        self.before_command()
        response = self.transport.transmit(native_apdu(ins, data))
        if len(response) < 2 or response[-2:] != expected:
            self.retire()
            if response[-2:] == b"\x91\xae":
                raise AuthenticationFailed("Card authentication refused.")
            raise EncoderError("Card command refused or interrupted; reauthenticate.")
        return response[:-2]

    def authenticate(self, index, key):
        self.retire()
        if index not in range(5) or len(key) != 16:
            raise EncoderError("Invalid authentication input.")
        self.transport.reset()
        try:
            encrypted_b = self._exchange(0x71, bytes([index, 0]), b"\x91\xaf")
            if len(encrypted_b) != 16:
                raise EncoderError("Invalid authentication challenge.")
            b = aes(key, encrypted_b, decrypt=True)
            a = self.random_bytes(16)
            reply = self._exchange(0xAF, aes(key, a + b[1:] + b[:1]))
            if len(reply) != 32:
                raise EncoderError("Invalid authentication response.")
            plain = aes(key, reply, decrypt=True)
            if not hmac.compare_digest(plain[4:20], a[1:] + a[:1]):
                raise AuthenticationFailed("Card nonce proof refused.")
            self.enc, self.auth_mac = derive(key, a, b)
            self.ti = plain[:4]
        except BaseException:
            self.retire()
            raise

    def secure(self, ins, header=b"", data=b"", *, full=True,
               encrypted_response=False, retires=False):
        if self.ti is None or self.counter >= 65534:
            raise EncoderError("A fresh authenticated session is required.")
        try:
            count = self.counter.to_bytes(2, "little")
            payload = data
            if full:
                iv = aes(self.enc, b"\xa5\x5a" + self.ti + count + bytes(8))
                payload = aes(self.enc, pad(data), iv)
            proof = truncated_mac(self.auth_mac, bytes([ins]) + count + self.ti + header + payload)
            answer = self._exchange(ins, header + payload + proof)
            if retires:
                if answer:
                    raise EncoderError("Unexpected authenticated-key change response.")
                self.retire()
                return b""
            self.counter += 1
            if len(answer) < 8:
                raise EncoderError("Missing card response authentication.")
            body, received = answer[:-8], answer[-8:]
            count = self.counter.to_bytes(2, "little")
            expected = truncated_mac(self.auth_mac, b"\0" + count + self.ti + body)
            if not hmac.compare_digest(received, expected):
                raise EncoderError("Card response integrity refused.")
            if encrypted_response:
                iv = aes(self.enc, b"\x5a\xa5" + self.ti + count + bytes(8))
                body = unpad(aes(self.enc, body, iv, decrypt=True))
            return body
        except BaseException:
            self.retire()
            raise

    def uid(self):
        uid = self.secure(0x51, full=False, encrypted_response=True)
        if len(uid) != 7:
            raise EncoderError("Unsupported card UID.")
        return uid

    def settings(self):
        return self.secure(0xF5, b"\x02", full=False)

    def change_settings(self, settings):
        return self.secure(0x5F, b"\x02", settings)

    def write_file(self, data):
        if len(data) != 256:
            raise EncoderError("Only the complete bounded NDEF file can be written.")
        for offset in range(0, 256, 192):
            chunk = data[offset:offset + 192]
            self.secure(0x8D, b"\x02" + offset.to_bytes(3, "little") +
                        len(chunk).to_bytes(3, "little"), chunk)

    def change_key(self, index, old, new):
        return self.secure(0xC4, bytes([index]), key_data(index, old, new), retires=index == 0)


def verify_sun(picc, proof, metadata_key, read_key, uid):
    if len(picc) != 16 or len(proof) != 8 or len(uid) != 7:
        raise EncoderError("Invalid coherent card proof.")
    plain = aes(metadata_key, picc, decrypt=True)
    if plain[0] != 0xC7 or not hmac.compare_digest(plain[1:8], uid):
        raise EncoderError("Card proof UID does not match the authorized plan.")
    session = mac(read_key, bytes.fromhex("3CC300010080") + plain[1:11])
    if not hmac.compare_digest(truncated_mac(session, b""), proof):
        raise EncoderError("Card proof integrity refused.")
    return int.from_bytes(plain[8:11], "little")
