import hashlib
import time
from urllib.parse import urlsplit
import pytest
import requests
from navigateed_converter.errors import ConversionError
from navigateed_converter.source import SourceFetcher

class Response:
    def __init__(self, data, status=200, headers=None):
        self.data, self.status_code, self.headers = data, status, headers or {}
    def __enter__(self): return self
    def __exit__(self, *args): pass
    def iter_content(self, chunk_size):
        for index in range(0, len(self.data), chunk_size): yield self.data[index:index+chunk_size]

class Session:
    trust_env = True
    def __init__(self, response): self.response = response; self.calls = []
    def get(self, *args, **kwargs): self.calls.append((args, kwargs)); return self.response

@pytest.mark.parametrize('transform', [
    lambda url: url.replace('backend.example', 'evil.example'),
    lambda url: url.replace('backend.example', 'backend.example.evil.test'),
    lambda url: url.replace('https://', 'http://'),
    lambda url: url.replace('https://', 'https://user:pass@'),
    lambda url: url + '#fragment',
    lambda url: url + '&expires=9999999999',
    lambda url: url + '&extra=value',
    lambda url: url.replace('/1/9/', '/2/9/'),
    lambda url: url.replace('/1/9/', '/1/8/'),
    lambda url: url.replace('/internal/', '/%69nternal/'),
    lambda url: url.replace('/internal/', '/internal/../internal/'),
    lambda url: ' '+url,
    lambda url: url.replace('backend.example', 'backend.example:443'),
])
def test_only_exact_school_item_generation_and_origin_can_be_fetched(settings, source, tmp_path, transform):
    row = source()
    invalid = row.model_copy(update={'source_url': transform(row.source_url)})
    session = Session(Response(b''))
    with pytest.raises(ConversionError, match='invalid_source_url'):
        SourceFetcher(settings, session).fetch(invalid, tmp_path / 'source')
    assert session.calls == []

@pytest.mark.parametrize('expiry', [lambda: int(time.time())-1, lambda: int(time.time())+1000])
def test_expired_or_excessively_long_source_capabilities_are_refused(settings, source, expiry):
    row = source()
    row = row.model_copy(update={'source_url': row.source_url.replace(urlsplit(row.source_url).query.split('&')[0], 'expires='+str(expiry()))})
    with pytest.raises(ConversionError, match='invalid_source_url'): SourceFetcher(settings).validate_url(row)

def test_streamed_source_is_checksum_pinned_and_never_follows_redirects_or_environment_proxies(settings, source, tmp_path):
    data = b'Private lesson text'
    session = Session(Response(data))
    fetcher = SourceFetcher(settings, session)
    target = tmp_path / 'source'
    fetcher.fetch(source(data), target)
    assert target.read_bytes() == data
    assert target.stat().st_mode & 0o777 == 0o600
    assert session.trust_env is False
    assert session.calls[0][1]['allow_redirects'] is False
    assert session.calls[0][1]['headers']['Accept-Encoding'] == 'identity'

@pytest.mark.parametrize('response,code', [
    (Response(b'', 302, {'Location': 'https://evil.test'}), 'source_unavailable'),
    (Response(b'bad'), 'source_checksum_changed'),
    (Response(b'too many bytes'*9), 'source_too_large'),
    (Response(b'Private lesson text', headers={'Content-Encoding':'gzip'}), 'source_encoding_refused'),
    (Response(b'Private lesson text', headers={'Content-Length':'10'}), 'source_size_changed'),
])
def test_changed_redirected_or_unbounded_sources_fail_closed(settings, source, tmp_path, response, code):
    with pytest.raises(ConversionError, match=code): SourceFetcher(settings, Session(response)).fetch(source(), tmp_path / 'source')
