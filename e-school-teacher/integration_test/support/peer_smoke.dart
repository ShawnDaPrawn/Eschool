import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart';

Future<void> exercisePeerSupervisor(
  WidgetTester tester,
  PeerSmokeFixture fixture,
) async {
  Finder field(String label) => find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == label,
  );
  await tapWhenReady(tester, find.text('Peer tutoring'));
  await pumpUntilFound(tester, find.text('Synthetic supervised room'));
  await tapWhenReady(tester, find.byType(DropdownButtonFormField<int>).first);
  await tapWhenReady(tester, find.text('Synthetic maths group').last);
  await pumpUntilFound(tester, find.text('Learner needing support'));
  await tapWhenReady(tester, find.byType(DropdownButtonFormField<int>).at(1));
  await tapWhenReady(tester, find.text('Synthetic peer learner').last);
  await pumpUntilFound(
    tester,
    find.ancestor(
      of: find.text('Tutor and topic'),
      matching: find.byWidgetPredicate((w) => w is DropdownButtonFormField),
    ),
  );
  await tapWhenReady(
    tester,
    find.ancestor(
      of: find.text('Tutor and topic'),
      matching: find.byWidgetPredicate((w) => w is DropdownButtonFormField),
    ),
  );
  await tapWhenReady(
    tester,
    find.text('Synthetic own learner · Synthetic fractions').last,
  );
  await tester.enterText(
    field('Starts at (YYYY-MM-DDTHH:MM)'),
    '2099-11-02T14:00',
  );
  await tester.enterText(
    field('Ends at (YYYY-MM-DDTHH:MM)'),
    '2099-11-02T15:00',
  );
  await tester.enterText(field('Venue'), 'Synthetic new supervised room');
  await tapWhenReady(tester, find.text('Schedule peer session'));
  await pumpUntilFound(tester, find.text('Retry original request'));
  await tapWhenReady(tester, find.text('Retry original request'));
  await pumpUntilFound(tester, find.text('Synthetic new supervised room'));
  expect(fixture.effects, 1);
  expect(fixture.writes[0], fixture.writes[1]);
  expect(fixture.scheduled!['starts_at'], '2099-11-02T14:00');
  await tapWhenReady(tester, find.text('Confirm supervised service'));
  await pumpUntilFound(tester, field('Verified service minutes'));
  await tester.enterText(field('Verified service minutes'), '35');
  await tapWhenReady(tester, find.text('Confirm session change'));
  await pumpUntilFound(tester, find.text('Verified service minutes: 35'));
  await tapWhenReady(tester, find.text('Correct completed service'));
  await pumpUntilFound(tester, field('Reason'));
  await tester.enterText(
    field('Reason'),
    'Synthetic correction of supervision record',
  );
  await tapWhenReady(tester, find.text('Confirm session change'));
  await pumpUntilFound(tester, find.text('reversed'));
  expect(fixture.effects, 3);
  expect(
    fixture.writes.last['body']['reason'],
    'Synthetic correction of supervision record',
  );
  expect(tester.takeException(), isNull);
}
