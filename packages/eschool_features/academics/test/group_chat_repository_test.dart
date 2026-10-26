import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/src/activities/group_chat_repository.dart';

const threadId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
const base = 'v2/activities/groups/7/chat';
Map<String, dynamic> threadJson() => {
  'id': threadId,
  'activity_group_id': 7,
  'title': 'Synthetic private question',
  'audience': 'guardians',
  'private': true,
  'closed': false,
  'revision': 1,
  'last_message_at': '2026-02-10T12:00:00Z',
};
Map<String, dynamic> pageJson() => {
  'items': [
    {...threadJson(), 'unread': 1},
  ],
  'next_before': null,
  'policy': {
    'revision': 0,
    'staff_learners': true,
    'staff_guardians': true,
    'staff_staff': true,
    'learner_shared_replies': false,
    'guardian_shared_replies': false,
  },
  'persona': 'parent',
  'writable': true,
  'max_characters': 500,
};
Map<String, dynamic> mutation() => {
  'operation_type': 'message_sent',
  'persona': 'parent',
  'thread_id': threadId,
  'activity_group_id': 7,
  'revision': 1,
  'message_id': 24,
};
Object ok(Object data) => {'error': false, 'data': data};
ApiGroupChatRepository repo(
  FakeApi api, {
  bool Function()? active,
  String Function()? token,
  bool cookies = false,
}) => ApiGroupChatRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic',
    sendsBearerToken: !cookies,
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 12,
  groupId: 7,
  persona: 'parent',
  contextActive: active ?? () => true,
);

void main() {
  test(
    'school/account/persona are explicit for both bearer and cookie clients',
    () async {
      for (final cookies in [false, true]) {
        final api = FakeApi(routes: {'GET $base/threads': ok(pageJson())});
        final page = await repo(api, cookies: cookies).threads();
        expect(page.policy.guardianSharedReplies, isFalse);
        final request = api.requests.single;
        expect(request.headers['X-School-Id'], '1');
        expect(request.headers['X-Account-Id'], '12');
        expect(request.queryParameters['persona'], 'parent');
      }
    },
  );
  test(
    'malformed group/persona response never reaches a private reader',
    () async {
      for (final field in ['group', 'persona', 'cursor']) {
        final page = pageJson();
        if (field == 'group')
          (page['items'] as List).single['activity_group_id'] = 8;
        if (field == 'persona') page['persona'] = 'staff';
        if (field == 'cursor') page['next_before'] = 'not-an-id';
        final api = FakeApi(routes: {'GET $base/threads': ok(page)});
        await expectLater(
          repo(api).threads(),
          throwsA(
            isA<ApiException>().having((e) => e.statusCode, 'status', 403),
          ),
        );
      }
    },
  );
  test(
    'same-account generation and cookie context retirement discard late bodies',
    () async {
      for (final cookies in [false, true]) {
        var active = true;
        final api = FakeApi(
          routes: {
            'GET $base/threads': (RequestOptions request) {
              active = false;
              return ok(pageJson());
            },
          },
        );
        await expectLater(
          repo(api, cookies: cookies, active: () => active).threads(),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test(
    'token replacement or inactive context refuse before transport',
    () async {
      var token = 'first', active = true;
      final api = FakeApi(routes: {});
      final repository = repo(api, token: () => token, active: () => active);
      token = 'second';
      await expectLater(repository.threads(), throwsA(isA<ApiException>()));
      token = 'first';
      active = false;
      await expectLater(repository.threads(), throwsA(isA<ApiException>()));
      expect(api.requests, isEmpty);
    },
  );
  test(
    'ambiguous exact retry retains original UUID and immutable private text',
    () async {
      final data = {
        'body': 'Synthetic private text',
        'language': 'en',
        'visibility': 'staff',
      };
      final intent = ChatWriteIntent(threadId, data);
      data['body'] = 'Changed composer';
      final api = FakeApi(
        routes: {'POST $base/threads/$threadId/messages': ok(mutation())},
      );
      final repository = repo(api);
      await repository.send(threadId, intent);
      await repository.send(threadId, intent);
      expect(api.requests.length, 2);
      expect(api.requests.first.data, api.requests.last.data);
      expect(
        (api.requests.first.data as Map)['body'],
        'Synthetic private text',
      );
      expect((api.requests.first.data as Map)['operation_id'], threadId);
      intent.erase();
      await expectLater(
        repository.send(threadId, intent),
        throwsA(isA<ApiException>()),
      );
      expect(api.requests.length, 2);
    },
  );
  test('erased inflight draft cannot restore its successful result', () async {
    final intent = ChatWriteIntent(threadId, {
      'body': 'Synthetic private text',
    });
    final api = FakeApi(
      routes: {
        'POST $base/threads/$threadId/messages': (RequestOptions request) {
          intent.erase();
          return ok(mutation());
        },
      },
    );
    await expectLater(
      repo(api).send(threadId, intent),
      throwsA(isA<ApiException>()),
    );
    expect(intent.erased, isTrue);
    expect(() => intent.fields, throwsStateError);
  });
  test(
    'metadata recovery binds original operation, group and persona',
    () async {
      for (final field in ['operation', 'group', 'persona']) {
        final receipt = {
          'recorded': true,
          'operation_id': threadId,
          'receipt': mutation(),
        };
        if (field == 'operation')
          receipt['operation_id'] = '00000000-0000-4000-8000-000000000000';
        if (field == 'group')
          (receipt['receipt'] as Map)['activity_group_id'] = 8;
        if (field == 'persona')
          (receipt['receipt'] as Map)['persona'] = 'staff';
        final api = FakeApi(
          routes: {'GET $base/operations/$threadId': ok(receipt)},
        );
        await expectLater(
          repo(api).receipt(threadId),
          throwsA(isA<ApiException>()),
        );
      }
    },
  );
  test(
    'a family reader refuses another private participant or an undeclared peer audience',
    () async {
      for (final change in [
        'private-other',
        'peer-off',
        'other-persona',
        'other-audience',
      ]) {
        final message = <String, dynamic>{
          'id': 24,
          'sender_id': 99,
          'sender_name': 'Synthetic participant',
          'sender_persona': 'parent',
          'body': 'Synthetic private content',
          'language': 'en',
          'revision': 1,
          'reply_to': null,
          'private': true,
          'removed': false,
          'can_remove': false,
          'sent_at': '2026-02-10T12:00:00Z',
        };
        final conversation = <String, dynamic>{
          'thread': threadJson(),
          'items': [message],
          'policy': pageJson()['policy'],
          'next_before': null,
          'can_close': true,
          'can_post': true,
          'can_reopen': false,
          'max_characters': 500,
        };
        if (change == 'peer-off') message['private'] = false;
        if (change == 'other-persona') message['sender_persona'] = 'student';
        if (change == 'other-audience')
          (conversation['thread'] as Map)['audience'] = 'learners';
        final api = FakeApi(
          routes: {'GET $base/threads/$threadId': ok(conversation)},
        );
        await expectLater(
          repo(api).read(threadId),
          throwsA(
            isA<ApiException>().having((e) => e.statusCode, 'status', 403),
          ),
        );
      }
    },
  );
}
