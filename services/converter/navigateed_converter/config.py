from dataclasses import dataclass
import os
from pathlib import Path
from urllib.parse import urlsplit

@dataclass(frozen=True)
class Settings:
    service_token: str
    source_origin: str
    scratch_root: Path
    model_path: Path | None = None
    allow_http: bool = False
    max_source_bytes: int = 25 * 1024 * 1024
    max_markdown_bytes: int = 2 * 1024 * 1024
    worker_seconds: int = 210
    download_seconds: int = 45
    max_media_seconds: int = 1800

    def validate(self) -> None:
        if len(self.service_token.encode()) < 32 or self.service_token.strip() != self.service_token:
            raise ValueError('A private converter token of at least 32 bytes is required.')
        origin = urlsplit(self.source_origin)
        if (origin.scheme not in (['https', 'http'] if self.allow_http else ['https'])
            or not origin.hostname or origin.username or origin.password or origin.query
            or origin.fragment or origin.path not in ['', '/']):
            raise ValueError('Configure one trusted backend source origin.')
        self.scratch_root.mkdir(parents=True, exist_ok=True, mode=0o700)
        os.chmod(self.scratch_root, 0o700)

    @classmethod
    def from_env(cls) -> 'Settings':
        return cls(service_token=os.environ.get('CONVERTER_SERVICE_TOKEN', ''),
            source_origin=os.environ.get('CONVERTER_SOURCE_ORIGIN', ''),
            scratch_root=Path(os.environ.get('CONVERTER_SCRATCH_ROOT', '/tmp/navigateed-converter')),
            model_path=Path(os.environ['TRANSCRIPTION_MODEL_PATH']) if os.environ.get('TRANSCRIPTION_MODEL_PATH') else None,
            allow_http=os.environ.get('CONVERTER_ALLOW_HTTP_SOURCE') == 'true')
