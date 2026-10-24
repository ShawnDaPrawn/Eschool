import json
import os
from pathlib import Path
import subprocess
import sys
import tempfile
from .archives import validate_file
from .config import Settings
from .contracts import Source, DOCUMENTS, MEDIA
from .errors import ConversionError
from .source import SourceFetcher

class Engine:
    def __init__(self, settings: Settings, fetcher=None):
        self.settings = settings
        self.fetcher = fetcher or SourceFetcher(settings)

    def run(self, source: Source, transcribe: bool = False) -> dict:
        if transcribe and source.extension not in MEDIA:
            raise ConversionError('unsupported_transcription_source')
        if not transcribe and source.extension not in DOCUMENTS:
            raise ConversionError('use_transcription_gateway')
        if transcribe and (self.settings.model_path is None or not self.settings.model_path.is_dir()):
            raise ConversionError('transcription_unavailable', 503)
        with tempfile.TemporaryDirectory(prefix='convert-', dir=self.settings.scratch_root) as temporary:
            scratch = Path(temporary)
            source_path = scratch / ('source.' + source.extension)
            output_path = scratch / 'result.json'
            self.fetcher.fetch(source, source_path)
            validate_file(source_path, source.extension)
            env = {'PATH': os.defpath, 'LANG': 'C.UTF-8', 'PYTHONPATH': str(Path(__file__).parent.parent),
                   'HF_HUB_OFFLINE': '1', 'TRANSFORMERS_OFFLINE': '1', 'OMP_NUM_THREADS': '2',
                   'OPENBLAS_NUM_THREADS': '2', 'MKL_NUM_THREADS': '2', 'NUMEXPR_NUM_THREADS': '2'}
            if transcribe:
                env['TRANSCRIPTION_MODEL_PATH'] = str(self.settings.model_path)
            command = [sys.executable, '-m', 'navigateed_converter.worker', str(source_path), str(output_path),
                       'transcribe' if transcribe else 'convert', str(min(self.settings.max_media_seconds, source.maximum_seconds))]
            try:
                result = subprocess.run(command, cwd=scratch, env=env, stdin=subprocess.DEVNULL,
                    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=self.settings.worker_seconds, check=False)
            except subprocess.TimeoutExpired:
                raise ConversionError('conversion_timeout', 504) from None
            if result.returncode == 42:
                raise ConversionError('transcription_duration_limit')
            if result.returncode != 0 or not output_path.is_file() or output_path.stat().st_size > self.settings.max_markdown_bytes * 3:
                raise ConversionError('conversion_failed')
            try:
                output = json.loads(output_path.read_text(encoding='utf-8'))
                markdown = output['markdown']
                if not isinstance(markdown, str) or not markdown.strip() or len(markdown.encode()) > self.settings.max_markdown_bytes:
                    raise ValueError('output')
                seconds = output.get('duration_seconds', 0)
                if type(seconds) is not int or seconds < 0 or seconds > min(self.settings.max_media_seconds, source.maximum_seconds):
                    raise ValueError('duration')
            except (ValueError, KeyError, TypeError, UnicodeError):
                raise ConversionError('invalid_conversion_output') from None
            return {'markdown': markdown, 'source_sha256': source.source_sha256,
                'generation_id': source.generation(), 'duration_seconds': seconds,
                'language': output.get('language'), 'review_required': True}
