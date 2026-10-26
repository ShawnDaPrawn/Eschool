import unittest
import ctypes

from nexencoder.ev2 import EncoderError
from nexencoder.personalize import Personalizer
from nexencoder.pcsc import PCSC
from tests.emulator import Card
from tests.fixtures import values


class Personalization(unittest.TestCase):
    def setUp(self):
        _, _, self.plan, self.old = values()
        self.card = Card(self.plan.uid, self.old)

    def test_real_crypto_full_write_key0_last_all_keys_and_coherent_read(self):
        proof, counter = Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.changes, [1, 2, 3, 4, 0])
        self.assertEqual(self.card.writes, 2)
        self.assertEqual(tuple(self.card.keys), self.plan.keys)
        self.assertEqual(counter, 1)
        self.assertEqual(set(proof), {"picc", "mac"})
        self.assertEqual(self.card.authentications[-5:], [1, 2, 3, 4, 0])

    def adapter(self):
        adapter = PCSC.__new__(PCSC)
        adapter.handle, adapter.protocol = 1, ctypes.c_ulong(2)
        adapter.transmit = self.card.transmit
        def reconnect(*_):
            self.card.reset()
            return 0
        adapter.reconnect = reconnect
        return adapter

    def test_iso_native_adapter_commands_version_guard_and_full_recovery_with_emulator(self):
        _, counter = Personalizer(self.adapter(), self.plan).run(self.old)
        self.assertEqual(counter, 1)
        self.assertEqual(self.card.writes, 2)
        self.assertEqual(self.card.changes, [1, 2, 3, 4, 0])

    def test_emulated_wrong_card_family_stops_before_authentication_and_writes(self):
        self.card.software_version = bytes.fromhex("04040203011105")
        with self.assertRaises(EncoderError):
            Personalizer(self.adapter(), self.plan).run(self.old)
        self.assertEqual(self.card.authentications, [])
        self.assertEqual(self.card.writes, 0)
        self.assertEqual(self.card.changes, [])

    def test_power_loss_after_each_partial_key_recovers_without_blind_key_xor(self):
        for key in range(1, 5):
            with self.subTest(key=key):
                card = Card(self.plan.uid, self.old)
                card.fault_after = "key%d" % key
                with self.assertRaises(OSError):
                    Personalizer(card, self.plan).run(self.old)
                Personalizer(card, self.plan).run(self.old)
                self.assertEqual(card.changes, [1, 2, 3, 4, 0])
                self.assertEqual(tuple(card.keys), self.plan.keys)

    def test_power_loss_after_master_commit_never_rewrites_card(self):
        self.card.fault_after = "key0"
        with self.assertRaises(OSError):
            Personalizer(self.card, self.plan).run(self.old)
        writes = self.card.writes
        Personalizer(self.card, self.plan).run()
        self.assertEqual(self.card.writes, writes)
        self.assertEqual(self.card.changes, [1, 2, 3, 4, 0])

    def test_confirmed_plan_is_verify_only_even_with_wrong_or_incomplete_keys(self):
        self.plan.confirmed = True
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.writes, 0)
        self.assertEqual(self.card.changes, [])

    def test_confirmed_personalized_chip_verified_without_bootstrap(self):
        Personalizer(self.card, self.plan).run(self.old)
        self.plan.confirmed = True
        count = self.card.writes
        _, counter = Personalizer(self.card, self.plan).run()
        self.assertEqual(counter, 2)
        self.assertEqual(self.card.writes, count)

    def test_wrong_uid_refused_before_any_write_despite_known_bootstrap_keys(self):
        self.card.uid = bytes.fromhex("04112233445566")
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.changes, [])
        self.assertEqual(self.card.writes, 0)

    def test_unknown_nonmaster_key_refused_before_file_write(self):
        self.card.keys[3] = b"\x88" * 16
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.writes, 0)

    def test_issued_master_with_wrong_settings_or_file_is_not_repaired_by_rewrite(self):
        Personalizer(self.card, self.plan).run(self.old)
        self.card.file_settings = self.card.file_settings[:-1] + b"\x01"
        writes = self.card.writes
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.writes, writes)

    def test_tampered_authenticated_response_and_template_are_rejected(self):
        Personalizer(self.card, self.plan).run(self.old)
        self.card.tamper_response = True
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run()
        self.card.tamper_response, self.card.tamper_file = False, True
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run()

    def test_expired_plan_refused_before_chip_authentication(self):
        self.plan.context["expires_at"] = 1
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.authentications, [])

    def test_expiry_during_full_write_blocks_second_chunk_and_key_rotation(self):
        transport = self.card.transmit
        def expires_after_first_chunk(apdu):
            result = transport(apdu)
            if self.card.writes == 1:
                self.plan.context["expires_at"] = 1
            return result
        self.card.transmit = expires_after_first_chunk
        with self.assertRaises(EncoderError):
            Personalizer(self.card, self.plan).run(self.old)
        self.assertEqual(self.card.writes, 1)
        self.assertEqual(self.card.changes, [])


if __name__ == "__main__":
    unittest.main()
