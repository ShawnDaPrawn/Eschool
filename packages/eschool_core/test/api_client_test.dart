import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/foundation.dart';

/// Answers every request with [respond], and remembers the requests it saw.
class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.respond);

  final Future<ResponseBody> Function(RequestOptions options) respond;
  final List<RequestOptions> requests = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) {
    requests.add(options);
    return respond(options);
  }

  @override
  void close({bool force = false}) {}
}

ResponseBody _json(Object body, {int status = 200}) {
  return ResponseBody.fromString(
    jsonEncode(body),
    status,
    headers: {
      Headers.contentTypeHeader: [Headers.jsonContentType],
    },
  );
}

void main() {
  late int unauthorizedCalls;
  late _FakeAdapter adapter;
  late ApiClient client;

  void useServer(Future<ResponseBody> Function(RequestOptions) respond) {
    adapter = _FakeAdapter(respond);
    client = ApiClient(
      authToken: () => 'secret-token',
      onUnauthorized: () => unauthorizedCalls++,
      createDio: () => Dio()..httpClientAdapter = adapter,
    );
  }

  setUp(() {
    unauthorizedCalls = 0;
    useServer((_) async => _json({'error': false, 'data': 'ok'}));
  });

  Future<ApiException> failure(Future<Object?> call) async {
    try {
      await call;
    } on ApiException catch (e) {
      return e;
    }
    fail('expected an ApiException');
  }

  group('request identity', () {
    test(
      'another portal can replace cookies without changing this tab generation or school',
      () async {
        var browserMarker = 'opaque-parent-session';
        final started = Completer<void>();
        final response = Completer<ResponseBody>();
        client = ApiClient(
          authToken: () => '',
          sendsBearerToken: false,
          sessionIdentity: () => (0, '', 1, browserMarker),
          onUnauthorized: () => unauthorizedCalls++,
          createDio: () => Dio()
            ..httpClientAdapter = _FakeAdapter((_) {
              started.complete();
              return response.future;
            }),
        );
        final result = failure(
          client.get(
            url: 'https://school.test/api/v2/private',
            useAuthToken: true,
          ),
        );
        await started.future;
        browserMarker = 'opaque-teacher-replacement';
        response.complete(_json({'message': 'Unauthenticated.'}, status: 401));
        expect((await result).statusCode, 401);
        expect(unauthorizedCalls, 0);
      },
    );
    for (final cookie in [false, true]) {
      for (final stale in [false, true]) {
        for (final method in ['get', 'post', 'supported', 'send', 'bytes']) {
          test(
            '$method ${cookie ? 'cookie' : 'bearer'} ${stale ? 'stale' : 'current'} 401',
            () async {
              var generation = 1;
              final started = Completer<void>();
              final response = Completer<ResponseBody>();
              client = ApiClient(
                authToken: () => cookie ? '' : 'token-$generation',
                sendsBearerToken: !cookie,
                sessionIdentity: () => generation,
                onUnauthorized: () => unauthorizedCalls++,
                createDio: () => Dio()
                  ..httpClientAdapter = _FakeAdapter((_) {
                    started.complete();
                    return response.future;
                  }),
              );
              const url = 'https://school.test/api/v2/private';
              final Future<Object?> request = switch (method) {
                'get' => client.get(url: url, useAuthToken: true),
                'post' => client.post(url: url, body: {}, useAuthToken: true),
                'supported' => client.getIfSupported(
                  url: url,
                  useAuthToken: true,
                ),
                'send' => client.send(method: 'GET', url: url),
                _ => client.getBytes(url: url),
              };
              final result = failure(request);
              await started.future;
              if (stale) generation++;
              response.complete(
                _json({'message': 'Unauthenticated.'}, status: 401),
              );
              expect((await result).statusCode, 401);
              expect(unauthorizedCalls, stale ? 0 : 1);
            },
          );
        }
      }
    }
    test(
      'anonymous startup calls never read unopened session storage',
      () async {
        client = ApiClient(
          authToken: () => throw StateError('storage unopened'),
          sessionIdentity: () => throw StateError('storage unopened'),
          onUnauthorized: () => unauthorizedCalls++,
          createDio: () => Dio()
            ..httpClientAdapter = _FakeAdapter(
              (_) async => _json({'error': false}),
            ),
        );
        await client.get(
          url: 'https://school.test/settings',
          useAuthToken: false,
        );
        await client.post(
          url: 'https://school.test/login',
          body: {},
          useAuthToken: false,
        );
        await client.getIfSupported(
          url: 'https://school.test/settings',
          useAuthToken: false,
        );
        await client.send(
          method: 'GET',
          url: 'https://school.test/settings',
          useAuthToken: false,
        );
        expect(unauthorizedCalls, 0);
      },
    );
    test('anonymous 401 does not sign out a current account', () async {
      useServer((_) async => _json({}, status: 401));
      expect(
        (await failure(
          client.get(url: 'https://school.test/public', useAuthToken: false),
        )).statusCode,
        401,
      );
      expect(unauthorizedCalls, 0);
    });
    test(
      'logging keeps route metadata but excludes credentials and URL copies',
      () async {
        final messages = <String>[];
        final previous = debugPrint;
        debugPrint = (String? message, {int? wrapWidth}) {
          messages.add(message ?? '');
        };
        addTearDown(() => debugPrint = previous);
        await client.get(
          url:
              'https://user:password@school.test/api/v2/guardian/%69nvitations/private%2Fsecret?token=query-secret#fragment-secret',
          useAuthToken: false,
        );
        expect(
          messages.single,
          contains('/api/v2/guardian/invitations/:redacted'),
        );
        for (final secret in [
          'user:',
          'password',
          'private',
          'secret',
          'query',
          'fragment',
        ]) {
          expect(messages.join(), isNot(contains(secret)));
        }
      },
    );
  });

  test(
    'pinned requests send account binding and anonymous requests omit it',
    () async {
      await client.send(
        method: 'GET',
        url: 'https://school.test/api/v2/private',
        accountId: 12,
        schoolId: 7,
      );
      expect(adapter.requests.single.headers['X-Account-Id'], '12');
      await client.send(
        method: 'GET',
        url: 'https://school.test/public',
        accountId: 12,
        useAuthToken: false,
      );
      expect(
        adapter.requests.last.headers.containsKey('X-Account-Id'),
        isFalse,
      );
    },
  );

  test(
    'HTTP error status survives translation for private access clearing',
    () async {
      for (final status in [401, 403, 404, 409, 422, 500, 503]) {
        useServer(
          (_) async => _json({
            'error': true,
            'message': 'Request refused',
          }, status: status),
        );
        final error = await failure(
          client.send(method: 'GET', url: 'https://school.test/api/v2/private'),
        );
        expect(error.statusCode, status);
      }
      expect(unauthorizedCalls, 1);
    },
  );

  test(
    'offline fallback distinguishes transport loss from HTTP, certificate and cancellation',
    () async {
      for (final type in DioExceptionType.values) {
        useServer(
          (request) async =>
              throw DioException(requestOptions: request, type: type),
        );
        final error = await failure(
          client.send(method: 'GET', url: 'https://school.test/api/v2/private'),
        );
        expect(
          error.isTransportFailure,
          [
            DioExceptionType.connectionError,
            DioExceptionType.connectionTimeout,
            DioExceptionType.sendTimeout,
            DioExceptionType.receiveTimeout,
          ].contains(type),
          reason: '$type',
        );
      }
      for (final status in [401, 403, 404, 500, 503]) {
        useServer(
          (_) async =>
              _json({'error': true, 'message': 'Refused'}, status: status),
        );
        final error = await failure(
          client.send(method: 'GET', url: 'https://school.test/api/v2/private'),
        );
        expect(error.isTransportFailure, false);
        expect(error.statusCode, status);
      }
    },
  );

  test(
    'a stalled v2 transport is cancelled at its deadline and permits offline fallback',
    () async {
      final pending = Completer<ResponseBody>();
      useServer((_) => pending.future);
      final error = await failure(
        client.send(
          method: 'GET',
          url: 'https://school.test/api/v2/private',
          requestTimeout: const Duration(milliseconds: 20),
        ),
      );
      expect(error.isTransportFailure, isTrue);
      expect(error.statusCode, isNull);
      expect(adapter.requests, hasLength(1));
      pending.complete(_json({'error': false, 'data': 'late'}));
    },
  );

  test(
    'a bounded call preserves HTTP refusals rather than using offline fallback',
    () async {
      useServer(
        (_) async => _json({'error': true, 'message': 'Refused'}, status: 403),
      );
      final error = await failure(
        client.send(
          method: 'GET',
          url: 'https://school.test/api/v2/private',
          requestTimeout: const Duration(seconds: 20),
        ),
      );
      expect(error.isTransportFailure, isFalse);
      expect(error.statusCode, 403);
    },
  );

  test('v2 retries preserve the supplied operation key and school', () async {
    for (var attempt = 0; attempt < 2; attempt++) {
      await client.send(
        method: 'POST',
        url: 'https://school.test/api/v2/report',
        schoolId: 2,
        idempotencyKey: 'stable-operation',
        data: {'revision': 3},
      );
    }
    expect(adapter.requests, hasLength(2));
    for (final request in adapter.requests) {
      expect(request.headers['Idempotency-Key'], 'stable-operation');
      expect(request.headers['X-School-Id'], '2');
      expect(request.data, {'revision': 3});
    }
  });

  test('headers carry the signed-in token and ask for JSON', () {
    // Accept: application/json makes the server answer an expired token with 401.
    expect(client.headers(), {
      'Authorization': 'Bearer secret-token',
      'Accept': 'application/json',
    });
  });

  test(
    'authenticated asset bytes preserve school scope and unauthorized handling',
    () async {
      useServer(
        (_) async => ResponseBody.fromBytes(
          [37, 80, 68, 70],
          200,
          headers: {
            Headers.contentTypeHeader: ['application/pdf'],
          },
        ),
      );
      expect(
        await client.getBytes(
          url: 'https://school.test/protected/file',
          schoolId: 2,
        ),
        [37, 80, 68, 70],
      );
      expect(adapter.requests.single.headers['X-School-Id'], '2');
      expect(adapter.requests.single.followRedirects, isFalse);
      expect(
        adapter.requests.single.headers['Authorization'],
        'Bearer secret-token',
      );
      useServer(
        (_) async => _json({'message': 'Unauthenticated'}, status: 401),
      );
      await failure(
        client.getBytes(url: 'https://school.test/protected/file', schoolId: 2),
      );
      expect(unauthorizedCalls, 1);
    },
  );

  group('post', () {
    test(
      'returns the response and sends the token only when asked to',
      () async {
        final result = await client.post(
          body: {'a': 1},
          url: 'https://school.test/api/x',
          useAuthToken: true,
        );
        await client.post(
          body: {'a': 1},
          url: 'https://school.test/api/x',
          useAuthToken: false,
        );

        expect(result['data'], 'ok');
        expect(
          adapter.requests[0].headers['Authorization'],
          'Bearer secret-token',
        );
        expect(
          adapter.requests[1].headers.containsKey('Authorization'),
          isFalse,
        );
      },
    );

    test('a message from the server is shown as it is', () async {
      useServer(
        (_) async => _json({'error': true, 'message': 'Wrong password'}),
      );

      final e = await failure(
        client.post(
          body: {},
          url: 'https://school.test/x',
          useAuthToken: false,
        ),
      );

      expect(e.errorMessage, 'Wrong password');
      expect(e.isApiMessage, isTrue);
    });

    test('a code from the server is left to be translated', () async {
      useServer(
        (_) async => _json({'error': true, 'code': 103, 'message': 'ignored'}),
      );

      final e = await failure(
        client.post(
          body: {},
          url: 'https://school.test/x',
          useAuthToken: false,
        ),
      );

      expect(e.errorMessage, '103');
      expect(e.isApiMessage, isFalse);
    });

    test('401 tells the app once and reports unauthorized access', () async {
      useServer((_) async => _json({'error': true}, status: 401));

      final e = await failure(
        client.post(body: {}, url: 'https://school.test/x', useAuthToken: true),
      );

      expect(e.errorMessage, ApiErrorCodes.unauthorizedAccess);
      expect(unauthorizedCalls, 1);
    });

    test('500 and 503 report a server error', () async {
      for (final status in [500, 503]) {
        useServer((_) async => _json({'error': true}, status: status));

        final e = await failure(
          client.post(
            body: {},
            url: 'https://school.test/x',
            useAuthToken: true,
          ),
        );

        expect(e.errorMessage, ApiErrorCodes.internetServerError);
      }
      expect(unauthorizedCalls, 0);
    });

    test('no connection is told apart from other failures', () async {
      useServer(
        (options) async => throw DioException(
          requestOptions: options,
          error: const SocketException('offline'),
          type: DioExceptionType.connectionError,
        ),
      );
      final offline = await failure(
        client.post(body: {}, url: 'https://school.test/x', useAuthToken: true),
      );

      useServer(
        (options) async => throw DioException(
          requestOptions: options,
          type: DioExceptionType.connectionTimeout,
        ),
      );
      final other = await failure(
        client.post(body: {}, url: 'https://school.test/x', useAuthToken: true),
      );

      expect(offline.errorMessage, ApiErrorCodes.noInternet);
      expect(other.errorMessage, ApiErrorCodes.defaultError);
    });

    test('a reply that is not the expected shape is a default error', () async {
      useServer(
        (_) async => ResponseBody.fromString(
          '"just text"',
          200,
          headers: {
            Headers.contentTypeHeader: [Headers.jsonContentType],
          },
        ),
      );

      final e = await failure(
        client.post(body: {}, url: 'https://school.test/x', useAuthToken: true),
      );

      expect(e.errorMessage, ApiErrorCodes.defaultErrorMessageKey);
    });
  });

  group('get', () {
    test('returns the response and passes the query', () async {
      final result = await client.get(
        url: 'https://school.test/api/x',
        useAuthToken: true,
        queryParameters: {'page': 2},
      );

      expect(result['data'], 'ok');
      expect(adapter.requests.single.queryParameters, {'page': 2});
      expect(adapter.requests.single.method, 'GET');
    });

    test('401 tells the app', () async {
      useServer((_) async => _json({'error': true}, status: 401));

      final e = await failure(
        client.get(url: 'https://school.test/x', useAuthToken: true),
      );

      expect(e.errorMessage, ApiErrorCodes.unauthorizedAccess);
      expect(unauthorizedCalls, 1);
    });
  });

  group('getIfSupported', () {
    test('returns the response like get', () async {
      final result = await client.getIfSupported(
        url: 'https://school.test/api/v2/me/context',
        useAuthToken: true,
      );

      expect(result!['data'], 'ok');
    });

    test('answers null when the server has no such route', () async {
      useServer((_) async => _json({'message': 'Not Found'}, status: 404));

      final result = await client.getIfSupported(
        url: 'https://school.test/api/v2/me/context',
        useAuthToken: true,
      );

      expect(result, isNull);
      expect(unauthorizedCalls, 0);
    });

    test('still fails on other errors, and 401 still tells the app', () async {
      useServer((_) async => _json({'error': true}, status: 401));

      final e = await failure(
        client.getIfSupported(url: 'https://school.test/x', useAuthToken: true),
      );

      expect(e.errorMessage, ApiErrorCodes.unauthorizedAccess);
      expect(unauthorizedCalls, 1);
    });
  });

  group('send', () {
    const String url = 'https://school.test/api/v2/me/notification-preferences';

    test('sends the method and a JSON body with the token', () async {
      final result = await client.send(
        method: 'PUT',
        url: url,
        data: {'type': 'comms.notice', 'channel': 'email', 'enabled': false},
      );

      final RequestOptions request = adapter.requests.single;
      expect(result!['data'], 'ok');
      expect(request.method, 'PUT');
      expect(request.headers['Authorization'], 'Bearer secret-token');
      expect(request.contentType, Headers.jsonContentType);
      expect(request.data, containsPair('enabled', false));
    });

    test('a file goes as multipart', () async {
      await client.send(
        method: 'POST',
        url: url,
        data: FormData.fromMap({'_method': 'PUT'}),
      );

      expect(adapter.requests.single.data, isA<FormData>());
    });

    test('a refusal the server explains is shown as it says', () async {
      useServer(
        (_) async => _json({
          'error': true,
          'message': 'Your school sends this to everyone.',
          'code': 422,
        }, status: 422),
      );

      final e = await failure(
        client.send(method: 'PUT', url: url, data: const {}),
      );

      expect(e.isApiMessage, isTrue);
      expect(e.errorMessage, 'Your school sends this to everyone.');
    });

    test('a route not deployed yet answers null when asked to', () async {
      useServer((_) async => _json({'message': 'Not Found'}, status: 404));

      expect(
        await client.send(method: 'GET', url: url, notFoundAsNull: true),
        isNull,
      );
      final e = await failure(client.send(method: 'GET', url: url));
      expect(e.errorMessage, ApiErrorCodes.defaultError);
    });

    test('401 still tells the app', () async {
      useServer(
        (_) async => _json({'message': 'Unauthenticated.'}, status: 401),
      );

      final e = await failure(client.send(method: 'GET', url: url));

      expect(e.errorMessage, ApiErrorCodes.unauthorizedAccess);
      expect(unauthorizedCalls, 1);
    });
  });

  test('a cookie-signed-in client sends no Authorization header', () {
    final cookieClient = ApiClient(
      authToken: () => '',
      onUnauthorized: () {},
      sendsBearerToken: false,
    );

    expect(cookieClient.headers(), {'Accept': 'application/json'});
  });

  group('download', () {
    test(
      'a missing file is reported as not found, without logging out',
      () async {
        useServer((_) async => _json({'error': true}, status: 404));

        final e = await failure(
          client.download(
            url: 'https://school.test/file.pdf',
            cancelToken: CancelToken(),
            savePath: '${Directory.systemTemp.path}/never-written.pdf',
            updateDownloadedPercentage: (_) {},
          ),
        );

        expect(e.errorMessage, ApiErrorCodes.fileNotFound);
        expect(unauthorizedCalls, 0);
      },
    );

    test('a server error is reported as one', () async {
      useServer((_) async => _json({'error': true}, status: 500));

      final e = await failure(
        client.download(
          url: 'https://school.test/file.pdf',
          cancelToken: CancelToken(),
          savePath: '${Directory.systemTemp.path}/never-written.pdf',
          updateDownloadedPercentage: (_) {},
        ),
      );

      expect(e.errorMessage, ApiErrorCodes.internetServerError);
    });
  });
}
