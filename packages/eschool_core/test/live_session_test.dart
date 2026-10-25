import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const lease = '75133b39-0476-4ba0-a627-a152d042687a';
  const channel = 'private-ne.school.1.user.7.session.$lease';
  late HttpServer server;
  late ApiClient api;
  late List<RequestOptions> requests;
  late List<WebSocket> peers;
  late List<Map<String, dynamic>> subscriptions;
  late String token;
  late bool authorized;
  Completer<void>? responseGate;

  Map<String, dynamic> metadata({int revision = 1, int school = 1}) => {
    'type': 'house_points.changed',
    'school_id': school,
    'session_year_id': 3,
    'house_id': 4,
    'revision': revision,
  };
  void publish(
    WebSocket peer,
    Map<String, dynamic> data, {
    String destination = channel,
  }) => peer.add(
    jsonEncode({
      'event': 'navigateed.invalidate',
      'channel': destination,
      'data': jsonEncode(data),
    }),
  );
  Future<void> until(bool Function() condition) async {
    for (var i = 0; i < 100 && !condition(); i++) {
      await Future<void>.delayed(const Duration(milliseconds: 10));
    }
    expect(condition(), isTrue);
  }

  setUp(() async {
    token = 'original-token';
    authorized = true;
    responseGate = null;
    requests = [];
    peers = [];
    subscriptions = [];
    server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
    server.listen((request) async {
      expect(request.headers.value('Origin'), 'https://app.navigateed.co.za');
      final peer = await WebSocketTransformer.upgrade(request);
      peers.add(peer);
      peer.add(
        jsonEncode({
          'event': 'pusher:connection_established',
          'data': jsonEncode({'socket_id': '123.456', 'activity_timeout': 30}),
        }),
      );
      peer.listen((raw) {
        final frame = jsonDecode(raw as String) as Map<String, dynamic>;
        if (frame['event'] == 'pusher:subscribe') {
          final data = Map<String, dynamic>.from(frame['data'] as Map);
          subscriptions.add(data);
          peer.add(
            jsonEncode({
              'event': 'pusher_internal:subscription_succeeded',
              'channel': data['channel'],
              'data': '{}',
            }),
          );
        }
      });
    });
    final dio = Dio();
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          requests.add(options);
          if (responseGate != null) await responseGate!.future;
          final data = options.path.endsWith('/session')
              ? {
                  'lease_id': lease,
                  'channel': channel,
                  'key': 'local-live-key',
                  'host': '127.0.0.1',
                  'port': server.port,
                  'scheme': 'ws',
                  'heartbeat_seconds': 20,
                  'expires_in': 45,
                }
              : {'auth': 'local-live-key:synthetic-signature'};
          handler.resolve(
            Response(
              requestOptions: options,
              statusCode: 200,
              data: {
                'error': false,
                'code': 200,
                'data': data,
                'message': 'Ready',
              },
            ),
          );
        },
      ),
    );
    api = ApiClient(
      authToken: () => token,
      onUnauthorized: () {},
      createDio: () => dio,
    );
  });
  tearDown(() async {
    responseGate?.complete();
    for (final peer in peers) {
      await peer.close();
    }
    await server.close(force: true);
  });

  LiveSession session() => LiveSession(
    client: api,
    schoolId: 1,
    accountId: 7,
    contextActive: () => authorized,
    baseUrl: 'https://api.example/api/v2/',
  );

  test(
    'real IO socket handshake carries bound headers and emits deduplicated references only',
    () async {
      final live = session();
      final events = <LiveInvalidation>[];
      var refreshes = 0;
      final sub = live.events.listen(events.add);
      final ready = live.refreshes.listen((_) => refreshes++);
      await live.resume();
      await until(() => subscriptions.isNotEmpty && refreshes == 1);
      expect(subscriptions.single, {
        'channel': channel,
        'auth': 'local-live-key:synthetic-signature',
      });
      expect(requests, hasLength(2));
      for (final request in requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '7');
        expect(request.headers['Authorization'], 'Bearer original-token');
      }
      publish(peers.single, metadata(revision: 2));
      publish(peers.single, metadata(revision: 2));
      publish(peers.single, metadata(revision: 1));
      await until(() => events.isNotEmpty);
      await Future<void>.delayed(const Duration(milliseconds: 20));
      expect(events, hasLength(1));
      expect(events.single.revision, 2);
      await live.dispose();
      await sub.cancel();
      await ready.cancel();
    },
  );

  test(
    'same-account authority withdrawal and bearer replacement refuse late socket bodies',
    () async {
      final live = session();
      final events = <LiveInvalidation>[];
      final sub = live.events.listen(events.add);
      await live.resume();
      await until(() => subscriptions.isNotEmpty);
      authorized = false;
      publish(peers.single, metadata());
      await Future<void>.delayed(const Duration(milliseconds: 30));
      expect(events, isEmpty);
      authorized = true;
      token = 'replacement-token';
      publish(peers.single, metadata(revision: 2));
      await Future<void>.delayed(const Duration(milliseconds: 30));
      expect(events, isEmpty);
      await live.dispose();
      await sub.cancel();
    },
  );

  test(
    'backgrounding closes transport and resume authorizes a fresh socket',
    () async {
      final live = session();
      var refreshed = 0;
      final sub = live.refreshes.listen((_) => refreshed++);
      await live.resume();
      await until(() => refreshed == 1);
      live.pause();
      await live.resume();
      await until(() => refreshed == 2);
      expect(
        requests.where((r) => r.path.endsWith('/authorize')),
        hasLength(2),
      );
      expect(peers, hasLength(2));
      await live.dispose();
      await sub.cancel();
    },
  );

  test(
    'an HTTP session reply arriving after scope withdrawal never opens a socket',
    () async {
      final live = session();
      responseGate = Completer<void>();
      final opening = live.resume();
      await until(() => requests.isNotEmpty);
      authorized = false;
      responseGate!.complete();
      responseGate = null;
      await opening;
      expect(peers, isEmpty);
      await live.dispose();
    },
  );

  test('cross-school or content-bearing payloads are refused', () {
    expect(
      () => LiveInvalidation.fromJson(metadata(school: 2), expectedSchoolId: 1),
      throwsFormatException,
    );
    expect(
      () => LiveInvalidation.fromJson({
        ...metadata(),
        'name': 'Private content',
      }, expectedSchoolId: 1),
      throwsFormatException,
    );
    expect(
      () => LiveInvalidation.fromJson({
        ...metadata(),
        'revision': 0,
      }, expectedSchoolId: 1),
      throwsFormatException,
    );
  });
}
