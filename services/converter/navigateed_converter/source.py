import hashlib
import hmac
from pathlib import Path
import re
import time
from urllib.parse import parse_qsl, urlsplit
import requests
from .config import Settings
from .contracts import Source
from .errors import ConversionError

class SourceFetcher:
    def __init__(self, settings: Settings, session=None):
        self.settings = settings
        self.session = session or requests.Session()
        self.session.trust_env = False

    def validate_url(self, source: Source) -> str:
        try:
            if any(ord(character) < 33 or ord(character) > 126 for character in source.source_url):
                raise ValueError('characters')
            url = urlsplit(source.source_url)
            origin = urlsplit(self.settings.source_origin)
            if (url.scheme, url.hostname, url.port) != (origin.scheme, origin.hostname, origin.port):
                raise ValueError('origin')
            expected = f'/internal/content-source/{source.school_id}/{source.version_id}/{source.generation()}'
            if url.path != expected or url.username or url.password or url.fragment or '\\' in source.source_url:
                raise ValueError('path')
            query = parse_qsl(url.query, keep_blank_values=True, strict_parsing=True)
            if len(query) != 2 or {key for key, _ in query} != {'expires', 'signature'}:
                raise ValueError('query')
            fields = dict(query)
            if not re.fullmatch(r'[0-9]{1,12}', fields['expires']) or not re.fullmatch(r'[a-f0-9]{64}', fields['signature']):
                raise ValueError('signature')
            expiry = int(fields['expires'])
            if expiry <= time.time() or expiry > time.time() + 330:
                raise ValueError('expiry')
        except (ValueError, TypeError):
            raise ConversionError('invalid_source_url', 422) from None
        return source.source_url

    def fetch(self, source: Source, destination: Path) -> None:
        url = self.validate_url(source)
        if source.source_bytes > self.settings.max_source_bytes:
            raise ConversionError('source_too_large')
        digest = hashlib.sha256()
        count = 0
        deadline = time.monotonic() + self.settings.download_seconds
        try:
            with self.session.get(url, stream=True, allow_redirects=False, timeout=(3, 15),
                                  headers={'Accept-Encoding': 'identity'}) as response:
                if response.status_code != 200:
                    raise ConversionError('source_unavailable', 409)
                if response.headers.get('Content-Encoding', 'identity').lower() not in ['', 'identity']:
                    raise ConversionError('source_encoding_refused')
                length = response.headers.get('Content-Length')
                if length is not None and (not re.fullmatch(r'[0-9]{1,12}', length) or int(length) != source.source_bytes):
                    raise ConversionError('source_size_changed', 409)
                with destination.open('xb') as output:
                    destination.chmod(0o600)
                    for chunk in response.iter_content(chunk_size=65536):
                        if time.monotonic() > deadline:
                            raise ConversionError('source_timeout', 504)
                        count += len(chunk)
                        if count > min(source.source_bytes, self.settings.max_source_bytes):
                            raise ConversionError('source_too_large')
                        digest.update(chunk)
                        output.write(chunk)
        except requests.RequestException:
            raise ConversionError('source_unavailable', 409) from None
        if count != source.source_bytes or not hmac.compare_digest(digest.hexdigest(), source.source_sha256):
            raise ConversionError('source_checksum_changed', 409)
