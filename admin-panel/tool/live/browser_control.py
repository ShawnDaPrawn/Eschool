"""Loopback-only synthetic publisher for the full-app browser transport check.

No production configuration/credentials are read. Start local Reverb with these
qualification credentials; this helper signs only the fixed test channels.
"""
import hashlib
import hmac
import json
import time
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from urllib.parse import parse_qs, urlencode, urlparse
from urllib.request import Request, urlopen

KEY = 'qualification-browser-key'
SECRET = b'qualification-browser-secret-32-characters'
LEASE = '75133b39-0476-4ba0-a627-a152d042687a'
CHANNEL = f'private-ne.school.1.group.7.user.12.session.{LEASE}'

class Handler(BaseHTTPRequestHandler):
    def do_GET(self):
        origin = self.headers.get('Origin', 'http://localhost')
        if urlparse(origin).hostname not in ('localhost', '127.0.0.1'):
            self.send_error(403)
            return
        query = parse_qs(urlparse(self.path).query)
        kind = query.get('type', ['presence'])[0]
        revision = int(query.get('revision', ['1'])[0])
        if kind == 'presence':
            payload = {'type': 'group.presence', 'school_id': 1, 'group_id': 7, 'revision': revision,
                       'online_count': int(query.get('count', ['3'])[0])}
        elif kind == 'group':
            payload = {'type': 'activity.group_changed', 'school_id': 1, 'group_id': 7, 'revision': revision}
        elif kind == 'house':
            account = int(query.get('account', ['11'])[0])
            if account not in (11, 12, 21):
                self.send_error(422)
                return
            payload = {'type': 'house_points.changed', 'school_id': 1, 'session_year_id': 7,
                       'house_id': 3, 'revision': revision}
        else:
            self.send_error(422)
            return
        channel = f'private-ne.school.1.user.{account}.session.{LEASE}' if kind == 'house' else CHANNEL
        body = json.dumps({'name': 'navigateed.invalidate', 'channels': [channel], 'data': json.dumps(payload)}).encode()
        path = '/apps/qualification-browser/events'
        params = {'auth_key': KEY, 'auth_timestamp': str(int(time.time())), 'auth_version': '1.0', 'body_md5': hashlib.md5(body).hexdigest()}
        signed = urlencode(sorted(params.items()))
        params['auth_signature'] = hmac.new(SECRET, ('POST\n' + path + '\n' + signed).encode(), hashlib.sha256).hexdigest()
        try:
            with urlopen(Request('http://127.0.0.1:8088' + path + '?' + urlencode(sorted(params.items())), data=body,
                                 headers={'Content-Type': 'application/json'}), timeout=5) as response:
                response.read()
            self.send_response(200)
            self.send_header('Access-Control-Allow-Origin', origin)
            self.send_header('Vary', 'Origin')
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(b'{"ok":true}')
        except Exception:
            self.send_error(503)

    def log_message(self, *_args):
        pass

ThreadingHTTPServer(('127.0.0.1', 8089), Handler).serve_forever()
