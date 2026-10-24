"""Run inside the isolated read-only converter test container."""
import hashlib
import json
import os
from pathlib import Path
import time
import requests

origin='http://127.0.0.1:8080'
for attempt in range(60):
    try:
        response=requests.get(origin+'/health', timeout=1)
        if response.status_code==200: break
    except requests.RequestException: pass
    time.sleep(0.25)
else: raise SystemExit('Converter health did not become ready.')
assert requests.post(origin+'/convert',json={},timeout=2).status_code==401
assert requests.post(origin+'/transcribe',json={},timeout=2).status_code==401
payload={'school_id':1,'version_id':1,'generation_id':'f8e72b11-6df8-4b15-8c68-1f6fa6aeaa18',
    'source_url':'https://outside.example/internal/content-source/1/1/f8e72b11-6df8-4b15-8c68-1f6fa6aeaa18?expires='+str(int(time.time())+300)+'&signature='+'a'*64,
    'source_sha256':'a'*64,'source_bytes':1,'extension':'txt'}
response=requests.post(origin+'/convert',json=payload,headers={'Authorization':'Bearer '+os.environ['CONVERTER_SERVICE_TOKEN']},timeout=3)
assert response.status_code==422 and response.json()=={'error':'invalid_source_url'}
manifest=json.loads(Path('/app/transcription-model.json').read_text())
for name, expected in manifest['files'].items():
    assert hashlib.sha256((Path(os.environ['TRANSCRIPTION_MODEL_PATH'])/name).read_bytes()).hexdigest()==expected
print('Read-only offline runtime, authenticated ingress and pinned model smoke passed.')

# The host creates these synthetic fixtures with the pinned dev dependencies.
# Conversions here use production dependencies, the image model, the read-only
# root and the normal bounded/offline child. No backend or inference network is used.
from navigateed_converter.config import Settings
from navigateed_converter.contracts import Source
from navigateed_converter.engine import Engine
class MemoryFetcher:
    def __init__(self, data): self.data=data
    def fetch(self, source, destination):
        with destination.open('xb') as output: output.write(self.data)
        destination.chmod(0o600)
settings=Settings.from_env(); settings.validate()
for extension in ['pdf','docx','pptx','xlsx','wav','mp4']:
    data=(Path('/tmp/smoke-inputs')/('fractions.'+extension)).read_bytes()
    request=Source.model_validate({**payload,'extension':extension,'source_bytes':len(data),'source_sha256':hashlib.sha256(data).hexdigest()})
    result=Engine(settings,MemoryFetcher(data)).run(request,extension in ['wav','mp4'])
    assert 'half' in result['markdown'].lower(), extension
    assert result['review_required'] is True
    assert result['source_sha256']==hashlib.sha256(data).hexdigest()
    assert list(settings.scratch_root.iterdir())==[], extension
print('Actual PDF, Word, slides, spreadsheet, audio and video converted inside the read-only offline image.')
