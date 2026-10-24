import base64
import gzip
import os
from pathlib import Path
import subprocess
from dataclasses import replace
import pytest
from navigateed_converter.engine import Engine
from conftest import MemoryFetcher

@pytest.mark.parametrize('extension', ['wav', 'mp4'])
def test_real_offline_audio_and_video_transcription(settings, source, tmp_path, extension):
    configured = os.environ.get('TRANSCRIPTION_MODEL_PATH')
    if not configured:
        pytest.skip('Offline model not configured; CI downloads the pinned model before this cohort.')
    model = Path(configured)
    assert model.is_dir(), 'Run tool/download_model.py before the transcription cohort.'
    audio = gzip.decompress(base64.b64decode((Path(__file__).parent / 'fixtures/fractions.wav.gz.b64').read_text()))
    if extension == 'mp4':
        wav, video = tmp_path / 'source.wav', tmp_path / 'source.mp4'
        wav.write_bytes(audio)
        subprocess.run(['ffmpeg', '-nostdin', '-v', 'error', '-f', 'lavfi', '-i', 'color=c=black:s=320x240:r=1', '-i', str(wav),
            '-shortest', '-c:v', 'libx264', '-c:a', 'alac', str(video)], check=True, timeout=30)
        data = video.read_bytes()
    else:
        data = audio
    result = Engine(replace(settings, model_path=model), MemoryFetcher(data)).run(source(data, extension), True)
    assert 'fraction' in result['markdown'].lower()
    assert 'half' in result['markdown'].lower()
    assert result['markdown'].startswith('# Transcript')
    assert 1 <= result['duration_seconds'] <= 30
    assert result['language'] == 'en'
    assert result['review_required'] is True
    assert list(settings.scratch_root.iterdir()) == []


def test_remaining_duration_limit_refuses_before_model_inference_and_cleans_scratch(settings, source, tmp_path):
    from navigateed_converter.errors import ConversionError
    model=tmp_path/'empty-model'
    model.mkdir()
    audio=gzip.decompress(base64.b64decode((Path(__file__).parent / 'fixtures/fractions.wav.gz.b64').read_text()))
    request=source(audio,'wav',maximum_seconds=1)
    with pytest.raises(ConversionError,match='transcription_duration_limit'):
        Engine(replace(settings,model_path=model),MemoryFetcher(audio)).run(request,True)
    assert list(settings.scratch_root.iterdir())==[]
