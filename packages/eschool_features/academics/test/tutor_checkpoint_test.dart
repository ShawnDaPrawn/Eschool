import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/tutor/tutor_checkpoint_card.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'tutor_fixture.dart';

const checkId = '10000000-0000-4000-8000-000000000006';

class Checks extends FakeTutorRepository implements TutorCheckpointRepository {
  bool lose = false, unavailable = false, denied = false, answered = false;
  final issued = <String>[], submitted = <Map<String, dynamic>>[];
  String? operation;
  TutorCheckpointDetail result() => TutorCheckpointDetail.fromJson({
    'school_id': 1,
    'reader_id': 7,
    'id': checkId,
    'session_id': tutorSession,
    'learner_id': 9,
    'topic_id': 6,
    'operation_id': operation ?? tutorOperation,
    'answer_operation_id': answered ? submitted.last['operation_id'] : null,
    'state': answered ? 'answered' : 'ready',
    'question': {
      'kind': 'number',
      'prompt': 'PRIVATE-CHECK: one half as a decimal?',
      'options': [],
    },
    'correct': answered ? true : null,
    'explanation': answered
        ? 'APPROVED-EXPLANATION: divide into equal parts.'
        : null,
    'answered_at': answered ? '2026-10-26T10:15:30Z' : null,
    'verified_by': answered ? 'approved_answer_definition' : null,
  });
  @override
  Future<TutorCheckpointDetail> issueCheckpoint(
    String session,
    String op,
  ) async {
    issued.add(op);
    operation = op;
    return result();
  }

  @override
  Future<TutorCheckpointDetail> checkpoint(String session, String id) async {
    if (denied) throw ApiException('Denied', statusCode: 403);
    if (unavailable) throw ApiException('Missing', statusCode: 404);
    return result();
  }

  @override
  Future<TutorCheckpointDetail> answerCheckpoint(
    String session,
    String id,
    TutorCheckpointAnswerWrite input,
  ) async {
    submitted.add(input.toJson());
    if (lose) {
      lose = false;
      throw StateError('Synthetic lost response');
    }
    answered = true;
    return result();
  }
}

class TopicChecks extends Checks {
  TutorSessionWrite? created;
  bool checksEnabled = true;
  @override
  Future<TutorConfiguration> configuration() async {
    final body = tutorConfigurationJson(enabled: false);
    (body['groups'] as List).first['topics'] = [
      {'id': 6, 'name': 'Fractions'},
    ];
    body['checkpoints_enabled'] = checksEnabled;
    return TutorConfiguration.fromJson(body);
  }

  @override
  TutorSessionDetail detail() => TutorSessionDetail.fromJson({
    ...super.detail().toJson(),
    'topic_id': 6,
    'checkpoints': [],
  });
  @override
  Future<TutorSessionResult> create(TutorSessionWrite input) async {
    created = input;
    return TutorSessionResult.fromJson({
      ...super.detail().toJson(),
      'topic_id': input.topicId,
    });
  }
}

Future<void> press(WidgetTester tester, String label) async {
  final target = find.text(label);
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> mount(
  WidgetTester tester,
  Checks repo,
  TutorCheckpointRecovery recovery,
) async {
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await tester.pumpWidget(
    MaterialApp(
      home: Scaffold(
        body: SingleChildScrollView(
          child: TutorCheckpointCard(
            repository: repo,
            owner: repo,
            session: tutorSession,
            recovery: recovery,
            current: () => repo.active,
            writable: () => true,
            summaries: const [],
          ),
        ),
      ),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  testWidgets(
    'published topic pins session and approved checks remain usable while inference is off',
    (tester) async {
      final repo = TopicChecks();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpWidget(MaterialApp(home: TutorScreen(repository: repo)));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('tutor-topic-3')));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Fractions').last);
      await tester.pumpAndSettle();
      await press(tester, 'Start a learning conversation');
      expect(repo.created!.topicId, 6);
      expect(find.text('Verified practice check'), findsOneWidget);
      await press(tester, 'Start an approved check');
      expect(find.textContaining('PRIVATE-CHECK'), findsOneWidget);
      expect(repo.writes, isEmpty);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'practice capability off hides checks without removing the existing topic conversation',
    (tester) async {
      final repo = TopicChecks()..checksEnabled = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpWidget(
        MaterialApp(
          home: TutorScreen(repository: repo, initialSession: tutorSession),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Verified practice check'), findsNothing);
      expect(find.text('Fractions'), findsOneWidget);
      expect(
        find.text(
          'Your school is preparing the tutor. Try again when it is available.',
        ),
        findsOneWidget,
      );
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'approved first answer reveals explanation only after verification and never invokes chat',
    (tester) async {
      final repo = Checks(), recovery = TutorCheckpointRecovery();
      addTearDown(recovery.close);
      await mount(tester, repo, recovery);
      await press(tester, 'Start an approved check');
      expect(find.textContaining('PRIVATE-CHECK'), findsOneWidget);
      expect(find.textContaining('APPROVED-EXPLANATION'), findsNothing);
      await tester.enterText(find.byType(TextField), '0.5');
      await press(tester, 'Check my first answer');
      expect(find.text('Verified correct'), findsOneWidget);
      expect(find.textContaining('APPROVED-EXPLANATION'), findsOneWidget);
      expect(repo.submitted.single['answer'], '0.5');
      expect(repo.writes, isEmpty);
      expect(recovery.pending, isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'uncertain answer survives background encrypted and retries exact UUID after fresh source read',
    (tester) async {
      final repo = Checks()..lose = true;
      final recovery = TutorCheckpointRecovery();
      addTearDown(recovery.close);
      await mount(tester, repo, recovery);
      await press(tester, 'Start an approved check');
      await tester.enterText(find.byType(TextField), '0.500');
      await press(tester, 'Check my first answer');
      final original = Map<String, dynamic>.from(repo.submitted.single);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.textContaining('PRIVATE-CHECK'), findsNothing);
      expect(find.byType(TextField), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(tester.widget<TextField>(find.byType(TextField)).readOnly, isTrue);
      await press(tester, 'Retry exact first answer');
      expect(repo.submitted, [original, original]);
      expect(repo.issued.length, 1);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'racing receipt 404 erases question and cannot mint a replacement issue UUID',
    (tester) async {
      final repo = Checks()..lose = true;
      final recovery = TutorCheckpointRecovery();
      addTearDown(recovery.close);
      await mount(tester, repo, recovery);
      await press(tester, 'Start an approved check');
      await tester.enterText(find.byType(TextField), '0.5');
      await press(tester, 'Check my first answer');
      final op = repo.issued.single;
      repo.unavailable = true;
      await press(tester, 'Check answer status');
      expect(find.textContaining('PRIVATE-CHECK'), findsNothing);
      repo.unavailable = false;
      await press(tester, 'Recover the same approved check');
      expect(repo.issued, [op, op]);
      await press(tester, 'Retry exact first answer');
      expect(repo.submitted[1], repo.submitted[0]);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'fresh source denial removes private question and closes retained answer intent',
    (tester) async {
      final repo = Checks()..lose = true;
      final recovery = TutorCheckpointRecovery();
      addTearDown(recovery.close);
      await mount(tester, repo, recovery);
      await press(tester, 'Start an approved check');
      await tester.enterText(find.byType(TextField), '0.5');
      await press(tester, 'Check my first answer');
      repo.denied = true;
      await press(tester, 'Check answer status');
      expect(find.textContaining('PRIVATE-CHECK'), findsNothing);
      expect(recovery.pending, isNull);
      expect(
        find.text('Reopen the tutor to check your access.'),
        findsOneWidget,
      );
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
