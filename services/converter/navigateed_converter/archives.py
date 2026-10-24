from pathlib import Path, PurePosixPath
import zipfile
from .errors import ConversionError

REQUIRED = {'docx': 'word/document.xml', 'pptx': 'ppt/presentation.xml', 'xlsx': 'xl/workbook.xml'}

def validate_file(path: Path, extension: str) -> None:
    with path.open('rb') as handle:
        magic = handle.read(1024)
    if extension in REQUIRED:
        try:
            with zipfile.ZipFile(path) as archive:
                members = archive.infolist()
                if len(members) > 4000 or '[Content_Types].xml' not in archive.namelist() or REQUIRED[extension] not in archive.namelist():
                    raise ConversionError('invalid_office_archive')
                total = 0
                for member in members:
                    name = member.filename
                    parts = PurePosixPath(name).parts
                    total += member.file_size
                    if (member.compress_type not in (zipfile.ZIP_STORED, zipfile.ZIP_DEFLATED) or member.flag_bits & 1 or (member.external_attr >> 16) & 0o170000 == 0o120000 or '\\' in name or name.startswith('/') or '..' in parts
                        or (parts and ':' in parts[0]) or member.file_size > 20 * 1024 * 1024
                        or total > 100 * 1024 * 1024 or member.file_size > max(1, member.compress_size) * 100
                        or 'vbaproject' in name.lower() or '/embeddings/' in name.lower()
                        or name.lower().endswith(('.exe', '.com', '.dll', '.vbs', '.js'))):
                        raise ConversionError('unsafe_office_archive')
                    if name.lower().endswith(('.xml', '.rels')):
                        # Reject DTD/entity payloads before XML parsers see the document.
                        data = archive.read(member)
                        if b'\x00' in data or b'<!doctype' in data.lower() or b'<!entity' in data.lower():
                            raise ConversionError('unsafe_office_xml')
        except (zipfile.BadZipFile, RuntimeError, OSError):
            raise ConversionError('invalid_office_archive') from None
    elif extension == 'pdf' and not magic.startswith(b'%PDF-'):
        raise ConversionError('invalid_pdf')
    elif extension in {'txt', 'md'}:
        try:
            path.read_text(encoding='utf-8')
        except UnicodeError:
            raise ConversionError('invalid_text_encoding') from None
    elif extension == 'wav' and not (magic.startswith(b'RIFF') and magic[8:12] == b'WAVE'):
        raise ConversionError('invalid_media')
    elif extension == 'mp3' and not (magic.startswith(b'ID3') or (len(magic) > 1 and magic[0] == 255 and magic[1] & 224 == 224)):
        raise ConversionError('invalid_media')
    elif extension in {'mp4', 'm4a'} and magic[4:8] != b'ftyp':
        raise ConversionError('invalid_media')
    elif extension == 'webm' and not magic.startswith(bytes.fromhex('1a45dfa3')):
        raise ConversionError('invalid_media')
    elif extension == 'ogg' and not magic.startswith(b'OggS'):
        raise ConversionError('invalid_media')
