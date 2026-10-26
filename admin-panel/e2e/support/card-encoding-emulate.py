"""Synthetic browser proof only: decrypt real plan and run the independent card.

Never opens PC/SC, loads a reader library or writes a key/outbox to disk.
"""
import base64
import json
from pathlib import Path
import secrets
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'tool/cards/encoder'))
from cryptography.hazmat.primitives.asymmetric.x25519 import X25519PrivateKey
from nexencoder.plan import open_plan
from nexencoder.personalize import Personalizer
from tests.emulator import Card

value = json.load(sys.stdin)
plan = open_plan(value['envelope'], X25519PrivateKey.from_private_bytes(base64.b64decode(value['private'], validate=True)), value['plan'], allow_local_http=True)
bootstrap = tuple(secrets.token_bytes(16) for _ in range(5))
chip = Card(plan.uid, bootstrap)
proof, counter = Personalizer(chip, plan).run(bootstrap)
assert chip.changes == [1, 2, 3, 4, 0] and chip.writes == 2
print(json.dumps({'proof': proof, 'counter': counter, 'writes': chip.writes, 'changes': chip.changes}))
