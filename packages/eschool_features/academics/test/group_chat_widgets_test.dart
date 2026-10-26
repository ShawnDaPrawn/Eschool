import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/src/activities/group_chat_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'group_chat_widget_fixture.dart';
import 'group_chat_repository_test.dart' as fixture;

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Finder body(String text) =>
    find.byWidgetPredicate((w) => w is SelectableText && w.data == text);
Future<void> tap(WidgetTester tester, Finder target) async {
  await tester.scrollUntilVisible(
    target,
    160,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(target);
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> open(WidgetTester tester, ChatFixture repo) async {
  await tester.pumpWidget(
    MaterialApp(
      home: GroupChatScreen(repository: repo, onClose: () {}),
    ),
  );
  await tester.pumpAndSettle();
  await tap(tester, find.text('Synthetic private question'));
}

Future<void> enter(WidgetTester tester, String label, String text) async {
  final target = field(label);
  await tester.scrollUntilVisible(
    target,
    160,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.enterText(target, text);
  await tester.pump();
}

Future<void> background(WidgetTester tester) async {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
  await tester.pump();
}

Future<void> resume(WidgetTester tester) async {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('private reply round trip and layout at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 900);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = ChatFixture();
      await open(tester, repo);
      expect(body('Synthetic private family question'), findsOneWidget);
      expect(find.text('Share with this group audience'), findsNothing);
      await enter(tester, 'Message', 'Synthetic exact private reply');
      await tap(tester, find.text('Send message'));
      expect(repo.calls.single['visibility'], 'staff');
      expect(body('Synthetic exact private reply'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'ambiguous retry stays byte-identical; later readonly refusal checks original outcome',
    (tester) async {
      final repo = ChatFixture()
        ..failures.addAll([
          ApiException('Interrupted', isTransportFailure: true),
          ApiException('Readonly', statusCode: 423),
        ]);
      await open(tester, repo);
      await enter(tester, 'Message', 'Synthetic frozen reply');
      await tap(tester, find.text('Send message'));
      expect(find.text('Retry the same change'), findsOneWidget);
      await tap(tester, find.text('Retry the same change'));
      expect(repo.calls.length, 2);
      expect(repo.calls[0], repo.calls[1]);
      expect(find.text('Check previous change'), findsOneWidget);
      expect(field('Message'), findsNothing);
      expect(body('Synthetic private family question'), findsNothing);
      await tap(tester, find.text('Check previous change'));
      expect(
        find.text(
          'The result is still unconfirmed. This screen will not resend the discarded draft.',
        ),
        findsOneWidget,
      );
      expect(repo.calls.length, 2);
    },
  );
  testWidgets(
    'background discards private text and late bodies without automatically resending',
    (tester) async {
      final repo = ChatFixture()
        ..failures.add(ApiException('Interrupted', isTransportFailure: true));
      await open(tester, repo);
      await enter(tester, 'Message', 'Synthetic discarded secret');
      await tap(tester, find.text('Send message'));
      await background(tester);
      expect(body('Synthetic private family question'), findsNothing);
      expect(find.text('Synthetic discarded secret'), findsNothing);
      await resume(tester);
      expect(find.text('Check previous change'), findsOneWidget);
      expect(repo.calls.length, 1);
      expect(field('Message'), findsNothing);
    },
  );
  testWidgets(
    'moderation reason is inline and erased on foreground retirement',
    (tester) async {
      final repo = ChatFixture()..persona = 'staff';
      await open(tester, repo);
      await tap(tester, find.text('Remove message'));
      expect(find.byType(Dialog), findsNothing);
      await enter(
        tester,
        'Private moderation reason',
        'Synthetic private moderation reason',
      );
      await background(tester);
      await resume(tester);
      expect(find.text('Synthetic private moderation reason'), findsNothing);
      expect(field('Private moderation reason'), findsNothing);
      expect(repo.calls, isEmpty);
    },
  );
  testWidgets(
    'same repository retirement is one-way and a replacement starts empty',
    (tester) async {
      final repo = ChatFixture();
      await open(tester, repo);
      repo.active = false;
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(repository: repo, onClose: () {}),
        ),
      );
      await tester.pumpAndSettle();
      expect(body('Synthetic private family question'), findsNothing);
      repo.active = true;
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(repository: repo, onClose: () {}),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('Reopen Activity groups to refresh private access.'),
        findsOneWidget,
      );
      expect(body('Synthetic private family question'), findsNothing);
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(
            repository: ChatFixture()..accountId = 99,
            onClose: () {},
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(body('Synthetic private family question'), findsNothing);
    },
  );
  testWidgets(
    'staff reply names the private participant and uses an explicit private envelope',
    (tester) async {
      final repo = ChatFixture()..persona = 'staff';
      await open(tester, repo);
      await tap(tester, find.text('Reply privately'));
      await enter(tester, 'Message', 'Synthetic supervised answer');
      await tap(tester, find.text('Send message'));
      expect(repo.calls.single['reply_to'], 24);
      expect(repo.calls.single['visibility'], 'staff');
    },
  );
  testWidgets(
    'discarded committed send recovers by metadata without a second write',
    (tester) async {
      final repo = ChatFixture()
        ..failures.add(ApiException('Interrupted', isTransportFailure: true));
      await open(tester, repo);
      await enter(tester, 'Message', 'Synthetic unknown reply');
      await tap(tester, find.text('Send message'));
      final operation = repo.calls.single['operation_id'] as String;
      repo.receipts[operation] = GroupChatMutation.fromJson(fixture.mutation());
      await background(tester);
      await resume(tester);
      await tap(tester, find.text('Check previous change'));
      expect(body('Synthetic private family question'), findsOneWidget);
      expect(find.text('Check previous change'), findsNothing);
      expect(repo.calls.length, 1);
    },
  );
  testWidgets(
    'late conversation response cannot restore a retired foreground body',
    (tester) async {
      final repo = ChatFixture();
      await open(tester, repo);
      final reply = Completer<GroupChatConversation>();
      repo.delayedRead = reply;
      await tester.tap(find.byTooltip('Refresh conversation access'));
      await tester.pump();
      await background(tester);
      reply.complete(repo.conversation());
      repo.delayedRead = null;
      await tester.pump();
      expect(body('Synthetic private family question'), findsNothing);
      await resume(tester);
      expect(body('Synthetic private family question'), findsNothing);
    },
  );
  testWidgets(
    'shared family reply requires the visible explicit school policy choice',
    (tester) async {
      final repo = ChatFixture()..sharing = true;
      await open(tester, repo);
      await tap(tester, find.text('Share with this group audience'));
      await enter(tester, 'Message', 'Synthetic shared reply');
      await tap(tester, find.text('Send message'));
      expect(repo.calls.single['visibility'], 'thread');
    },
  );
  testWidgets(
    'readonly conversations keep permitted moderation while hiding new sends',
    (tester) async {
      final repo = ChatFixture()..writable = false;
      await open(tester, repo);
      expect(find.text('Send message'), findsNothing);
      await tap(tester, find.text('Remove message'));
      await enter(tester, 'Private moderation reason', 'Synthetic correction');
      await tap(tester, find.widgetWithText(FilledButton, 'Remove message'));
      expect(repo.calls.single['_action'], 'remove');
      expect(body('Synthetic private family question'), findsNothing);
      expect(body('Message removed'), findsOneWidget);
    },
  );
  testWidgets('staff announcement composer makes its audience explicit', (
    tester,
  ) async {
    final repo = ChatFixture()..persona = 'staff';
    await tester.pumpWidget(
      MaterialApp(
        home: GroupChatScreen(repository: repo, onClose: () {}),
      ),
    );
    await tester.pumpAndSettle();
    await tap(tester, find.text('Start group announcement'));
    await enter(tester, 'Conversation title', 'Synthetic announced fixture');
    await tap(tester, find.text('Learners'));
    await tap(tester, find.text('Guardians').last);
    await enter(tester, 'Message', 'Synthetic announced update');
    await tap(tester, find.text('Open conversation'));
    expect(repo.calls.single['kind'], 'announcement');
    expect(repo.calls.single['audience'], 'guardians');
  });
  testWidgets(
    'family question composer cannot choose another persona or staff audience',
    (tester) async {
      final repo = ChatFixture();
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(repository: repo, onClose: () {}),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, find.text('Ask group staff privately'));
      expect(find.text('Audience'), findsNothing);
      await enter(
        tester,
        'Conversation title',
        'Synthetic private question title',
      );
      await enter(tester, 'Message', 'Synthetic private new question');
      await tap(tester, find.text('Open conversation'));
      expect(repo.calls.single['kind'], 'question');
      expect(repo.calls.single.containsKey('audience'), isFalse);
    },
  );
  testWidgets(
    'authorized group presence count updates without replacing the private reader',
    (tester) async {
      final repo = ChatFixture();
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(
            repository: repo,
            onlineCount: 2,
            onClose: () {},
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tap(tester, find.text('Synthetic private question'));
      expect(find.text('Online now: 2'), findsOneWidget);
      await tester.pumpWidget(
        MaterialApp(
          home: GroupChatScreen(
            repository: repo,
            onlineCount: 3,
            onClose: () {},
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Online now: 3'), findsOneWidget);
      expect(body('Synthetic private family question'), findsOneWidget);
    },
  );
}
