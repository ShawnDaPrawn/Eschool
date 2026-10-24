"""Fetch a public offline model at a reviewed revision and verify every artifact."""
from pathlib import Path
import argparse
import hashlib
import json
from huggingface_hub import snapshot_download

parser = argparse.ArgumentParser()
parser.add_argument('--path', type=Path, required=True)
arguments = parser.parse_args()
manifest = json.loads((Path(__file__).parent.parent / 'transcription-model.json').read_text())
snapshot_download(repo_id=manifest['repository'], revision=manifest['revision'],
    local_dir=str(arguments.path), allow_patterns=list(manifest['files']))
for name, expected in manifest['files'].items():
    path = arguments.path / name
    if not path.is_file() or hashlib.sha256(path.read_bytes()).hexdigest() != expected:
        raise SystemExit('Offline transcription model verification failed.')
print('Offline model verified at the pinned revision.')
