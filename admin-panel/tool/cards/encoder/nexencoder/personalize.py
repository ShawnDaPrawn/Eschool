"""Recoverable personalization: prove keys, write FULL, rotate master last."""

import hmac

from .ev2 import AuthenticationFailed, EV2, EncoderError, verify_sun
from .plan import hex_bytes


class Personalizer:
    def __init__(self, transport, plan, random_bytes=None):
        self.transport, self.plan = transport, plan
        self.session = (EV2(transport, before_command=plan.alive) if random_bytes is None
                        else EV2(transport, random_bytes, before_command=plan.alive))

    def _authenticate(self, index, key):
        self.plan.alive()
        self.session.authenticate(index, key)
        if not hmac.compare_digest(self.session.uid(), self.plan.uid):
            self.session.retire()
            raise EncoderError("Physical card UID does not match this plan; no write is permitted.")

    def _probe(self, index, key):
        try:
            self._authenticate(index, key)
            return True
        except AuthenticationFailed:
            return False

    def run(self, bootstrap=None):
        """Returns one locally verified coherent SUN, never marks server encoded.

        An authenticated new master means this card is already personalized:
        always verify-only, even when the server has not received confirmation.
        No retry after ambiguous APDU is made within a session.
        """
        new_master = self._probe(0, self.plan.keys[0])
        if self.plan.confirmed and not new_master:
            raise EncoderError("Confirmed plan requires issued-key verification; rewriting is forbidden.")
        if not new_master:
            if (bootstrap is None or len(bootstrap) != 5
                    or any(not isinstance(k, bytes) or len(k) != 16 for k in bootstrap)):
                raise EncoderError("All five operator-supplied bootstrap keys are required.")
            self._authenticate(0, bootstrap[0])
            already_changed = []
            # Resolve every key before touching NDEF; do not infer blank defaults.
            for index in range(1, 5):
                current = self._probe(index, self.plan.keys[index])
                if not current:
                    self._authenticate(index, bootstrap[index])
                already_changed.append(current)
            self._authenticate(0, bootstrap[0])
            self.plan.alive()
            self.session.change_settings(bytes.fromhex("0300E0"))
            self.session.write_file(self.plan.ndef)
            self.session.change_settings(self.plan.settings)
            for index, changed in enumerate(already_changed, 1):
                self.plan.alive()
                if not changed:
                    self.session.change_key(index, bootstrap[index], self.plan.keys[index])
            self.plan.alive()
            self.session.change_key(0, bootstrap[0], self.plan.keys[0])
        # key0 change deliberately retired the session; prove all five keys.
        for index in range(1, 5):
            self._authenticate(index, self.plan.keys[index])
        self._authenticate(0, self.plan.keys[0])
        if self.session.settings() != self.plan.expected_settings:
            raise EncoderError("Issued card settings differ from the authorized profile; not rewriting.")
        self.plan.alive()
        actual = self.transport.read_ndef()
        if len(actual) != 256:
            raise EncoderError("A complete single-read SUN proof is required.")
        picc_offset = int.from_bytes(self.plan.settings[6:9], "little")
        mac_offset = int.from_bytes(self.plan.settings[12:15], "little")
        picc = hex_bytes(actual[picc_offset:picc_offset + 32].decode("ascii", errors="replace"), 16)
        proof = hex_bytes(actual[mac_offset:mac_offset + 16].decode("ascii", errors="replace"), 8)
        restored = bytearray(actual)
        restored[picc_offset:picc_offset + 32] = b"0" * 32
        restored[mac_offset:mac_offset + 16] = b"0" * 16
        if not hmac.compare_digest(restored, self.plan.ndef):
            raise EncoderError("Card file differs from its approved immutable template.")
        counter = verify_sun(picc, proof, self.plan.keys[2], self.plan.keys[1], self.plan.uid)
        self.plan.alive()
        return {"picc": picc.hex().upper(), "mac": proof.hex().upper()}, counter
