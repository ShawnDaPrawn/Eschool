import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fixture.dart';

DateTime clock() => DateTime.utc(2026, 10, 30, 8);
void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'guardian booking and exact collection-request recovery at $width',
      (tester) async {
        final repo = Visits();
        await mount(
          tester,
          FamilyVisitsScreen(repository: repo, now: clock),
          width: width,
        );
        expect(repo.meetingLearner, 11);
        await tap(tester, 'Book this meeting');
        expect(repo.effects, 1);
        expect(find.text('Booked'), findsOneWidget);
        await tap(tester, 'Cancel my booking');
        expect(repo.effects, 2);
        expect(find.text('Available'), findsOneWidget);
        await tap(tester, 'Early collection');
        await tester.enterText(
          find.byKey(const ValueKey('visit-collector')),
          'Synthetic private collector',
        );
        await tester.enterText(
          find.byKey(const ValueKey('visit-reason')),
          'Synthetic private collection reason',
        );
        await tester.pump();
        repo.unknown = true;
        await tap(tester, 'Request staff approval');
        expect(
          find.text(
            'The request outcome is uncertain. Retry the original request.',
          ),
          findsOneWidget,
        );
        expect(find.text('Synthetic private collector'), findsNothing);
        expect(find.text('Retry original request'), findsOneWidget);
        final first = repo.mutations.last;
        await tap(tester, 'Retry original request');
        expect(repo.mutations.last.toJson(), first.toJson());
        expect(repo.effects, 3);
        expect(repo.mutations, hasLength(4));
        expect(repo.mutations.last.body['starts_at'], '2026-10-30T10:15');
        expect(find.text('Awaiting staff approval'), findsOneWidget);
        expect(find.text('Record collection'), findsNothing);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets(
    'readonly parent sees records without a booking or collection-submit capability',
    (tester) async {
      final repo = Visits(writable: false);
      await mount(tester, FamilyVisitsScreen(repository: repo, now: clock));
      expect(
        tester
            .widget<FilledButton>(
              find.byKey(const ValueKey('visit-book-$meetingId')),
            )
            .onPressed,
        isNull,
      );
      await tap(tester, 'Early collection');
      expect(
        tester
            .widget<FilledButton>(find.byKey(const ValueKey('visit-request')))
            .onPressed,
        isNull,
      );
      expect(repo.mutations, isEmpty);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'ordinary staff have only their calendar and no guardian collection form',
    (tester) async {
      final repo = Visits(staff: true)
        ..slot = slotJson(status: 'booked', learner: 11, guardian: 7);
      await mount(
        tester,
        FamilyVisitsScreen(repository: repo, staffOnly: true, now: clock),
      );
      expect(repo.meetingLearner, isNull);
      expect(find.text('Synthetic current learner'), findsOneWidget);
      expect(find.byKey(const ValueKey('visit-collector')), findsNothing);
      expect(find.text('Approve this collection request'), findsNothing);
      expect(find.text('Book this meeting'), findsNothing);
    },
  );
  testWidgets(
    'background wipes private forms and late records cannot return after fresh withdrawal',
    (tester) async {
      final repo = Visits();
      await mount(tester, FamilyVisitsScreen(repository: repo, now: clock));
      repo.delayedMeetings = Completer();
      await tester.tap(find.text('Refresh current records'));
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      await tester.pump();
      repo.delayedMeetings!.complete(
        FamilyMeetingPage.fromJson({
          'items': [slotJson()],
          'next_page': null,
        }),
      );
      await tester.pump();
      expect(find.text('Synthetic parent meeting'), findsNothing);
      repo.revoked = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic parent meeting'), findsNothing);
      expect(find.byKey(const ValueKey('visit-collector')), findsNothing);
    },
  );
  testWidgets(
    'sealed unknown write survives background only behind a fresh guardian check',
    (tester) async {
      final repo = Visits()..unknown = true;
      await mount(tester, FamilyVisitsScreen(repository: repo, now: clock));
      await tap(tester, 'Early collection');
      await tester.enterText(
        find.byKey(const ValueKey('visit-collector')),
        'Synthetic private collector',
      );
      await tester.enterText(
        find.byKey(const ValueKey('visit-reason')),
        'Synthetic private collection reason',
      );
      await tester.pump();
      await tap(tester, 'Request staff approval');
      final first = repo.mutations.single;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      await tester.pump();
      expect(find.text('Synthetic private collector'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      await tap(tester, 'Retry original request');
      expect(repo.mutations.last.toJson(), first.toJson());
      expect(repo.effects, 1);
      expect(repo.configurations, greaterThanOrEqualTo(4));
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
