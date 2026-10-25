import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

class _Refuse implements HttpClientAdapter {
  RequestOptions? request;
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) async {
    request = options;
    return ResponseBody.fromString(
      '{}',
      403,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  test(
    'generated cover client binds expected account and may omit school year',
    () async {
      final adapter = _Refuse();
      final dio = Dio()..httpClientAdapter = adapter;
      await expectLater(
        PastoralApi(dio).getCoverConfiguration(
          from: '2026-01-01',
          to: '2026-01-31',
          xAccountId: 12,
          xSchoolId: 7,
        ),
        throwsA(isA<DioException>()),
      );
      expect(adapter.request!.headers['X-Account-Id'], 12);
      expect(
        adapter.request!.queryParameters.containsKey('session_year_id'),
        isFalse,
      );
    },
  );
}
