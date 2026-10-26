import contextlib
import copy
import ctypes
import io
import json
import os
from pathlib import Path
import tempfile
import threading
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from unittest.mock import patch
from urllib.error import HTTPError, URLError

from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey

from nexencoder.__main__ import main, secret
from nexencoder.api import Api, UnknownConfirmation
from nexencoder.ev2 import EncoderError
from nexencoder.pcsc import PCSC
from nexencoder.storage import Outbox, generate_key
from tests.emulator import Card
from tests.fixtures import DEVICE, PLAN, sealed, values

NED = "NED1.7." + DEVICE + "." + "ab" * 32


class Response:
    status = 200

    def __init__(self, url, value, raw=None):
        self.url, self.value, self.raw = url, value, raw

    def geturl(self):
        return self.url

    def read(self, limit):
        return (self.raw if self.raw is not None else json.dumps(self.value).encode())[:limit]

    def __enter__(self):
        return self

    def __exit__(self, *_):
        pass


class Opener:
    def __init__(self, result=None, error=None):
        self.result, self.error, self.requests = result, error, []

    def open(self, request, timeout):
        self.requests.append(request)
        if self.error:
            raise self.error
        return Response(request.full_url, self.result)


class HttpBoundary(unittest.TestCase):
    def setUp(self):
        self.plan = values()[2]
        self.body = dict(operation_id="a725c552-82c7-417a-acd5-5ac6a23b18dd", picc="A9" * 16, mac="B8" * 8)
        self.good = dict(encoded=True, plan_id=PLAN, card_id=self.plan.binding["card_id"], revision=3,
                         verified_counter=1, encoded_at="2026-10-26T12:00:00.000Z",
                         collection_authorized=False, sign_in_authorized=False)

    def api(self, result=None, error=None):
        opener = Opener(dict(error=False, code=200, data=self.good if result is None else result), error)
        return Api("https://school.example", NED, opener=opener), opener

    def test_body_only_explicit_bearer_no_cookie_query_or_redirect_handler(self):
        api, opener = self.api()
        self.assertEqual(api.confirm(PLAN, self.body, self.plan.binding), self.good)
        request = opener.requests[0]
        self.assertEqual(request.get_method(), "POST")
        self.assertEqual(request.full_url, "https://school.example/api/v2/card-encoder/plans/" + PLAN + "/confirm")
        self.assertEqual(json.loads(request.data), self.body)
        self.assertEqual(request.get_header("Authorization"), "Bearer " + NED)
        self.assertIsNone(request.get_header("Cookie"))
        self.assertNotIn(NED, repr(api))
        self.assertEqual(api.school_id, 7)
        self.assertEqual(api.device_id, DEVICE)

    def test_malformed_success_or_unbound_receipt_is_always_unknown(self):
        for mutation in (dict(encoded=1), dict(card_id=PLAN), dict(revision=True), dict(verified_counter=True),
                         dict(sign_in_authorized=True), dict(collection_authorized=True), dict(encoded_at="not-time")):
            api, _ = self.api({**self.good, **mutation})
            with self.assertRaises(UnknownConfirmation):
                api.confirm(PLAN, self.body, self.plan.binding)
        api, _ = self.api()
        api.opener.result = {"error": False, "code": "200", "data": self.good}
        with self.assertRaises(UnknownConfirmation):
            api.confirm(PLAN, self.body, self.plan.binding)

    def test_counter_zero_and_full_24_bit_range_are_valid_without_accepting_bool(self):
        for counter in (0, 0xFFFFFF):
            api, _ = self.api({**self.good, "verified_counter": counter})
            self.assertEqual(api.confirm(PLAN, self.body, self.plan.binding)["verified_counter"], counter)
        for counter in (False, True, -1, 0x1000000, "0"):
            api, _ = self.api({**self.good, "verified_counter": counter})
            with self.assertRaises(UnknownConfirmation):
                api.confirm(PLAN, self.body, self.plan.binding)

    def test_explicit_uncertain_statuses_timeout_and_bad_json_are_retryable_exactly(self):
        for status in (408, 425, 429, 500, 502, 503):
            api, _ = self.api(error=HTTPError("https://school.example", status, "synthetic", {}, None))
            with self.assertRaises(UnknownConfirmation):
                api.confirm(PLAN, self.body, self.plan.binding)
        api, _ = self.api(error=URLError("synthetic interrupted response"))
        with self.assertRaises(UnknownConfirmation):
            api.confirm(PLAN, self.body, self.plan.binding)
        api, _ = self.api()
        api.opener.open = lambda request, timeout: Response(request.full_url, None, b"not-json")
        with self.assertRaises(UnknownConfirmation):
            api.confirm(PLAN, self.body, self.plan.binding)

    def test_https_origin_and_encoder_only_credentials_no_secret_arguments(self):
        for origin in ("http://remote.example", "https://user:pass@school.example", "https://school.example/?x=1", "https://school.example/path", "https://school.example/#fragment"):
            with self.assertRaises(EncoderError):
                Api(origin, NED, allow_local_http=True)
        for credential in ("NEF1.secret", NED + "\r\nX-Header:unsafe", "Bearer " + NED):
            with self.assertRaises(EncoderError):
                Api("https://school.example", credential)

    def test_oversized_response_refused_with_unknown_post_semantics(self):
        api, _ = self.api()
        api.opener.open = lambda request, timeout: Response(request.full_url, None, b" " * 65537)
        with self.assertRaises(UnknownConfirmation):
            api.confirm(PLAN, self.body, self.plan.binding)

    def test_real_loopback_http_request_redirect_refusal_no_cookie_or_proxy(self):
        observed = []
        class Handler(BaseHTTPRequestHandler):
            def log_message(self, *_):
                pass

            def do_GET(self):
                observed.append(dict(self.headers))
                self.send_response(302)
                self.send_header("Location", "/redirect-must-not-be-followed")
                self.end_headers()

        server = ThreadingHTTPServer(("127.0.0.1", 0), Handler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            api = Api("http://127.0.0.1:%d" % server.server_port, NED, allow_local_http=True)
            with patch.dict(os.environ, {"http_proxy": "http://127.0.0.1:1", "HTTP_PROXY": "http://127.0.0.1:1"}):
                with self.assertRaises(EncoderError):
                    api.download(PLAN)
            self.assertEqual(len(observed), 1)
            self.assertEqual(observed[0]["Authorization"], "Bearer " + NED)
            self.assertNotIn("Cookie", observed[0])
        finally:
            server.shutdown()
            server.server_close()
            thread.join(timeout=2)

    def test_read_binary_is_one_exact_256_byte_command(self):
        transport = PCSC.__new__(PCSC)
        sent = []
        def answer(apdu):
            sent.append(apdu)
            return b"\x90\x00" if len(sent) == 1 else bytes(256) + b"\x90\x00"
        transport.transmit = answer
        self.assertEqual(transport.read_ndef(), bytes(256))
        self.assertEqual(sent, [bytes.fromhex("00A4000C02E104"), bytes.fromhex("00B0000000")])

    def test_unsupported_version_fails_reset_before_any_authentication(self):
        transport = PCSC.__new__(PCSC)
        transport.handle, transport.protocol = 1, ctypes.c_ulong(2)
        transport.reconnect = lambda *_: 0
        commands = []
        def answer(apdu):
            commands.append(apdu)
            return bytes.fromhex("9000") if len(commands) == 1 else bytes.fromhex("6A81")
        transport.transmit = answer
        with self.assertRaises(EncoderError):
            transport.reset()
        self.assertEqual(commands, [bytes.fromhex("00A4040C07D276000085010100"), bytes.fromhex("9060000000")])


class ExactRecoveryJourney(unittest.TestCase):
    def test_physical_commit_unknown_http_restart_reuses_encrypted_exact_uuid_without_rewrite(self):
        context, payload, plan, old = values()
        card = Card(plan.uid, old)
        key = X25519PrivateKey.generate()
        posted = []
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory)
            # Actual encrypted local key and actual outbox encryption in both runs.
            public = generate_key(path / "private.pem", b"synthetic-long-password")
            from nexencoder.storage import load_key
            key = load_key(path / "private.pem", b"synthetic-long-password")
            box = Outbox(path / "receipts", key, "https://school.example", PLAN)
            class FakeApi:
                school_id, device_id, origin = 7, DEVICE, "https://school.example"

                def __init__(self, *_args, **_kwargs):
                    pass

                def download(self, _):
                    return sealed(key, context, payload)

                def confirm(self, plan_id, body, binding):
                    self_receipt = box.load()
                    if self_receipt["body"] != body or self_receipt["binding"] != binding:
                        raise AssertionError("POST must follow durable encrypted exact receipt")
                    posted.append(copy.deepcopy(body))
                    if len(posted) == 1:
                        payload["confirmed"] = True
                        raise UnknownConfirmation("Synthetic committed server response lost")
                    return dict(encoded=True)

            args = ["--key-file", str(path / "private.pem"), "--server", "https://school.example", "--plan", PLAN,
                    "--outbox", str(path / "receipts"), "--execute-on-reader"]
            output, errors = io.StringIO(), io.StringIO()
            with patch("nexencoder.__main__.Api", FakeApi), patch("nexencoder.__main__.PCSC", return_value=contextlib.nullcontext(card)), \
                 patch("nexencoder.__main__.secret", side_effect=["synthetic-long-password", NED] + [k.hex() for k in old]), \
                 patch("builtins.input", return_value="yes"), contextlib.redirect_stdout(output), contextlib.redirect_stderr(errors):
                self.assertEqual(main(args), 1)
            self.assertIsNotNone(box.load())
            written = card.writes
            with patch("nexencoder.__main__.Api", FakeApi), patch("nexencoder.__main__.PCSC", side_effect=AssertionError("Must not reopen physical reader")), \
                 patch("nexencoder.__main__.secret", side_effect=["synthetic-long-password", NED]), \
                 patch("builtins.input", return_value="yes"), contextlib.redirect_stdout(output), contextlib.redirect_stderr(errors):
                self.assertEqual(main(args), 0)
            self.assertEqual(posted[0], posted[1])
            self.assertEqual(card.writes, written)
            self.assertIsNone(box.load())
            for value in [NED, "synthetic-long-password", *[k.hex() for k in old], posted[0]["picc"], posted[0]["mac"]]:
                self.assertNotIn(value, output.getvalue() + errors.getvalue())

    def test_piped_secret_input_is_refused_without_echo_fallback(self):
        with patch("sys.stdin.isatty", return_value=False), patch("getpass.getpass", side_effect=AssertionError("Must not read pipe")):
            with self.assertRaises(EncoderError):
                secret("synthetic prompt")


if __name__ == "__main__":
    unittest.main()
