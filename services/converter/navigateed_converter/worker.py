"""One bounded child per file, with no network or inherited service credentials."""
import json
import math
import os
from pathlib import Path
import resource
import socket
import statistics
import subprocess
import sys
import wave


class MediaLimit(Exception):
    pass


def disable_network() -> None:
    original = socket.socket
    class OfflineSocket(original):
        def __init__(self, family=socket.AF_INET, *args, **kwargs):
            if family in (socket.AF_INET, socket.AF_INET6):
                raise PermissionError('Conversion workers have no network access.')
            super().__init__(family, *args, **kwargs)
    socket.socket = OfflineSocket
    def refused(*args, **kwargs):
        raise PermissionError('Conversion workers have no network access.')
    socket.create_connection = refused


def pdf_headings(markdown: str, source: Path) -> str:
    from pdfminer.high_level import extract_pages
    from pdfminer.layout import LTTextContainer, LTChar
    lines = []
    sizes = []
    for page_index, page in enumerate(extract_pages(str(source))):
        if page_index >= 100:
            raise ValueError('PDF page limit exceeded')
        for element in page:
            if isinstance(element, LTTextContainer):
                for line in element:
                    chars = [character for character in line if isinstance(character, LTChar)] if hasattr(line, '__iter__') else []
                    if chars:
                        size = statistics.median(character.size for character in chars)
                        sizes.extend([size] * min(len(chars), 1000))
                        lines.append((line.get_text().strip(), size))
    if not sizes:
        return markdown
    body = statistics.median(sizes)
    headings = {text for text, size in lines if 3 <= len(text) <= 160 and size >= max(13, body * 1.2)}
    return '\n'.join('## ' + line.strip() if line.strip() in headings and not line.lstrip().startswith('#') else line for line in markdown.splitlines())


def document(source: Path) -> dict:
    from markitdown import MarkItDown
    result = MarkItDown(enable_plugins=False).convert(str(source))
    markdown = result.text_content
    if source.suffix.lower() == '.pdf':
        markdown = pdf_headings(markdown, source)
    return {'markdown': markdown, 'duration_seconds': 0, 'language': None}


def transcription(source: Path, maximum: int) -> dict:
    probe = subprocess.run(['ffprobe', '-v', 'error', '-protocol_whitelist', 'file,pipe', '-format_whitelist', 'wav,mp3,mov,matroska,webm,ogg,aac',
        '-select_streams', 'a:0', '-show_entries', 'stream=duration:format=duration', '-of', 'json', str(source)], capture_output=True,
        stdin=subprocess.DEVNULL, timeout=15, check=True)
    if len(probe.stdout) > 32768:
        raise ValueError('Invalid media metadata')
    metadata = json.loads(probe.stdout)
    streams = metadata.get('streams', [])
    if not streams: raise ValueError('No audio stream')
    seconds = float(streams[0].get('duration', metadata.get('format', {}).get('duration', 'nan')))
    if not math.isfinite(seconds) or seconds <= 0 or seconds > maximum:
        raise MediaLimit('Media duration exceeds its limit')
    wav = source.parent / 'audio.wav'
    subprocess.run(['ffmpeg', '-nostdin', '-v', 'error', '-protocol_whitelist', 'file,pipe', '-format_whitelist', 'wav,mp3,mov,matroska,webm,ogg,aac', '-i', str(source),
        '-t', str(maximum + 1), '-map', '0:a:0', '-ac', '1', '-ar', '16000', '-acodec', 'pcm_s16le', '-f', 'wav', str(wav)],
        stdin=subprocess.DEVNULL, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=60, check=True)
    from faster_whisper import WhisperModel
    model = WhisperModel(os.environ['TRANSCRIPTION_MODEL_PATH'], device='cpu', compute_type='int8',
        cpu_threads=2, num_workers=1, local_files_only=True)
    import numpy as np
    # Use the provider's documented waveform API. Decoding remains bounded in
    # our ffmpeg process rather than relying on version-specific PyAV arguments.
    with wave.open(str(wav), 'rb') as audio:
        frames = audio.getnframes()
        if audio.getframerate() != 16000 or audio.getnchannels() != 1 or audio.getsampwidth() != 2 or not 0 < frames <= maximum * 16000:
            raise MediaLimit('Invalid canonical audio')
        raw = audio.readframes(frames)
        if len(raw) != frames * 2:
            raise ValueError('Truncated canonical audio')
    waveform = np.frombuffer(raw, dtype=np.int16).astype(np.float32) / 32768.0
    segments, info = model.transcribe(waveform, beam_size=1, vad_filter=True)
    text = '\n\n'.join(segment.text.strip() for segment in segments if segment.text.strip())
    if not text:
        raise ValueError('No speech was detected')
    return {'markdown': '# Transcript\n\n' + text, 'duration_seconds': math.ceil(frames / 16000), 'language': info.language}


def main() -> None:
    resource.setrlimit(resource.RLIMIT_AS, (3 * 1024 ** 3, 3 * 1024 ** 3))
    resource.setrlimit(resource.RLIMIT_CPU, (180, 180))
    resource.setrlimit(resource.RLIMIT_FSIZE, (64 * 1024 * 1024, 64 * 1024 * 1024))
    resource.setrlimit(resource.RLIMIT_NOFILE, (128, 128))
    os.umask(0o077)
    disable_network()
    source, output, mode, maximum = sys.argv[1:]
    try:
        result = transcription(Path(source), int(maximum)) if mode == 'transcribe' else document(Path(source))
    except MediaLimit:
        raise SystemExit(42) from None
    with Path(output).open('x', encoding='utf-8') as handle:
        json.dump(result, handle, ensure_ascii=False)

if __name__ == '__main__':
    main()
