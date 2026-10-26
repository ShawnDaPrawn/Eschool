"""Interactive operator boundary. Never supplies or prints chip secrets."""

import argparse
import getpass
import os
import sys
import uuid
import warnings

from .api import Api, UnknownConfirmation
from .ev2 import EV2, EncoderError
from .pcsc import PCSC
from .personalize import Personalizer
from .plan import hex_bytes, open_plan, uuid_text
from .storage import Outbox, generate_key, load_key


def secret(prompt):
    # getpass's pipe/echo fallback is forbidden: it must be a real private TTY.
    if not sys.stdin.isatty():
        raise EncoderError("Use an interactive private terminal for secret input.")
    with warnings.catch_warnings():
        warnings.simplefilter("error", getpass.GetPassWarning)
        try:
            return getpass.getpass(prompt)
        except getpass.GetPassWarning:
            raise EncoderError("Secret input cannot be hidden on this terminal.") from None


def main(argv=None):
    parser = argparse.ArgumentParser(description="NavigateEd local NTAG 424 encoder (physical qualification pending).")
    parser.add_argument("--key-file", help="Owner-only encrypted PKCS8 file path; never a key value")
    parser.add_argument("--initialize-key", action="store_true", help="Create a new encrypted local key; print only its public setup value")
    parser.add_argument("--inspect-uid", action="store_true", help="Local bootstrap-key authentication and UID read only; no server call or chip write")
    parser.add_argument("--server", help="HTTPS origin, no path/query/credentials")
    parser.add_argument("--plan", help="Authorized plan UUID, not a secret")
    parser.add_argument("--outbox", help="Owner-only directory for encrypted exact confirmation receipts")
    parser.add_argument("--reader", help="Exact local PC/SC reader name, required when multiple readers exist")
    parser.add_argument("--allow-local-http-fixture", action="store_true", help="Only localhost/loopback synthetic fixtures; never a remote HTTP host")
    parser.add_argument("--execute-on-reader", action="store_true", help="Explicitly enable local physical PC/SC access")
    args = parser.parse_args(argv)
    os.umask(0o077)
    try:
        if args.inspect_uid:
            if args.initialize_key or not args.execute_on_reader:
                raise EncoderError("UID inspection requires --execute-on-reader and cannot initialize a key.")
            bootstrap = hex_bytes(secret("Existing card master key0 (32 hex; no default): "), 16)
            with PCSC(args.reader) as transport:
                session = EV2(transport)
                session.authenticate(0, bootstrap)
                print("Authenticated chip UID: " + session.uid().hex().upper())
            return 0
        if not args.key_file:
            raise EncoderError("An encrypted local private-key file path is required.")
        password = secret("Local private-key passphrase: ").encode("utf-8")
        if args.initialize_key:
            if secret("Repeat private-key passphrase: ").encode("utf-8") != password:
                raise EncoderError("Passphrases differ.")
            public = generate_key(args.key_file, password)
            print("Encoder public setup key (safe to register): " + public)
            return 0
        if not args.server or not uuid_text(args.plan) or not args.outbox:
            raise EncoderError("Server, canonical plan UUID and private outbox directory are required.")
        key = load_key(args.key_file, password)
        del password
        api = Api(args.server, secret("Encoder device access code (NED1): "), allow_local_http=args.allow_local_http_fixture)
        plan = open_plan(api.download(args.plan), key, args.plan, allow_local_http=args.allow_local_http_fixture)
        if plan.context["school_id"] != api.school_id or plan.context["device_id"] != api.device_id:
            raise EncoderError("Encoding envelope belongs to another encoder or school.")
        outbox = Outbox(args.outbox, key, api.origin, args.plan)
        receipt = outbox.load()
        if receipt is not None:
            if receipt["binding"] != plan.binding:
                raise EncoderError("Unresolved receipt belongs to a retired source; it cannot be replayed or replaced.")
            if input("Retry the existing encrypted confirmation without writing the card? [yes/NO] ").strip() != "yes":
                raise EncoderError("Existing encrypted receipt retained; no card operation occurred.")
        else:
            if not args.execute_on_reader:
                raise EncoderError("Physical reader access requires --execute-on-reader; no card was opened.")
            bootstrap = None
            if not plan.confirmed:
                bootstrap = tuple(hex_bytes(secret("Bootstrap key %d (32 hex; no defaults): " % i), 16) for i in range(5))
            if input("Use the authorized plan on the present card? [yes/NO] ").strip() != "yes":
                raise EncoderError("Cancelled before opening the physical reader.")
            with PCSC(args.reader) as transport:
                body, _counter = Personalizer(transport, plan).run(bootstrap)
            body["operation_id"] = str(uuid.uuid4())
            outbox.save_new(plan.binding, body)
            receipt = outbox.load()
            if receipt is None:
                raise EncoderError("Exact receipt was not durably recorded; no confirmation was sent.")
        plan.alive()
        api.confirm(args.plan, receipt["body"], plan.binding)
        outbox.remove_confirmed()
        print("Card encoding verified and confirmed. Collection and sign-in are not authorized by this operation.")
        return 0
    except EncoderError as error:
        print(str(error), file=sys.stderr)
        return 1
    except Exception:
        # Exception repr/tracebacks can contain raw server data or chip buffers.
        print("Encoding did not complete. Keep any encrypted receipt and retry the same authorized plan; no diagnostic secrets were printed.", file=sys.stderr)
        return 1
    except (KeyboardInterrupt, EOFError):
        print("Stopped. Any durable encrypted exact confirmation is retained.", file=sys.stderr)
        return 130


if __name__ == "__main__":
    raise SystemExit(main())
