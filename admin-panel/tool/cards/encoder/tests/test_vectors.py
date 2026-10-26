"""Small published vectors: NXP AN12196 Rev.2.0, 4 March2025.

Tables14(auth/session keys),7(MAC settings),18(FULL settings),25/26(key
rotation) and4(SUN). These pin actual bytes independently of our emulator.
"""

import unittest

from nexencoder.ev2 import EV2, EncoderError, aes, derive, key_data, mac, pad, truncated_mac, verify_sun
from nexencoder.pcsc import PCSC


H = bytes.fromhex


class Script:
    def __init__(self, pairs):
        self.pairs = list(pairs)

    def reset(self):
        pass

    def transmit(self, command):
        expected, response = self.pairs.pop(0)
        if command != H(expected):
            raise AssertionError("Command differs from published NXP vector")
        return H(response)


class PublishedVectors(unittest.TestCase):
    def test_table12_bounded_three_frame_supported_card_version(self):
        script = Script([("9060000000", "0404083000110591AF"),
                         ("90AF000000", "0404020101110591AF"),
                         ("90AF000000", "04968CAA5C5E80CD65935D4021189100")])
        adapter = PCSC.__new__(PCSC)
        adapter.transmit = script.transmit
        adapter.check_version()
        self.assertEqual(script.pairs, [])

    def test_other_family_tamper_variant_wrong_lengths_and_unbounded_chaining_refused(self):
        base = [("9060000000", "0404083000110591AF"),
                ("90AF000000", "0404020101110591AF"),
                ("90AF000000", "04968CAA5C5E80CD65935D4021189100")]
        for index, response in ((0, "0401083000110591AF"), (1, "0404020301110591AF"),
                                 (0, "040408300011059100"), (2, "04968CAA5C5E80CD65935D40211891AF"),
                                 (1, "0404020101110591AF00")):
            with self.subTest(index=index, response=response):
                pairs = list(base)
                pairs[index] = (pairs[index][0], response)
                adapter = PCSC.__new__(PCSC)
                adapter.transmit = Script(pairs).transmit
                with self.assertRaises(EncoderError):
                    adapter.check_version()

    def test_table14_authentication_zero_iv_each_message_and_session_keys(self):
        transport = Script([
            ("9071000002000000", "A04C124213C186F22399D33AC2A3021591AF"),
            ("90AF00002035C3E05A752E0144BAC0DE51C1F22C56B34408A23D8AEA266CAB947EA8E0118D00",
             "3FA64DB5446D1F34CD6EA311167F5E4985B89690C04A05F17FA7AB2F081206639100")])
        session = EV2(transport, lambda _: H("13C5DB8A5930439FC3DEF9A4C675360F"))
        session.authenticate(0, bytes(16))
        self.assertEqual(session.enc.hex().upper(), "1309C877509E5A215007FF0ED19CA564")
        self.assertEqual(session.auth_mac.hex().upper(), "4C6626F5E72EA694202139295C7A7FC7")
        self.assertEqual(session.ti, H("9D00C4DF"))
        self.assertEqual(transport.pairs, [])

    def test_table7_mac_command_and_plain_authenticated_settings_response(self):
        transport = Script([("90F5000009026597A457C8CD442C00",
            "0040EEEE000100D1FE001F00004400004400002000006A00002A474282E7A479869100")])
        session = EV2(transport)
        session.ti, session.auth_mac = H("7A21085E"), H("8248134A386E86EB7FAF54A52E536CB6")
        answer = session.settings()
        self.assertEqual(answer.hex().upper(), "0040EEEE000100D1FE001F00004400004400002000006A0000")
        self.assertEqual(session.counter, 1)

    def test_table18_full_command_and_incremented_response_mac(self):
        transport = Script([("905F0000190261B6D97903566E84C3AE5274467E89EAD799B7C1A0EF7A0400",
                             "57BFF87B1241E93D9100")])
        session = EV2(transport)
        session.enc, session.auth_mac = H("1309C877509E5A215007FF0ED19CA564"), H("4C6626F5E72EA694202139295C7A7FC7")
        session.ti, session.counter = H("9D00C4DF"), 1
        self.assertEqual(session.change_settings(H("4000E0C1F121200000430000430000")), b"")
        self.assertEqual(session.counter, 2)

    def test_table25_other_key_crc_and_complete_full_apdu(self):
        new = H("F3847D627727ED3BC9C4CC050489B966")
        self.assertEqual(key_data(2, bytes(16), new)[17:], H("789DFADC"))
        transport = Script([("90C4000029022CF362B7BF4311FF3BE1DAA295E8C68DE09050560D19B9E16C2393AE9CD1FAC75D0CE20BCD1D06E600",
                             "203BB55D1089D5879100")])
        session = EV2(transport)
        session.enc, session.auth_mac = H("4CF3CB41A22583A61E89B158D252FC53"), H("5529860B2FC5FB6154B7F28361D30BF9")
        session.ti, session.counter = H("7614281A"), 2
        self.assertEqual(session.change_key(2, bytes(16), new), b"")

    def test_table26_master_change_omits_crc_and_accepts_only_bare_success(self):
        transport = Script([("90C400002900C0EB4DEEFEDDF0B513A03A95A75491818580503190D4D05053FF75668A01D6FDA6610234BDED643200", "9100")])
        session = EV2(transport)
        session.enc, session.auth_mac = H("4CF3CB41A22583A61E89B158D252FC53"), H("5529860B2FC5FB6154B7F28361D30BF9")
        session.ti, session.counter = H("7614281A"), 3
        self.assertEqual(session.change_key(0, bytes(16), H("5004BF991F408672B1EF00F08F9E8647")), b"")
        self.assertIsNone(session.ti)
        with self.assertRaises(EncoderError):
            session.settings()

    def test_table4_coherent_encrypted_metadata_and_empty_input_sun(self):
        self.assertEqual(verify_sun(H("EF963FF7828658A599F3041510671E88"), H("94EED9EE65337086"),
                                    bytes(16), bytes(16), H("04DE5F1EACC040")), 61)
        with self.assertRaises(EncoderError):
            verify_sun(H("EF963FF7828658A599F3041510671E88"), bytes(8), bytes(16), bytes(16), H("04DE5F1EACC040"))


if __name__ == "__main__":
    unittest.main()
