import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart' show tapWhenReady, pumpUntilFound;

Future<void> verifyGroupChat(
  WidgetTester tester,
  GroupChatClientFixture fixture,
  FakeApi api, {
  required bool bearer,
  bool resetsRoute = false,
}) async {
  await tapWhenReady(tester, find.text('Activity groups'));
  await tapWhenReady(tester, find.text('Hockey'));
  await tapWhenReady(tester, find.text('Group conversations'));
  if (fixture.staff) {
    await tapWhenReady(tester, find.text('Start group announcement'));
    final title = find.byWidgetPredicate(
      (w) => w is TextField && w.decoration?.labelText == 'Conversation title',
    );
    await pumpUntilFound(tester, title);
    await tester.enterText(title, 'Fixture change');
    await tapWhenReady(tester, find.text('Learners'));
    await tapWhenReady(tester, find.text('Guardians').last);
  } else {
    await tapWhenReady(tester, find.text('Fixture change'));
  }
  final input = find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == 'Message',
  );
  await pumpUntilFound(tester, input);
  await tester.ensureVisible(input);
  await tester.enterText(input, 'Synthetic exact group update');
  await tapWhenReady(
    tester,
    find.text(fixture.staff ? 'Open conversation' : 'Send message'),
  );
  await pumpUntilFound(tester, find.text('Retry the same change'));
  await tapWhenReady(tester, find.text('Retry the same change'));
  final body = find.byWidgetPredicate(
    (w) => w is SelectableText && w.data == 'Synthetic exact group update',
  );
  await pumpUntilFound(tester, body);
  expect(fixture.effects, 1);
  expect(fixture.writes.length, 2);
  expect(fixture.writes[0], fixture.writes[1]);
  expect(fixture.writes.first['persona'], fixture.persona);
  if (!fixture.staff) {
    expect(fixture.writes.first['visibility'], 'staff');
    expect(find.text('Share with this group audience'), findsNothing);
  } else {
    expect(fixture.writes.first['kind'], 'announcement');
    expect(fixture.writes.first['audience'], 'guardians');
  }
  final requests = api.requests
      .where((r) => r.uri.path.contains('/activities/'))
      .toList();
  expect(requests, isNotEmpty);
  for (final request in requests) {
    expect(request.headers['X-School-Id'], '1');
    expect(request.headers['X-Account-Id'], '${fixture.account}');
    expect(
      bearer
          ? '${request.headers['Authorization']}'.startsWith('Bearer ')
          : request.headers['Authorization'] == null,
      isTrue,
    );
    if (request.uri.path.endsWith('/groups/7'))
      expect(request.queryParameters, {'chat_persona': fixture.persona});
    else if (request.method == 'GET' && request.uri.path.contains('/chat/'))
      expect(request.queryParameters, {'persona': fixture.persona});
    else if (request.method == 'POST')
      expect(request.queryParameters, isEmpty);
  }
  fixture.allowed = false;
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  await tester.pump();
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await pumpUntilFound(
    tester,
    find.text(
      resetsRoute ? 'Your school activities' : 'Not available for this account',
    ),
  );
  expect(
    find.text('Synthetic exact group update', skipOffstage: false),
    findsNothing,
  );
  expect(
    find.text('Synthetic private coach question', skipOffstage: false),
    findsNothing,
  );
  expect(find.byType(SelectableText), findsNothing);
  expect(tester.takeException(), isNull);
}
