import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter_test/flutter_test.dart';

import 'facilitator_test_fixture.dart';

class _Delayed401 implements HttpClientAdapter {
  _Delayed401(this.api);
  final FakeApi api;
  final pending = Completer<ResponseBody>();
  @override
  Future<ResponseBody> fetch(
    RequestOptions request,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) {
    if (request.path.endsWith('/activities/groups')) return pending.future;
    return api.fetch(request, stream, cancel);
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  FacilitatorRepository repo(FakeApi api, {AuthMode mode = AuthMode.bearer}) =>
      FacilitatorRepository(
        mode: mode,
        createDio: () => Dio()..httpClientAdapter = api,
      );
  test('native session is scoped, ephemeral and sends expected account after sign-in', () async {
    final api = staffApi();
    final value = repo(api);
    addTearDown(value.dispose);
    await value.signIn(staffCredentials);
    expect(value.profile!.userId, 12);
    expect(value.ready, isTrue);
    await value.refresh();
    final request = api.requests.last;
    expect(request.headers['X-Account-Id'], '12');
    expect(request.headers['X-School-Id'], '1');
    expect(
      request.headers['Authorization'],
      'Bearer 12|synthetic-facilitator-token',
    );
  });
  test(
    'setup remains unauthenticated and never exposes a profile or bearer token',
    () async {
      final api = staffApi(
        signIn: FakeResponse(staffSetupFixture(), status: 409),
      );
      final value = repo(api);
      addTearDown(value.dispose);
      final reply = await value.signIn(staffCredentials);
      expect(reply.passwordRequired, isTrue);
      expect(value.signedIn, isFalse);
      expect(value.client.authToken(), isEmpty);
      expect(api.requests.single.headers.containsKey('Authorization'), isFalse);
    },
  );
  test(
    'cookie session binds expected account without sending bearer credentials',
    () async {
      final api = staffApi(signIn: staffReplyFixture(cookie: true));
      final value = repo(api, mode: AuthMode.cookie);
      addTearDown(value.dispose);
      await value.signIn(staffCredentials);
      await value.refresh();
      expect(api.requests.last.headers['X-Account-Id'], '12');
      expect(api.requests.last.headers.containsKey('Authorization'), isFalse);
    },
  );
  test('replacement account response is discarded rather than replacing private context', () async {
    final api = staffApi(
      session: {'error': false, 'data': staffProfileFixture(user: 99)},
    );
    final value = repo(api);
    addTearDown(value.dispose);
    await value.signIn(staffCredentials);
    await value.refresh();
    expect(value.signedIn, isFalse);
    expect(value.client.authToken(), isEmpty);
  });
  test('counterparty school sign-in response is refused', () async {
    final value = repo(staffApi(signIn: staffReplyFixture(school: 2)));
    addTearDown(value.dispose);
    await expectLater(
      value.signIn(staffCredentials),
      throwsA(isA<ApiException>().having((e) => e.statusCode, 'status', 403)),
    );
    expect(value.signedIn, isFalse);
  });
  test(
    'offline refresh preserves identity but hides access until revalidated',
    () async {
      final api = staffApi();
      final value = repo(api);
      addTearDown(value.dispose);
      await value.signIn(staffCredentials);
      api.routes['GET v2/facilitator/session'] = const FakeResponse({
        'message': 'Unavailable',
      }, status: 503);
      await value.refresh();
      expect(value.signedIn, isTrue);
      expect(value.ready, isFalse);
      api.routes['GET v2/facilitator/session'] = {
        'error': false,
        'data': staffProfileFixture(),
      };
      await value.refresh();
      expect(value.ready, isTrue);
    },
  );
  test(
    'offline logout clears local access and pinned private context first',
    () async {
      final api = staffApi(
        logout: const FakeResponse({'message': 'Unavailable'}, status: 503),
      );
      final value = repo(api);
      addTearDown(value.dispose);
      await value.signIn(staffCredentials);
      final epoch = value.epoch;
      final pending = value.signOut();
      expect(value.signedIn, isFalse);
      expect(value.matches(epoch, 1, 12), isFalse);
      await pending;
      expect(value.busy, isFalse);
      expect(value.client.authToken(), isEmpty);
    },
  );
  test(
    'late unauthorized reply from old account never signs out its replacement',
    () async {
      var calls = 0;
      final api = staffApi(
        signIn: (RequestOptions request) =>
            staffReplyFixture(user: ++calls == 1 ? 12 : 13),
      );
      final adapter = _Delayed401(api);
      final value = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = adapter,
      );
      addTearDown(value.dispose);
      await value.signIn(staffCredentials);
      final old = value.client.send(
        method: 'GET',
        url: '${AppConfig.apiV2Url}activities/groups',
        accountId: 12,
        schoolId: 1,
      );
      await Future<void>.delayed(Duration.zero);
      await value.signIn(staffCredentials);
      expect(value.profile!.userId, 13);
      adapter.pending.complete(
        ResponseBody.fromString(
          jsonEncode({'error': true, 'message': 'Expired'}),
          401,
          headers: {
            Headers.contentTypeHeader: [Headers.jsonContentType],
          },
        ),
      );
      await expectLater(old, throwsA(isA<ApiException>()));
      expect(value.profile!.userId, 13);
      expect(value.ready, isTrue);
    },
  );
  test('same-account refresh invalidates every old private reader before revalidation', () async {
    final value = repo(staffApi());
    addTearDown(value.dispose);
    await value.signIn(staffCredentials);
    final old = value.epoch;
    final fresh = value.refresh();
    expect(value.epoch, greaterThan(old));
    expect(value.matches(old, 1, 12), isFalse);
    expect(value.ready, isFalse);
    await fresh;
    expect(value.ready, isTrue);
    expect(value.matches(old, 1, 12), isFalse);
    expect(value.matches(value.epoch, 1, 12), isTrue);
  });
}
