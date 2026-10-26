"""Bounded same-origin HTTPS, explicit bearer only, no proxy/cookie/redirect."""

import re
import ssl
from datetime import datetime
from urllib.error import HTTPError, URLError
from urllib.parse import urlsplit
from urllib.request import (HTTPRedirectHandler, HTTPSHandler, ProxyHandler,
                            Request, build_opener)

from .ev2 import EncoderError
from .plan import canonical, unique_json, uuid_text


class UnknownConfirmation(EncoderError):
    pass


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, *_args, **_kwargs):
        return None


class Api:
    def __init__(self, origin, credential, *, allow_local_http=False, opener=None):
        parts = urlsplit(origin)
        try:
            port = parts.port
        except ValueError:
            raise EncoderError("Invalid server origin.") from None
        if (not parts.hostname or parts.username is not None or parts.password is not None
                or parts.path not in ("", "/") or parts.query or parts.fragment
                or (port is not None and not 0 < port <= 65535)
                or (parts.scheme != "https" and not (allow_local_http and parts.scheme == "http"
                    and parts.hostname in ("127.0.0.1", "::1", "localhost")))):
            raise EncoderError("Use an HTTPS server origin; local HTTP needs the explicit fixture option.")
        match = re.fullmatch(r"NED1\.([1-9][0-9]*)\.([a-f0-9-]{36})\.([a-f0-9]{64})", credential) if isinstance(credential, str) else None
        if match is None or not uuid_text(match[2]) or len(credential) >= 160:
            raise EncoderError("A valid encoder-only device credential is required.")
        self.school_id, self.device_id = int(match[1]), match[2]
        self.origin, self._credential = origin.rstrip("/"), credential
        self.opener = opener or build_opener(ProxyHandler({}), NoRedirect(),
                                             HTTPSHandler(context=ssl.create_default_context()))

    def __repr__(self):
        return "<EncoderApi credential=redacted>"

    def _request(self, plan, body=None):
        if not uuid_text(plan):
            raise EncoderError("A canonical plan UUID is required.")
        url = self.origin + "/api/v2/card-encoder/plans/" + plan + ("/confirm" if body is not None else "")
        headers = {"Authorization": "Bearer " + self._credential, "Accept": "application/json",
                   "Cache-Control": "no-store", "User-Agent": "NavigateEd-Local-Encoder/1"}
        if body is not None:
            headers["Content-Type"] = "application/json"
        request = Request(url, data=None if body is None else canonical(body), headers=headers,
                          method="GET" if body is None else "POST")
        try:
            with self.opener.open(request, timeout=20) as response:
                if response.status != 200 or response.geturl() != url:
                    raise EncoderError("Unexpected encoder HTTP destination or status.")
                raw = response.read(65537)
                if len(raw) > 65536:
                    raise EncoderError("Encoder response exceeds its bound.")
                value = unique_json(raw)
                if (not isinstance(value, dict) or value.get("error") is not False
                        or type(value.get("code")) is not int or value["code"] != 200
                        or not isinstance(value.get("data"), dict)):
                    raise EncoderError("Malformed successful encoder response.")
                return value["data"]
        except HTTPError as error:
            # Do not render response bodies, URI diagnostics, bearer or headers.
            if body is not None and (error.code in (408, 425, 429) or error.code >= 500):
                raise UnknownConfirmation("Confirmation outcome is unknown; keep and retry the encrypted exact receipt.") from None
            raise EncoderError("Encoder authorization or request refused; encrypted receipt is retained.") from None
        except (URLError, TimeoutError, OSError, EncoderError):
            if body is not None:
                raise UnknownConfirmation("Confirmation outcome is unknown; keep and retry the encrypted exact receipt.") from None
            raise EncoderError("Could not obtain a fresh authorized encoding plan.") from None

    def download(self, plan):
        return self._request(plan)

    def confirm(self, plan, body, binding):
        if (set(body) != {"operation_id", "picc", "mac"} or not uuid_text(body["operation_id"])
                or not isinstance(body["picc"], str) or not re.fullmatch(r"[0-9A-F]{32}", body["picc"])
                or not isinstance(body["mac"], str) or not re.fullmatch(r"[0-9A-F]{16}", body["mac"])):
            raise EncoderError("Invalid exact confirmation receipt.")
        result = self._request(plan, body)
        timestamp = result.get("encoded_at")
        try:
            valid_timestamp = (isinstance(timestamp, str) and len(timestamp) <= 64
                               and datetime.fromisoformat(timestamp.replace("Z", "+00:00")).tzinfo is not None)
        except (ValueError, TypeError):
            valid_timestamp = False
        if (result.get("encoded") is not True or result.get("plan_id") != plan
                or result.get("card_id") != binding["card_id"]
                or type(result.get("revision")) is not int or result["revision"] != binding["revision"]
                or type(result.get("verified_counter")) is not int or not 0 <= result["verified_counter"] <= 0xFFFFFF
                or not valid_timestamp
                or result.get("collection_authorized") is not False or result.get("sign_in_authorized") is not False):
            raise UnknownConfirmation("Unbound confirmation response; keep the exact encrypted receipt.")
        return result
