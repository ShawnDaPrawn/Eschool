import asyncio
from contextlib import asynccontextmanager
import hmac
import json
import logging
from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from pydantic import ValidationError
from .config import Settings
from .contracts import Source
from .engine import Engine
from .errors import ConversionError


def create_app(settings: Settings | None = None, engine_factory=Engine) -> FastAPI:
    lock = asyncio.Lock()
    @asynccontextmanager
    async def lifespan(application):
        configured = settings or Settings.from_env()
        configured.validate()
        application.state.settings = configured
        application.state.engine = engine_factory(configured)
        yield
    application = FastAPI(lifespan=lifespan, docs_url=None, redoc_url=None, openapi_url=None)

    @application.get('/health')
    async def health():
        return {'status': 'ok'}

    async def convert(request: Request, transcribe: bool):
        configured = request.app.state.settings
        if not hmac.compare_digest(request.headers.get('authorization', '').encode(), ('Bearer ' + configured.service_token).encode()):
            return JSONResponse({'error': 'unauthenticated'}, status_code=401)
        body = bytearray()
        async for chunk in request.stream():
            body.extend(chunk)
            if len(body) > 16384:
                return JSONResponse({'error': 'request_too_large'}, status_code=413)
        try:
            data = Source.model_validate(json.loads(body))
            data.generation()
        except (ValidationError, ValueError, UnicodeError):
            return JSONResponse({'error': 'invalid_request'}, status_code=422)
        if lock.locked():
            return JSONResponse({'error': 'converter_busy'}, status_code=503, headers={'Retry-After': '5'})
        async with lock:
            try:
                result = await asyncio.to_thread(request.app.state.engine.run, data, transcribe)
                return JSONResponse(result, headers={'Cache-Control': 'private, no-store'})
            except ConversionError as error:
                return JSONResponse({'error': error.code}, status_code=error.status)
            except Exception as error:
                logging.getLogger(__name__).warning('conversion_failed', extra={'failure_type': type(error).__name__})
                return JSONResponse({'error': 'conversion_failed'}, status_code=500)

    @application.post('/convert')
    async def document(request: Request):
        return await convert(request, False)

    @application.post('/transcribe')
    async def transcribe(request: Request):
        return await convert(request, True)

    return application

app = create_app()
