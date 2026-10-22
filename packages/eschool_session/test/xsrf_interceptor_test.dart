import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_session/src/auth/xsrf_interceptor.dart';
import 'package:flutter_test/flutter_test.dart';

/// The server: records each request's method, path and XSRF header and
/// answers with the status [statusFor] gives.
class _FakeServer implements HttpClientAdapter {
  _FakeServer(this.statusFor);

  final int Function(RequestOptions options, int attempt) statusFor;
  final List<String> requests = <String>[];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(
      '${options.method} ${options.path} '
      '${options.headers[XsrfInterceptor.headerName] ?? '-'}',
    );
    return ResponseBody.fromString(
      jsonEncode(<String, Object>{'error': false}),
      statusFor(options, requests.length),
      headers: <String, List<String>>{
        Headers.contentTypeHeader: <String>[Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  late Map<String, String> cookies;
  late List<String> csrfFetches;

  Dio portal(_FakeServer server) {
    final Dio dio = Dio(BaseOptions(baseUrl: 'https://admin.example'))
      ..httpClientAdapter = server;
    dio.interceptors.add(
      XsrfInterceptor(
        dio: dio,
        readCookie: (String name) => cookies[name],
        fetchCsrfCookie: () async {
          csrfFetches.add('csrf-cookie');
          cookies['XSRF-TOKEN'] = 'fresh%3D${csrfFetches.length}';
        },
      ),
    );
    return dio;
  }

  setUp(() {
    cookies = <String, String>{};
    csrfFetches = <String>[];
  });

  test('a sign-in without a token fetches the CSRF cookie first', () async {
    final _FakeServer server = _FakeServer((_, _) => 200);

    await portal(server).post<dynamic>('/api/student/login');

    expect(csrfFetches, hasLength(1));
    // The cookie is URL-encoded; the header carries the decoded value.
    expect(server.requests, <String>['POST /api/student/login fresh=1']);
  });

  test(
    'writes echo the cookie the browser has; reads send no header',
    () async {
      cookies['XSRF-TOKEN'] = 'abc%2B1';
      final _FakeServer server = _FakeServer((_, _) => 200);
      final Dio dio = portal(server);

      await dio.get<dynamic>('/api/v2/me/context');
      await dio.post<dynamic>('/api/logout');

      expect(csrfFetches, isEmpty);
      expect(server.requests, <String>[
        'GET /api/v2/me/context -',
        'POST /api/logout abc+1',
      ]);
    },
  );

  test('a 419 fetches a fresh token and sends the write once more', () async {
    cookies['XSRF-TOKEN'] = 'stale';
    final _FakeServer server = _FakeServer(
      (_, int attempt) => attempt == 1 ? 419 : 200,
    );

    final Response<dynamic> response = await portal(
      server,
    ).post<dynamic>('/api/parent/login');

    expect(response.statusCode, 200);
    expect(csrfFetches, hasLength(1));
    expect(server.requests, <String>[
      'POST /api/parent/login stale',
      'POST /api/parent/login fresh=1',
    ]);
  });

  test('a second 419 is the answer: no loop', () async {
    cookies['XSRF-TOKEN'] = 'stale';
    final _FakeServer server = _FakeServer((_, _) => 419);

    await expectLater(
      portal(server).post<dynamic>('/api/teacher/login'),
      throwsA(
        isA<DioException>().having(
          (DioException e) => e.response?.statusCode,
          'status',
          419,
        ),
      ),
    );
    expect(server.requests, hasLength(2));
    expect(csrfFetches, hasLength(1));
  });

  test('other errors are not retried', () async {
    cookies['XSRF-TOKEN'] = 'abc';
    final _FakeServer server = _FakeServer((_, _) => 401);

    await expectLater(
      portal(server).post<dynamic>('/api/logout'),
      throwsA(isA<DioException>()),
    );
    expect(server.requests, hasLength(1));
    expect(csrfFetches, isEmpty);
  });
}
