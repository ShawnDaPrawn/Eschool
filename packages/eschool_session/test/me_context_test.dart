import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fixtures.dart';

class _Adapter implements HttpClientAdapter {
  _Adapter(this.status, this.body);

  final int status;
  final Object body;
  final List<RequestOptions> requests = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      jsonEncode(body),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  group('MeContext.fromJson', () {
    test('reads the user, roles, school and modules', () {
      final context = MeContext.fromJson(contextJson);

      expect(context.user.id, 12);
      expect(context.user.name, 'Tara Naidoo');
      expect(context.hasRole('Teacher'), isTrue);
      expect(context.school!.timezone, 'Africa/Johannesburg');
      expect(context.school!.logoUrl, 'https://school.test/logo.png');
      expect(context.modules, isEmpty);
    });

    test('accepts the shapes a later server may send', () {
      final context = MeContext.fromJson({
        'user': {'id': '5', 'name': 'Asha'},
        'roles': [
          {'name': 'Student'},
        ],
        'current_school': {'id': 2, 'name': 'Other School'},
        'modules': [
          {'key': 'chat', 'enabled': true},
          {'key': 'wallet', 'enabled': false},
          'online_exams',
        ],
      });

      expect(context.user.id, 5);
      expect(context.user.name, 'Asha');
      expect(context.roles, ['Student']);
      expect(context.school!.name, 'Other School');
      expect(context.modules, ['chat', 'online_exams']);
      expect(
        MeContext.fromJson({
          'modules': {'chat': true, 'wallet': false},
        }).modules,
        ['chat'],
      );
    });

    test('never fails on missing or odd fields', () {
      final context = MeContext.fromJson({'roles': 'Teacher', 'user': 3});

      expect(context.roles, isEmpty);
      expect(context.user.id, 0);
      expect(context.school, isNull);
    });

    test('survives a round trip through the cache format', () {
      final context = MeContext.fromJson(contextJson);

      expect(MeContext.fromJson(context.toJson()), context);
    });
  });

  group('ApiMeContextRepository', () {
    ApiClient client(_Adapter adapter) => ApiClient(
      authToken: () => 'token',
      onUnauthorized: () {},
      createDio: () => Dio()..httpClientAdapter = adapter,
    );

    test('asks the v2 endpoint with the token', () async {
      final adapter = _Adapter(200, {
        'error': false,
        'message': 'ok',
        'data': contextJson,
        'code': 200,
      });

      final context = await ApiMeContextRepository(
        client(adapter),
      ).fetchContext();

      expect(context!.user.email, 'tara@school.test');
      expect(adapter.requests.single.uri.path, '/api/v2/me/context');
      expect(adapter.requests.single.headers['Authorization'], 'Bearer token');
    });

    test('answers null on a server older than API v2', () async {
      final adapter = _Adapter(404, {'message': 'Not Found'});

      expect(
        await ApiMeContextRepository(client(adapter)).fetchContext(),
        isNull,
      );
    });
  });
}
