"""Independent NTAG state machine for destructive/recovery tests.

Does not import runtime crypto, EV2 framing, padding, derivation or SUN helpers.
Decodes/validates commands and mutates durable file/key state; power loss can
occur after a mutation but before its acknowledgement. All values synthetic.
"""

import hashlib
import hmac
import zlib

from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.primitives.cmac import CMAC


def block(key, value, iv=bytes(16), decrypt=False):
    engine = Cipher(algorithms.AES(key), modes.CBC(iv))
    method = engine.decryptor() if decrypt else engine.encryptor()
    return method.update(value) + method.finalize()


def tag(key, value):
    instance = CMAC(algorithms.AES(key))
    instance.update(value)
    return instance.finalize()[1::2]


def full_mac(key, value):
    instance = CMAC(algorithms.AES(key))
    instance.update(value)
    return instance.finalize()


def padded(value):
    return value + bytes([128]) + bytes((-len(value) - 1) % 16)


class Card:
    def __init__(self, uid, keys):
        self.uid = uid
        self.keys = list(keys)
        self.file = bytes(256)
        self.file_settings = bytes.fromhex("0000E0")
        self.changes, self.writes, self.authentications = [], 0, []
        self.counter, self.read_counter, self.sequence = 0, 0, 0
        self.fault_after = None
        self.tamper_response = False
        self.tamper_file = False
        self.hardware_version = bytes.fromhex("04040830001105")
        self.software_version = bytes.fromhex("04040201011105")
        self.reset()

    def reset(self):
        self.selected = None
        self.challenge = None
        self.session_enc = self.session_mac = self.ti = None
        self.counter = 0
        self.version_frame = None

    def transmit(self, request):
        if request in (bytes.fromhex("00A4040C07D276000085010100"), bytes.fromhex("00A4000C02E104")):
            return bytes.fromhex("9000")
        if request == bytes.fromhex("00B0000000"):
            return self.read_ndef() + bytes.fromhex("9000")
        if (len(request) < 5 or request[0] != 0x90 or request[2:4] != bytes(2)
                or request[-1] != 0 or (len(request) != 5 and request[4] != len(request) - 6)):
            return bytes.fromhex("917E")
        instruction, data = request[1], request[5:-1] if len(request) > 5 else b""
        if instruction == 0x60 and not data:
            self.version_frame = 1
            return self.hardware_version + bytes.fromhex("91AF")
        if instruction == 0xAF and not data and self.version_frame:
            if self.version_frame == 1:
                self.version_frame = 2
                return self.software_version + bytes.fromhex("91AF")
            self.version_frame = None
            return self.uid + bytes(7) + bytes.fromhex("9100")
        if instruction == 0x71:
            if len(data) != 2 or data[0] > 4 or data[1] != 0:
                return bytes.fromhex("917E")
            self.selected = data[0]
            self.sequence += 1
            self.challenge = hashlib.sha256(b"synthetic-card-challenge" + self.sequence.to_bytes(4, "big")).digest()[:16]
            return block(self.keys[self.selected], self.challenge) + bytes.fromhex("91AF")
        if instruction == 0xAF:
            if self.challenge is None or len(data) != 32:
                return bytes.fromhex("91AE")
            opened = block(self.keys[self.selected], data, decrypt=True)
            a, proof = opened[:16], opened[16:]
            b = self.challenge
            if not hmac.compare_digest(proof, b[1:] + b[:1]):
                self.reset()
                return bytes.fromhex("91AE")
            material = a[:2] + bytes(map(lambda pair: pair[0] ^ pair[1], zip(a[2:8], b[:6]))) + b[6:] + a[8:]
            self.session_enc = full_mac(self.keys[self.selected], bytes.fromhex("A55A00010080") + material)
            self.session_mac = full_mac(self.keys[self.selected], bytes.fromhex("5AA500010080") + material)
            self.ti = self.sequence.to_bytes(4, "little")
            self.counter = 0
            self.authentications.append(self.selected)
            return block(self.keys[self.selected], self.ti + a[1:] + a[:1] + bytes(12)) + bytes.fromhex("9100")
        if self.ti is None:
            return bytes.fromhex("91AE")
        headers = {0xF5: 1, 0x51: 0, 0x5F: 1, 0x8D: 7, 0xC4: 1}
        if instruction not in headers or len(data) < headers[instruction] + 8:
            return bytes.fromhex("917E")
        header, payload, proof = data[:headers[instruction]], data[headers[instruction]:-8], data[-8:]
        expected = tag(self.session_mac, bytes([instruction]) + self.counter.to_bytes(2, "little") + self.ti + header + payload)
        if not hmac.compare_digest(expected, proof):
            self.reset()
            return bytes.fromhex("911E")
        output = b""
        if instruction in (0x5F, 0x8D, 0xC4):
            if self.selected != 0 or len(payload) % 16:
                return bytes.fromhex("919D")
            iv = block(self.session_enc, bytes.fromhex("A55A") + self.ti + self.counter.to_bytes(2, "little") + bytes(8))
            value = block(self.session_enc, payload, iv, decrypt=True).rstrip(b"\0")
            if value[-1:] != b"\x80":
                return bytes.fromhex("911E")
            value = value[:-1]
            if instruction == 0x5F:
                if header != b"\x02" or len(value) not in (3, 15):
                    return bytes.fromhex("917E")
                self.file_settings = value
                action = "settings"
            elif instruction == 0x8D:
                offset, size = int.from_bytes(header[1:4], "little"), int.from_bytes(header[4:], "little")
                if header[0] != 2 or self.file_settings != bytes.fromhex("0300E0") or len(value) != size or size > 192 or offset + size > 256:
                    return bytes.fromhex("919D")
                self.file = self.file[:offset] + value + self.file[offset + size:]
                self.writes += 1
                action = "write"
            else:
                index = header[0]
                if index not in range(5) or value[16] != 1 or len(value) != (17 if index == 0 else 21):
                    return bytes.fromhex("917E")
                key = value[:16] if index == 0 else bytes(x ^ y for x, y in zip(value[:16], self.keys[index]))
                if index and (zlib.crc32(key) ^ 0xFFFFFFFF).to_bytes(4, "little") != value[17:]:
                    return bytes.fromhex("911E")
                self.keys[index] = key
                self.changes.append(index)
                action = "key%d" % index
            if self.fault_after == action:
                self.fault_after = None
                self.reset()
                raise OSError("Synthetic power interruption after mutation")
            if instruction == 0xC4 and header == b"\0":
                self.reset()
                return bytes.fromhex("9100")
        elif instruction == 0x51:
            if payload:
                return bytes.fromhex("917E")
            iv = block(self.session_enc, bytes.fromhex("5AA5") + self.ti + (self.counter + 1).to_bytes(2, "little") + bytes(8))
            output = block(self.session_enc, padded(self.uid), iv)
        else:
            if header != b"\x02" or payload:
                return bytes.fromhex("917E")
            output = b"\0" + self.file_settings[:3] + bytes.fromhex("000100") + self.file_settings[3:]
        self.counter += 1
        answer = output + tag(self.session_mac, b"\0" + self.counter.to_bytes(2, "little") + self.ti + output)
        if self.tamper_response:
            answer = answer[:-1] + bytes([answer[-1] ^ 1])
        return answer + bytes.fromhex("9100")

    def read_ndef(self):
        if len(self.file_settings) != 15:
            raise OSError("Synthetic SDM not configured")
        self.read_counter += 1
        plain = b"\xc7" + self.uid + self.read_counter.to_bytes(3, "little") + b"\x01\x02\x03\x04\x05"
        encrypted = block(self.keys[2], plain).hex().upper().encode("ascii")
        derived = full_mac(self.keys[1], bytes.fromhex("3CC300010080") + self.uid + self.read_counter.to_bytes(3, "little"))
        proof = tag(derived, b"").hex().upper().encode("ascii")
        p = int.from_bytes(self.file_settings[6:9], "little")
        m = int.from_bytes(self.file_settings[12:], "little")
        result = bytearray(self.file)
        result[p:p + 32], result[m:m + 16] = encrypted, proof
        if self.tamper_file:
            result[12] ^= 1
        return bytes(result)
