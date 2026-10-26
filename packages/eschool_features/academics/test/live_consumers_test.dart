import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'activity_test_repository.dart';
import 'consultations/consultation_fixtures.dart';

class _Live extends LiveSession {
  _Live()
    : super(
        client: ApiClient(authToken: () => '', onUnauthorized: () {}),
        schoolId: 1,
        accountId: 12,
        contextActive: () => true,
      );
  final joined = <int>[], left = <int>[];
  final denied = StreamController<int>.broadcast();
  @override
  Stream<int> get deniedGroups => denied.stream;
  @override
  Future<void> joinGroup(int group) async {
    joined.add(group);
  }

  @override
  Future<void> leaveGroup(int group) async {
    left.add(group);
  }
}

LiveInvalidation presence(int count) => LiveInvalidation.fromJson({
  'type': 'group.presence',
  'school_id': 1,
  'group_id': 7,
  'revision': count + 1,
  'online_count': count,
}, expectedSchoolId: 1);
LiveInvalidation groupChange() => LiveInvalidation.fromJson({
  'type': 'activity.group_changed',
  'school_id': 1,
  'group_id': 7,
  'revision': 1,
}, expectedSchoolId: 1);
LiveInvalidation pollChange(int poll) => LiveInvalidation.fromJson({
  'type': 'consultations.counts_changed',
  'school_id': 1,
  'consultation_id': poll,
  'revision': 1,
}, expectedSchoolId: 1);
Future<void> tap(WidgetTester tester, String text) async {
  await tester.ensureVisible(find.text(text).first);
  await tester.pumpAndSettle();
  await tester.tap(find.text(text).first);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets(
    'group opens an explicit presence watch and fresh denial erases its body',
    (tester) async {
      final live = _Live(),
          events = StreamController<LiveInvalidation>.broadcast();
      final repo = FakeActivityRepository();
      await tester.pumpWidget(
        LiveUpdatesScope(
          events: events.stream,
          session: live,
          child: MaterialApp(home: ActivityGroupsScreen(repository: repo)),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, 'Hockey');
      expect(live.joined, [7]);
      events.add(presence(3));
      await tester.pumpAndSettle();
      expect(find.text('Online now: 3'), findsOneWidget);
      live.denied.add(7);
      await tester.pumpAndSettle();
      expect(find.text('Hockey'), findsNothing);
      expect(find.text('Online now: 3', skipOffstage: false), findsNothing);
      expect(find.text('Not available for this account'), findsOneWidget);
      await tester.pumpWidget(const SizedBox());
      expect(live.left, contains(7));
      await events.close();
      await live.denied.close();
      await live.dispose();
    },
  );

  testWidgets(
    'group invalidation refreshes protected detail while dirty capture retains its draft',
    (tester) async {
      final events = StreamController<LiveInvalidation>.broadcast();
      var reads = 0;
      final repo = FakeActivityRepository()
        ..readDetail = () async {
          reads++;
          return ActivityGroupDetail.fromJson(activityDetailFixture());
        };
      await tester.pumpWidget(
        LiveUpdatesScope(
          events: events.stream,
          child: MaterialApp(home: ActivityGroupsScreen(repository: repo)),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, 'Hockey');
      expect(reads, 1);
      events.add(groupChange());
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 350));
      await tester.pumpAndSettle();
      expect(reads, 2);
      await tap(tester, 'Open attendance');
      final field = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Attendance reason',
      );
      await tester.ensureVisible(field);
      await tester.enterText(field, 'OWN UNSAVED REASON');
      events.add(groupChange());
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 350));
      await tester.pumpAndSettle();
      expect(reads, 2);
      expect(find.text('OWN UNSAVED REASON'), findsOneWidget);
      expect(find.text('Updates available'), findsOneWidget);
      await tester.pumpWidget(const SizedBox());
      await events.close();
    },
  );

  testWidgets(
    'only the open poll results refetch and revoked result access erases aggregates',
    (tester) async {
      final events = StreamController<LiveInvalidation>.broadcast();
      final repo = FakeConsultationRepository()..live = true;
      await tester.pumpWidget(
        LiveUpdatesScope(
          events: events.stream,
          child: MaterialApp(home: ConsultationsScreen(repository: repo)),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, repo.title);
      await tap(tester, 'View consultation results');
      expect(repo.resultsCalls, 1);
      events.add(pollChange(900));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 350));
      expect(repo.resultsCalls, 1);
      events.add(pollChange(9));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 350));
      await tester.pumpAndSettle();
      expect(repo.resultsCalls, 2);
      repo.deny = true;
      events.add(pollChange(9));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 350));
      await tester.pumpAndSettle();
      expect(
        find.text('Responses recorded: 4', skipOffstage: false),
        findsNothing,
      );
      expect(
        find.textContaining('Your consultation access changed'),
        findsOneWidget,
      );
      await tester.pumpWidget(const SizedBox());
      await events.close();
    },
  );
}
