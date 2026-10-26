import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart' show pumpUntilFound, tapWhenReady;

Future<void> examTap(WidgetTester tester, Finder target) async {
  await tester.pump(const Duration(milliseconds: 300));
  await pumpUntilFound(tester, find.byType(Scrollable));
  tester
      .state<ScrollableState>(find.byType(Scrollable).first)
      .position
      .jumpTo(0);
  await tester.pump();
  await tester.scrollUntilVisible(
    target,
    230,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(target);
  await tapWhenReady(tester, target);
}

Future<void> examFill(WidgetTester tester, String label, String text) async {
  final field = find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == label,
  );
  await tester.pump(const Duration(milliseconds: 300));
  await pumpUntilFound(tester, find.byType(Scrollable));
  tester
      .state<ScrollableState>(find.byType(Scrollable).first)
      .position
      .jumpTo(0);
  await tester.pump();
  await tester.scrollUntilVisible(
    field,
    230,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(field);
  await tester.enterText(field, text);
  await tester.pump();
}

Future<void> runExamTeacher(
  WidgetTester tester,
  ExamCoachSmokeFixture fixture,
) async {
  await examTap(tester, find.text('Prepare licensed paper'));
  await examFill(tester, 'Paper title', 'Synthetic licensed paper');
  await examFill(tester, 'Paper code', 'MATH-P1');
  await examFill(tester, 'Permission reference', 'Synthetic permission');
  await examFill(
    tester,
    'Permission statement',
    'The school obtained synthetic test permission.',
  );
  await examTap(
    tester,
    find.text(
      'I checked the school has permission to use this paper and memo.',
    ),
  );
  await examFill(tester, 'Question number', '1');
  await examFill(tester, 'Question text', 'Explain one half.');
  await examFill(tester, 'Staff memo', 'PRIVATE-STAFF-MEMO-CANARY');
  await examTap(tester, find.text('Fractions'));
  await examTap(tester, find.text('Save reviewed draft'));
  await pumpUntilFound(tester, find.text('Retry exact saved request'));
  await examTap(tester, find.text('Retry exact saved request'));
  await pumpUntilFound(tester, find.text('Publish reviewed paper'));
  expect(fixture.paperEffects, 1);
  expect(fixture.writes.length, 2);
  expect(fixture.writes.first, fixture.writes.last);
  await examTap(tester, find.text('Publish reviewed paper'));
  await pumpUntilFound(tester, find.text('Synthetic learner 1'));
  await examTap(tester, find.text('Synthetic learner 1'));
  tester
      .state<ScrollableState>(find.byType(Scrollable).first)
      .position
      .jumpTo(0);
  await tester.pump();
  await tester.scrollUntilVisible(
    find.text('Explain the two equal parts.'),
    220,
    scrollable: find.byType(Scrollable).first,
  );
  await pumpUntilFound(tester, find.text('Explain the two equal parts.'));
  expect(find.textContaining('73.0%'), findsOneWidget);
  await examFill(tester, 'Verified marks', '0.75');
  await examFill(tester, 'Review reason', 'Teacher verified the equal parts.');
  await examTap(tester, find.text('Confirm or correct provisional feedback'));
  await examTap(tester, find.text('Retry exact saved request'));
  tester.state<ScrollableState>(find.byType(Scrollable).first).position.jumpTo(0);
  await tester.pump();
  await tester.scrollUntilVisible(find.text('Teacher review'), 220,
    scrollable: find.byType(Scrollable).first);
  await pumpUntilFound(tester, find.text('Teacher review'));
  expect(fixture.reviewEffects, 1);
  expect(fixture.reviews.length, 2);
  expect(fixture.reviews.first, fixture.reviews.last);
  expect(fixture.reviews.first['mark_units'], 750);
  expect(tester.takeException(), isNull);
}
