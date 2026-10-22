import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const String api = 'https://school.test/api/';

  late FakeApi fake;
  late ApiClient client;

  setUp(() {
    fake = FakeApi(
      routes: {
        'settings': {
          'error': false,
          'data': {'school_name': 'Test School'},
        },
        'POST student/login': {'error': false, 'token': 't', 'data': {}},
        'student/login': const FakeResponse({'error': true, 'code': 'x'}),
        'v2/me/context': const FakeResponse.notFound(),
        'echo': (RequestOptions request) => {
          'error': false,
          'data': request.queryParameters,
        },
      },
    );
    client = ApiClient(
      authToken: () => 'token',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = fake,
    );
  });

  test('answers a route by its path below /api/', () async {
    final result = await client.get(
      url: '${api}settings',
      useAuthToken: false,
      queryParameters: {'type': 'app_settings'},
    );

    expect(result['data'], {'school_name': 'Test School'});
    expect(fake.requested('GET settings'), isTrue);
  });

  test('a route with the method wins over the bare path', () async {
    final result = await client.post(
      body: {'gr_number': '1'},
      url: '${api}student/login',
      useAuthToken: false,
    );

    expect(result['token'], 't');
    expect(fake.requested('POST student/login'), isTrue);
    expect(fake.requested('student/login'), isTrue);
  });

  test('a function route sees the request', () async {
    final result = await client.get(
      url: '${api}echo',
      useAuthToken: false,
      queryParameters: {'page': '2'},
    );

    expect(result['data'], {'page': '2'});
  });

  test('an unknown path gets the empty answer', () async {
    final result = await client.get(url: '${api}unknown', useAuthToken: true);

    expect(result['data'], isEmpty);
  });

  test('a 404 route reads as an unsupported endpoint', () async {
    final result = await client.getIfSupported(
      url: '${api}v2/me/context',
      useAuthToken: true,
    );

    expect(result, isNull);
  });
}
