import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixture.dart';

ApiFamilyVisitRepository repository(
  FakeApi api, {
  bool cookie = false,
  bool Function()? active,
  String Function()? token,
}) => ApiFamilyVisitRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic-visits-session',
    onUnauthorized: () {},
    sendsBearerToken: !cookie,
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 7,
  contextActive: active ?? () => true,
);
void main() {
  test(
    'private cookie reads bind account and school without exposing visitor records in debug logs',
    () async {
      final log = <String>[], old = debugPrint;
      debugPrint = (String? value, {int? wrapWidth}) {
        if (value != null) log.add(value);
      };
      addTearDown(() => debugPrint = old);
      final api = FakeApi(
        routes: {
          'GET v2/family-visits/configuration': ok(configJson()),
          'GET v2/family-visits/meetings': ok({
            'items': [slotJson()],
            'next_page': null,
          }),
        },
      );
      final repo = repository(api, cookie: true);
      await repo.configuration();
      expect((await repo.meetings(learnerId: 11)).items.single.id, meetingId);
      for (final request in api.requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '7');
        expect(request.headers.containsKey('Authorization'), isFalse);
      }
      expect(
        log.where(
          (line) =>
              line.contains('family-visits') ||
              line.contains('Synthetic private'),
        ),
        isEmpty,
      );
    },
  );
  test(
    'wrong school/account and a late authorization epoch cannot publish a private configuration',
    () async {
      for (final body in [configJson(school: 2), configJson(reader: 8)]) {
        final api = FakeApi(
          routes: {'GET v2/family-visits/configuration': ok(body)},
        );
        await expectLater(
          repository(api).configuration(),
          throwsA(isA<ApiException>()),
        );
      }
      var active = true;
      final api = FakeApi(
        routes: {
          'GET v2/family-visits/configuration': (RequestOptions _) {
            active = false;
            return ok(configJson());
          },
        },
      );
      await expectLater(
        repository(api, active: () => active).configuration(),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'token rotation and an unconfigured learner dispatch no visit requests',
    () async {
      var token = 'before';
      final api = FakeApi(routes: {}),
          repo = repository(api, token: () => token);
      token = 'after';
      await expectLater(repo.configuration(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
      final fresh = FakeApi(
            routes: {'GET v2/family-visits/configuration': ok(configJson())},
          ),
          scoped = repository(fresh);
      await scoped.configuration();
      await expectLater(
        scoped.meetings(learnerId: 22),
        throwsA(isA<ApiException>()),
      );
      expect(fresh.requests, hasLength(1));
    },
  );
  test(
    'foreign booked participant or collector owner never reaches the reader',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/family-visits/configuration': ok(configJson()),
          'GET v2/family-visits/meetings': ok({
            'items': [
              slotJson(status: 'booked', revision: 2, learner: 22, guardian: 8),
            ],
            'next_page': null,
          }),
          'GET v2/family-visits/passes': ok({
            'items': [passJson(requester: 8)],
            'next_page': null,
          }),
        },
      );
      final repo = repository(api);
      await repo.configuration();
      await expectLater(
        repo.meetings(learnerId: 11),
        throwsA(isA<ApiException>()),
      );
      await expectLater(
        repo.passes(learnerId: 11),
        throwsA(isA<ApiException>()),
      );
    },
  );
  test(
    'an ordinary host cannot read another host appointment or the manager pass list',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/family-visits/configuration': ok(
            configJson(reader: 7, staff: true),
          ),
          'GET v2/family-visits/meetings': ok({
            'items': [slotJson(host: 12)],
            'next_page': null,
          }),
        },
      );
      final repo = repository(api);
      await repo.configuration();
      await expectLater(repo.meetings(), throwsA(isA<ApiException>()));
      await expectLater(repo.passes(), throwsA(isA<ApiException>()));
      expect(api.requests, hasLength(2));
    },
  );
  test(
    'exact replay preserves UUID, source, revision and body; no account client collects at a gate',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/family-visits/configuration': ok(configJson()),
          'POST v2/family-visits/meetings/$meetingId/book': ok(
            slotJson(status: 'booked', revision: 2, learner: 11, guardian: 7),
          ),
        },
      );
      final repo = repository(api);
      await repo.configuration();
      final intent = FamilyVisitMutation(
        action: FamilyVisitAction.book,
        target: meetingId,
        body: {'operation_id': operationId, 'revision': 1, 'learner_id': 11},
      );
      expect((await repo.execute(intent)).revision, 2);
      expect(
        (await repo.execute(
          FamilyVisitMutation.fromJson(intent.toJson()),
        )).revision,
        2,
      );
      final writes = api.requests
          .where((request) => request.method == 'POST')
          .toList();
      expect(writes, hasLength(2));
      expect(writes.first.data, writes.last.data);
      expect(
        api.requests.every(
          (request) => !request.uri.path.contains('card-device'),
        ),
        isTrue,
      );
    },
  );
  test(
    'a collection receipt cannot substitute a collector or collection window',
    () async {
      for (final change in [
        {'collector': 'Another collector'},
        {'starts_at': '2026-10-30T08:16:00Z'},
      ]) {
        final api = FakeApi(
          routes: {
            'GET v2/family-visits/configuration': ok(configJson()),
            'POST v2/family-visits/passes': ok({...passJson(), ...change}),
          },
        );
        final repo = repository(api);
        await repo.configuration();
        await expectLater(
          repo.execute(
            FamilyVisitMutation(
              action: FamilyVisitAction.requestPass,
              body: {
                'operation_id': operationId,
                'learner_id': 11,
                'collector': 'Synthetic private collector',
                'reason': 'Synthetic private collection reason',
                'starts_at': '2026-10-30T10:15',
                'ends_at': '2026-10-30T11:15',
              },
            ),
          ),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
}
