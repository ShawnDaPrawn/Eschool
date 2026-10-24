from typing import Literal
from uuid import UUID
from pydantic import BaseModel, ConfigDict, Field

DOCUMENTS = {'pdf', 'docx', 'pptx', 'xlsx', 'txt', 'md'}
MEDIA = {'wav', 'mp3', 'mp4', 'm4a', 'webm', 'ogg'}

class Source(BaseModel):
    model_config = ConfigDict(extra='forbid', strict=True)
    school_id: int = Field(gt=0)
    version_id: int = Field(gt=0)
    generation_id: str = Field(pattern=r'^[a-fA-F0-9-]{36}$')
    source_url: str = Field(min_length=1, max_length=4096)
    source_sha256: str = Field(pattern=r'^[a-f0-9]{64}$')
    source_bytes: int = Field(gt=0, le=25 * 1024 * 1024)
    extension: Literal['pdf', 'docx', 'pptx', 'xlsx', 'txt', 'md', 'wav', 'mp3', 'mp4', 'm4a', 'webm', 'ogg']

    maximum_seconds: int = Field(default=1800, ge=1, le=1800)

    def generation(self) -> str:
        return str(UUID(self.generation_id))
