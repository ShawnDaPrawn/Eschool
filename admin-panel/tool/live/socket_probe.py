"""Small standard-library Pusher-protocol qualification client (local CI only)."""
import base64
import hashlib
import hmac
import json
import os
import socket
import struct
import sys
from pathlib import Path

port, key, secret, channel, ready, output = sys.argv[1:]
sock = socket.create_connection(('127.0.0.1', int(port)), timeout=10)
sock.settimeout(10)
nonce = base64.b64encode(os.urandom(16)).decode()
request = (f'GET /app/{key}?protocol=7&client=qualification&version=1.0 HTTP/1.1\r\n'
           f'Host: localhost:{port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n'
           f'Sec-WebSocket-Key: {nonce}\r\nSec-WebSocket-Version: 13\r\nOrigin: http://localhost\r\n\r\n')
sock.sendall(request.encode())
buffer = b''
while b'\r\n\r\n' not in buffer:
    buffer += sock.recv(4096)
head, buffer = buffer.split(b'\r\n\r\n', 1)
assert b' 101 ' in head, head
expected = base64.b64encode(hashlib.sha1((nonce + '258EAFA5-E914-47DA-95CA-C5AB0DC85B11').encode()).digest())
assert expected.lower() in head.lower()

def take(size):
    global buffer
    while len(buffer) < size:
        block = sock.recv(4096)
        if not block:
            raise RuntimeError('socket closed')
        buffer += block
    result, buffer = buffer[:size], buffer[size:]
    return result

def receive():
    first, second = take(2)
    length = second & 127
    if length == 126:
        length = struct.unpack('!H', take(2))[0]
    elif length == 127:
        length = struct.unpack('!Q', take(8))[0]
    assert length <= 16384
    mask = take(4) if second & 128 else None
    body = take(length)
    if mask:
        body = bytes(value ^ mask[index % 4] for index, value in enumerate(body))
    assert first & 15 == 1, first
    return json.loads(body)

def send(body):
    payload = json.dumps(body).encode()
    mask = os.urandom(4)
    prefix = bytes([0x81, 0x80 | len(payload)]) if len(payload) < 126 else bytes([0x81, 0xfe]) + struct.pack('!H', len(payload))
    sock.sendall(prefix + mask + bytes(value ^ mask[index % 4] for index, value in enumerate(payload)))

connected = receive()
assert connected['event'] == 'pusher:connection_established', connected
socket_id = json.loads(connected['data'])['socket_id']
auth = key + ':' + hmac.new(secret.encode(), (socket_id + ':' + channel).encode(), hashlib.sha256).hexdigest()
send({'event': 'pusher:subscribe', 'data': {'channel': channel, 'auth': auth}})
subscribed = receive()
assert subscribed['event'] == 'pusher_internal:subscription_succeeded', subscribed
send({'event': 'client-forged-invalidation', 'channel': channel, 'data': {'type': 'house_points.changed'}})
refused = receive()
assert refused['event'] == 'pusher:error', refused
assert json.loads(refused['data'])['code'] == 4301, refused
Path(ready).write_text('ready')
while True:
    event = receive()
    if event['event'] == 'pusher:ping':
        send({'event': 'pusher:pong', 'data': {}})
    elif event['event'] == 'navigateed.invalidate':
        Path(output).write_text(json.dumps(event))
        break
sock.close()
