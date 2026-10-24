from fastapi.testclient import TestClient
import pytest
from navigateed_converter.app import create_app
from navigateed_converter.config import Settings
from navigateed_converter.errors import ConversionError

class FakeEngine:
    calls = []
    def __init__(self, settings): pass
    def run(self, source, transcribe=False):
        self.calls.append((source, transcribe))
        return {'markdown':'# Lesson', 'generation_id':source.generation(), 'source_sha256':source.source_sha256,
            'duration_seconds':0, 'language':None, 'review_required':True}

def test_no_service_can_start_with_an_empty_token_or_unapproved_plain_http(tmp_path):
    with pytest.raises(ValueError): Settings('', 'https://backend.example', tmp_path).validate()
    with pytest.raises(ValueError): Settings('x'*32, 'http://backend.example', tmp_path).validate()

def test_authentication_precedes_parsing_and_internal_docs_are_absent(settings, source):
    with TestClient(create_app(settings, FakeEngine)) as client:
        assert client.get('/health').json() == {'status':'ok'}
        assert client.get('/docs').status_code == 404
        assert client.post('/convert', content=b'not json').status_code == 401
        assert client.post('/transcribe', json=source().model_dump()).status_code == 401
        assert client.post('/convert', json=source().model_dump(), headers={'Authorization':'Bearer wrong'}).status_code == 401
        response = client.post('/convert', json=source().model_dump(), headers={'Authorization':'Bearer '+settings.service_token})
        assert response.status_code == 200
        assert response.json()['review_required'] is True
        assert response.headers['cache-control'] == 'private, no-store'

def test_oversized_and_unknown_or_wrongly_typed_fields_are_refused(settings, source):
    headers = {'Authorization':'Bearer '+settings.service_token}
    with TestClient(create_app(settings, FakeEngine)) as client:
        assert client.post('/convert', content=b'x'*16385, headers=headers).status_code == 413
        for data in [source().model_dump() | {'service_key':'hidden'}, source().model_dump() | {'school_id':True}, source().model_dump() | {'school_id':'1'}, source().model_dump() | {'generation_id':'x'*36}]:
            assert client.post('/convert', json=data, headers=headers).status_code == 422

def test_parser_failures_do_not_return_or_log_private_payloads(settings, source, caplog):
    class BrokenEngine(FakeEngine):
        def run(self, *args): raise RuntimeError('PRIVATE document body and key')
    with TestClient(create_app(settings, BrokenEngine)) as client:
        response = client.post('/convert', json=source().model_dump(), headers={'Authorization':'Bearer '+settings.service_token})
        assert response.status_code == 500
        assert response.json() == {'error':'conversion_failed'}
        assert 'PRIVATE' not in caplog.text
