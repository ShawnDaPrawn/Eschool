import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'message_test_repository.dart';

Future<void> messageFrame(
  WidgetTester tester,
  Widget child,
  double width,
) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(theme: ThemeData(useMaterial3: true), home: child),
  );
  await tester.pumpAndSettle();
}

Future<void> messageTap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Finder messageField(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
void main() {
  for (final width in [390.0, 1024.0, 1280.0]) {
    testWidgets(
      'family reads a private message and acknowledges it at $width',
      (tester) async {
        final repo = FakeMessageRepository()..staff = false;
        await messageFrame(
          tester,
          SchoolMessagesScreen(repository: repo),
          width,
        );
        await messageTap(tester, find.text('Private weekly update'));
        expect(find.byType(SchoolMessageReaderScreen), findsOneWidget);
        expect(find.textContaining('Private school text.'), findsOneWidget);
        expect(repo.reads, 1);
        if (width >= 1024) {
          expect(find.byType(NavigationRail), findsOneWidget);
          expect(find.text('Private weekly update'), findsNWidgets(2));
        }
        expect(tester.takeException(), isNull);
      },
    );
    testWidgets(
      'teacher composes previews and submits with explicit audience at $width',
      (tester) async {
        final repo = FakeMessageRepository();
        await messageFrame(
          tester,
          SchoolMessagesScreen(repository: repo),
          width,
        );
        await messageTap(tester, find.text('New school message'));
        await tester.enterText(
          messageField('Message subject'),
          'A private family reminder',
        );
        await tester.enterText(
          messageField('Message Markdown'),
          '# Reminder\n\nPlease bring your book.',
        );
        await messageTap(tester, find.text('Save private message'));
        expect(repo.writes.single.title, 'A private family reminder');
        expect(repo.writes.single.audience.targetId, 9);
        await messageTap(tester, find.text('Preview saved message'));
        await messageTap(tester, find.text('Submit previewed message'));
        expect(repo.submits, hasLength(1));
        expect(repo.state, 'pending');
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'reviewer approves another authors saved message without an editable body',
    (tester) async {
      final repo = FakeMessageRepository()
        ..author = false
        ..reviewer = true
        ..state = 'pending';
      await messageFrame(
        tester,
        SchoolMessagesScreen(repository: repo, initialMessage: 11),
        1280,
      );
      expect(messageField('Message Markdown'), findsNothing);
      await messageTap(tester, find.text('Preview saved message'));
      await messageTap(tester, find.text('Approve message'));
      await tester.enterText(
        messageField('Review reason'),
        'Reviewed this synthetic message.',
      );
      await messageTap(tester, find.text('Continue'));
      expect(repo.decisions, hasLength(1));
      expect(repo.state, 'approved');
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'uncertain save freezes editing and retries the original operation',
    (tester) async {
      final repo = FakeMessageRepository();
      await messageFrame(
        tester,
        SchoolMessageEditorScreen(repository: repo, id: 11),
        390,
      );
      await tester.enterText(
        messageField('Message Markdown'),
        'My local draft',
      );
      repo.writeError = ApiException('No reply', isTransportFailure: true);
      await messageTap(tester, find.text('Save private message'));
      expect(
        tester.widget<TextField>(messageField('Message Markdown')).enabled,
        isFalse,
      );
      repo.writeError = null;
      await messageTap(tester, find.text('Retry same message change'));
      expect(repo.writes, hasLength(2));
      expect(repo.writes[0].toJson(), repo.writes[1].toJson());
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'visible withdrawal clears the body on refresh in the two-pane screen',
    (tester) async {
      final repo = FakeMessageRepository()..staff = false;
      await messageFrame(tester, SchoolMessagesScreen(repository: repo), 1280);
      await messageTap(tester, find.text('Private weekly update'));
      expect(find.textContaining('Private school text.'), findsOneWidget);
      repo.readError = ApiException('Withdrawn', statusCode: 404);
      await messageTap(tester, find.byTooltip('Refresh messages'));
      expect(find.textContaining('Private school text.'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'background hides local drafts and resume retains them for comparison',
    (tester) async {
      final repo = FakeMessageRepository();
      await messageFrame(tester, SchoolMessagesScreen(repository: repo), 1280);
      await messageTap(tester, find.text('New school message'));
      await tester.enterText(
        messageField('Message Markdown'),
        'PRIVATE local draft retained across resume',
      );
      tester.binding.handleAppLifecycleStateChanged(
        AppLifecycleState.inactive,
      );
      await tester.pump();
      expect(
        find.text('PRIVATE local draft retained across resume'),
        findsNothing,
      );
      tester.binding.handleAppLifecycleStateChanged(
        AppLifecycleState.resumed,
      );
      await tester.pumpAndSettle();
      expect(
        tester
            .widget<TextField>(messageField('Message Markdown'))
            .controller!
            .text,
        'PRIVATE local draft retained across resume',
      );
      expect(
        find.text('Keep my local text against the current revision'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
