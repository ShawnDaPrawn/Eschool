import 'dart:async';
import 'dart:convert';

import 'package:web_socket_channel/web_socket_channel.dart';

import '../api/api_client.dart';
import '../api/api_exception.dart';
import '../config/app_config.dart';
import 'live_invalidation.dart';
import 'live_socket_io.dart'
    if (dart.library.js_interop) 'live_socket_web.dart';

typedef LiveSocketFactory = WebSocketChannel Function(Uri uri, String origin);

/// An ephemeral, credential-bound Pusher session. It never stores event content.
class LiveSession {
  LiveSession({
    required this.client,
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
    String? baseUrl,
    this.origin = 'https://app.navigateed.co.za',
    LiveSocketFactory? socketFactory,
  }) : baseUrl = baseUrl ?? AppConfig.apiV2Url,
       _token = client.authToken(),
       _socketFactory = socketFactory ?? openLiveSocket;

  final ApiClient client;
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String baseUrl, origin, _token;
  final LiveSocketFactory _socketFactory;
  final _events = StreamController<LiveInvalidation>.broadcast();
  final _refreshes = StreamController<void>.broadcast();
  final _deniedGroups = StreamController<int>.broadcast();
  Stream<LiveInvalidation> get events => _events.stream;
  Stream<void> get refreshes => _refreshes.stream;
  Stream<int> get deniedGroups => _deniedGroups.stream;
  WebSocketChannel? _socket;
  StreamSubscription<dynamic>? _subscription;
  Timer? _heartbeat, _retry, _deadline;
  final Map<String, int> _revisions = {};
  final Set<int> _groups = {};
  String? _lease, _channel, _socketId;
  bool _closed = false, _foreground = false, _renewing = false;
  int _epoch = 0, _retrySeconds = 3;
  bool get active =>
      !_closed &&
      _foreground &&
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);

  bool _current(int epoch) => active && epoch == _epoch;
  Future<Map<String, dynamic>> _send(
    String path,
    Map<String, dynamic> body, {
    String method = 'POST',
  }) async {
    if (!active) throw StateError('Live authorization changed.');
    final response = await client.send(
      method: method,
      url: '${baseUrl}live/$path',
      data: body,
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 10),
    );
    if (!active || response?['data'] is! Map) {
      throw StateError('Live authorization changed.');
    }
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  Future<void> resume() async {
    if (_closed) return;
    _foreground = true;
    await _connect();
  }

  Future<void> _connect() async {
    if (!active) return;
    _disconnect();
    final epoch = _epoch;
    try {
      final config = await _send('session', const {});
      if (!_current(epoch)) return;
      final host = config['host'],
          port = config['port'],
          scheme = config['scheme'];
      final key = config['key'],
          lease = config['lease_id'],
          channel = config['channel'];
      final heartbeat = config['heartbeat_seconds'];
      if (host is! String ||
          host.length > 253 ||
          host.contains('/') ||
          port is! int ||
          port < 1 ||
          port > 65535 ||
          !['ws', 'wss'].contains(scheme) ||
          (scheme == 'ws' &&
              !['127.0.0.1', 'localhost', '::1'].contains(host)) ||
          key is! String ||
          !RegExp(r'^[a-zA-Z0-9_-]{8,128}$').hasMatch(key) ||
          lease is! String ||
          !RegExp(r'^[a-f0-9-]{36}$').hasMatch(lease) ||
          channel !=
              'private-ne.school.$schoolId.user.$accountId.session.$lease' ||
          heartbeat is! int ||
          heartbeat < 10 ||
          heartbeat > 30) {
        throw const FormatException('Invalid live configuration.');
      }
      _lease = lease;
      _channel = channel as String;
      final socket = _socketFactory(
        Uri(
          scheme: scheme as String,
          host: host,
          port: port,
          path: '/app/$key',
          queryParameters: const {
            'protocol': '7',
            'client': 'navigateed',
            'version': '1.0',
          },
        ),
        origin,
      );
      _socket = socket;
      await socket.ready.timeout(const Duration(seconds: 10));
      if (!_current(epoch)) {
        await socket.sink.close();
        return;
      }
      _deadline = Timer(const Duration(seconds: 10), () => _lost(epoch));
      _subscription = socket.stream.listen(
        (message) => _message(epoch, message),
        onError: (Object _) => _lost(epoch),
        onDone: () => _lost(epoch),
      );
      _heartbeat = Timer.periodic(
        Duration(seconds: heartbeat),
        (_) => _renew(epoch),
      );
    } catch (error) {
      if (_current(epoch)) {
        _lost(
          epoch,
          permanent:
              error is ApiException &&
              [401, 403, 404].contains(error.statusCode),
        );
      }
    }
  }

  Future<void> _subscribe(int epoch, String channel) async {
    final auth = await _send('authorize', {
      'socket_id': _socketId,
      'channel_name': channel,
    });
    if (!_current(epoch)) return;
    if (auth['auth'] is! String) {
      throw const FormatException('Invalid channel authorization.');
    }
    _socket?.sink.add(
      jsonEncode({
        'event': 'pusher:subscribe',
        'data': {'channel': channel, 'auth': auth['auth']},
      }),
    );
  }

  Future<void> _message(int epoch, dynamic message) async {
    if (!_current(epoch)) return;
    try {
      if (message is! String || message.length > 16384) {
        throw const FormatException();
      }
      final envelope = jsonDecode(message);
      if (envelope is! Map || envelope['event'] is! String) {
        throw const FormatException();
      }
      final event = envelope['event'];
      if (event == 'pusher:ping') {
        _socket?.sink.add(
          jsonEncode({'event': 'pusher:pong', 'data': const {}}),
        );
        return;
      }
      final raw = envelope['data'];
      final data = raw is String ? jsonDecode(raw) : raw;
      if (event == 'pusher:connection_established') {
        if (data is! Map ||
            data['socket_id'] is! String ||
            !RegExp(
              r'^[0-9]{1,20}\.[0-9]{1,20}$',
            ).hasMatch(data['socket_id'] as String)) {
          throw const FormatException();
        }
        _socketId = data['socket_id'] as String;
        await _subscribe(epoch, _channel!);
      } else if (event == 'pusher_internal:subscription_succeeded') {
        if (envelope['channel'] == _channel) {
          _deadline?.cancel();
          _retrySeconds = 3;
          _refreshes.add(
            null,
          ); // Re-fetch authorized readers after every reconnect.
          for (final group in [..._groups]) {
            await joinGroup(group);
          }
        }
      } else if (event == 'navigateed.invalidate') {
        final channel = envelope['channel'];
        if (channel != _channel &&
            !_groups.any((g) => channel == _groupChannel(g))) {
          return;
        }
        if (data is! Map) throw const FormatException();
        final invalidation = LiveInvalidation.fromJson(
          Map<String, dynamic>.from(data),
          expectedSchoolId: schoolId,
        );
        final groupEvent = [
          'group.presence',
          'activity.group_changed',
        ].contains(invalidation.type);
        if (groupEvent && channel != _groupChannel(invalidation.groupId!) ||
            !groupEvent && channel != _channel) {
          return;
        }
        _accept(epoch, invalidation);
      } else if (event == 'pusher:error') {
        _lost(epoch);
      }
    } catch (_) {
      _lost(epoch);
    }
  }

  void _accept(int epoch, LiveInvalidation event) {
    final old = _revisions[event.sourceKey] ?? 0;
    if (event.revision > old && _current(epoch)) {
      if (_revisions.length >= 2048) _revisions.clear();
      _revisions[event.sourceKey] = event.revision;
      _events.add(event);
    }
  }

  void _presence(int epoch, int group, Map<String, dynamic> data) {
    final event = LiveInvalidation.fromJson(data, expectedSchoolId: schoolId);
    if (event.type != 'group.presence' || event.groupId != group) {
      throw const FormatException();
    }
    if (_groups.contains(group)) _accept(epoch, event);
  }

  void _groupFailure(int epoch, int group, Object error) {
    if (!_current(epoch)) return;
    if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
      _groups.remove(group);
      _deniedGroups.add(group);
      _socket?.sink.add(
        jsonEncode({
          'event': 'pusher:unsubscribe',
          'data': {'channel': _groupChannel(group)},
        }),
      );
    } else {
      _lost(epoch);
    }
  }

  String _groupChannel(int group) =>
      'private-ne.school.$schoolId.group.$group.user.$accountId.session.$_lease';
  Future<void> joinGroup(int group) async {
    if (group < 1 || _groups.length >= 10 && !_groups.contains(group)) {
      throw ArgumentError.value(group);
    }
    _groups.add(group);
    if (!active || _socketId == null || _lease == null) return;
    final epoch = _epoch;
    try {
      await _subscribe(epoch, _groupChannel(group));
      final data = await _send('groups/$group/presence', {'lease_id': _lease});
      _presence(epoch, group, data);
    } catch (error) {
      _groupFailure(epoch, group, error);
    }
  }

  Future<void> leaveGroup(int group) async {
    _groups.remove(group);
    if (!active || _lease == null) return;
    _socket?.sink.add(
      jsonEncode({
        'event': 'pusher:unsubscribe',
        'data': {'channel': _groupChannel(group)},
      }),
    );
    try {
      await _send('groups/$group/presence', {
        'lease_id': _lease,
      }, method: 'DELETE');
    } catch (_) {
      /* Ephemeral lease expires. */
    }
  }

  Future<void> _renew(int epoch) async {
    if (!_current(epoch)) {
      _disconnect();
      return;
    }
    if (_renewing) return;
    _renewing = true;
    try {
      final data = await _send('session', const {});
      if (!_current(epoch)) return;
      if (data['lease_id'] != _lease || data['channel'] != _channel) {
        _lost(epoch);
        return;
      }
      for (final group in [..._groups]) {
        try {
          final data = await _send('groups/$group/presence', {
            'lease_id': _lease,
          });
          _presence(epoch, group, data);
        } catch (error) {
          _groupFailure(epoch, group, error);
        }
      }
    } catch (error) {
      if (_current(epoch)) {
        _lost(
          epoch,
          permanent:
              error is ApiException &&
              [401, 403, 404].contains(error.statusCode),
        );
      }
    } finally {
      _renewing = false;
    }
  }

  void _lost(int epoch, {bool permanent = false}) {
    if (epoch != _epoch) return;
    _disconnect();
    if (active && !permanent) {
      _retry = Timer(Duration(seconds: _retrySeconds), _connect);
      _retrySeconds = (_retrySeconds * 2).clamp(3, 60);
    }
  }

  void _disconnect() {
    _epoch++;
    _heartbeat?.cancel();
    _deadline?.cancel();
    _retry?.cancel();
    _subscription?.cancel();
    _subscription = null;
    _socket?.sink.close();
    _socket = null;
    _socketId = null;
    _lease = null;
    _channel = null;
    _revisions.clear();
  }

  void pause() {
    _foreground = false;
    _disconnect();
  }

  Future<void> dispose() async {
    _closed = true;
    pause();
    await _events.close();
    await _refreshes.close();
    await _deniedGroups.close();
  }
}
