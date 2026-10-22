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

      expect(context.user!.id, 12);
      expect(context.user!.name, 'Tara Naidoo');
      expect(context.hasRole('Teacher'), isTrue);
      expect(context.currentSchool!.timezone, 'Africa/Johannesburg');
      expect(context.currentSchool!.crestUrl, 'https://school.test/logo.png');
      expect(context.currentSchoolId, 1);
      expect(context.modules, isEmpty);
      expect(context.children, isEmpty);
    });

    test('decodes a Phase 0 parent with the generated models', () {
      final context = MeContext.fromJson(phase0ParentJson);

      expect(context.roles, ['Parent']);
      expect(context.schools.single.school.id, 1);
      expect(context.schools.single.school.shortName, 'NHS');
      expect(context.schools.single.roles, ['Parent']);
      expect(context.currentSchool!.tagline, 'Learn and grow');
      expect(context.currentSchool!.sessionYear!.terms.single.current, isTrue);
      final MeChild asha = context.children!.single;
      expect(asha.id, 21);
      expect(asha.name, 'Asha Demo');
      expect(asha.schoolId, 1);
      expect(asha.schoolName, 'Northcliff High');
      expect(asha.avatarUrl, endsWith('asha.jpg'));
      // Phase 1 fields a Phase 0 server does not send: not available yet.
      expect(asha.studentNumber, isNull);
      expect(asha.approved, isNull);
      expect(asha.homeroom, isNull);
      expect(context.approval, isNull);
      expect(context.has('approval'), isFalse);
      // Sent, but empty: the server has the key.
      expect(context.notificationRules, isEmpty);
    });

    test('reads what Phase 1 adds', () {
      final context = MeContext.fromJson(phase1GuardianJson);

      expect(context.schools.map((s) => s.school.id), [1, 2]);
      expect(context.currentSchool!.name, 'Riverside Primary');
      expect(context.currentSchoolId, 2);
      expect(context.children, hasLength(2));
      expect(context.children!.first.studentNumber, 'NE-2026-00021');
      expect(context.children!.first.approved, isTrue);
      expect(context.children!.first.homeroom, 'Grade 7 A');
      expect(context.children!.last.schoolId, 2);
      expect(context.children!.last.approved, isFalse);
      expect(context.children!.last.avatarUrl, isEmpty);
      expect(context.moduleKeys, ['chat', 'fees']);
      expect(context.isModuleOn('fees'), isTrue);
      expect(context.isModuleReadOnly('fees'), isTrue);
      expect(context.isModuleReadOnly('chat'), isFalse);
      expect(context.notificationRules!.last.mode, 'mandatory');
      expect(context.isFeatureOn('passkeys'), isTrue);
      expect(context.signInMethods, ['password', 'passkey']);
      expect(context.has('approval'), isTrue);
      expect(context.approval, isNull);
      expect(context.has('a_key_from_a_later_phase'), isTrue);
    });

    test('a learner\'s approval', () {
      final context = MeContext.fromJson({
        ...contextJson,
        'approval': {'status': 'approved', 'approved_by_count': 2},
      });

      expect(context.approval!.isApproved, isTrue);
      expect(context.approval!.approvedByCount, 2);
    });

    test('never fails on missing or odd fields', () {
      final context = MeContext.fromJson({
        'user': 3,
        'schools': [
          'Demo School',
          {'school': 'odd'},
        ],
        'current_school': {'id': 'one'},
        'modules': {'chat': true},
        'children': [
          {'id': 21},
        ],
      });

      expect(context.user, isNull);
      expect(context.roles, isEmpty);
      expect(context.schools, isEmpty);
      expect(context.currentSchool, isNull);
      expect(context.modules, isEmpty);
      // Sent, but none decodes.
      expect(context.children, isEmpty);
      expect(MeContext.fromJson(const {}).children, isNull);
    });

    test('survives a round trip through the cache format', () {
      for (final Map<String, dynamic> json in [
        contextJson,
        phase0ParentJson,
        phase1GuardianJson,
      ]) {
        final context = MeContext.fromJson(json);
        expect(MeContext.fromJson(context.toJson()), context);
      }
    });

    test('a child known only from the v1 sign-in', () {
      final MeChild child = MeChild.of(
        id: 11,
        name: 'Thandi van Wyk',
        schoolName: 'Smoke Test School',
        homeroom: 'Grade 7 A',
      );

      expect(child.child.firstName, 'Thandi');
      expect(child.child.lastName, 'van Wyk');
      expect(child.schoolId, 0);
      expect(child.schoolName, 'Smoke Test School');
      expect(child.avatarUrl, isEmpty);
      expect(MeChild.fromJson(child.toJson()), child);
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

      expect(context!.user!.email, 'tara@school.test');
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
