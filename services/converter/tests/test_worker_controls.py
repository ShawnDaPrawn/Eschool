import socket
from navigateed_converter import worker

def test_conversion_child_refuses_internet_sockets_before_any_dns_or_provider_call(monkeypatch):
    original_socket, original_connection = socket.socket, socket.create_connection
    monkeypatch.setattr(socket, 'socket', original_socket)
    monkeypatch.setattr(socket, 'create_connection', original_connection)
    worker.disable_network()
    for action in [lambda: socket.socket(socket.AF_INET), lambda: socket.socket(socket.AF_INET6), lambda: socket.create_connection(('example.invalid', 443))]:
        try:
            action()
            raise AssertionError('Worker network access was allowed')
        except PermissionError:
            pass
