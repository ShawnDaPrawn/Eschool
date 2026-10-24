import hashlib
from pathlib import Path
import time
from uuid import uuid4
import pytest
from navigateed_converter.config import Settings
from navigateed_converter.contracts import Source

@pytest.fixture
def settings(tmp_path):
    configured = Settings(service_token='private-test-token-' * 3, source_origin='https://backend.example', scratch_root=tmp_path / 'scratch')
    configured.validate()
    return configured

@pytest.fixture
def source():
    def make(data=b'Private lesson text', extension='txt', **changes):
        generation = str(uuid4())
        fields = dict(school_id=1, version_id=9, generation_id=generation, source_sha256=hashlib.sha256(data).hexdigest(), source_bytes=len(data), extension=extension,
            source_url=f'https://backend.example/internal/content-source/1/9/{generation}?expires={int(time.time()) + 300}&signature=' + 'a' * 64)
        fields.update(changes)
        return Source.model_validate(fields)
    return make

class MemoryFetcher:
    def __init__(self, data): self.data = data
    def fetch(self, source, destination):
        with destination.open('xb') as output:
            destination.chmod(0o600)
            output.write(self.data)
