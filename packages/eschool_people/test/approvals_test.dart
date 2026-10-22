import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

Map<String, Object?> approvalJson(
  int id,
  String name,
  String status, {
  String? studentNumber,
}) => {
  'learner': {
    'id': id,
    'name': name,
    'image': null,
    'student_number': studentNumber,
    'school': school(1, 'Northcliff High', shortName: 'NHS'),
  },
  'status': status,
  'requested_at': '2026-10-20T08:00:00Z',
  'consent': {
    'version': '2026-10',
    'text': 'I agree that my child may use NavigateEd at school.',
  },
};

class FakeApprovals implements ApprovalsRepository {
  List<GuardianApproval>? answer = [
    GuardianApproval.fromJson(
      approvalJson(21, 'Asha Demo', 'pending', studentNumber: 'NE-21'),
    ),
    GuardianApproval.fromJson(approvalJson(23, 'Cara Demo', 'approved')),
  ];
  Object? error;
  final List<(String, int, String)> decisions = [];

  @override
  Future<List<GuardianApproval>?> fetch() async => answer;

  @override
  Future<void> approve(int learnerId, {required String consentVersion}) =>
      _decide('approve', learnerId, consentVersion);

  @override
  Future<void> withdraw(int learnerId, {required String consentVersion}) =>
      _decide('withdraw', learnerId, consentVersion);

  Future<void> _decide(String decision, int id, String version) async {
    if (error != null) {
      throw error!;
    }
    decisions.add((decision, id, version));
    answer = [
      for (final GuardianApproval approval in answer!)
        approval.learner.id == id
            ? GuardianApproval(
                learner: approval.learner,
                status: decision == 'approve' ? 'approved' : 'withdrawn',
                consent: approval.consent,
              )
            : approval,
    ];
  }
}

void main() {
  group('ApiApprovalsRepository', () {
    test('reads each child with their school and the consent', () async {
      final adapter = RecordingAdapter(
        200,
        ok([approvalJson(21, 'Asha Demo', 'pending', studentNumber: 'NE-21')]),
      );

      final approvals = await ApiApprovalsRepository(
        clientFor(adapter),
      ).fetch();

      expect(adapter.requests.single.uri.path, '/api/v2/guardian/approvals');
      final GuardianApproval asha = approvals!.single;
      expect(asha.learner.name, 'Asha Demo');
      expect(asha.learner.studentNumber, 'NE-21');
      expect(asha.learner.school!.name, 'Northcliff High');
      expect(asha.consent.version, '2026-10');
      expect(asha.requestedAt, DateTime.utc(2026, 10, 20, 8));
      expect(asha.isApproved, isFalse);
    });

    test('sends the decision with the consent version', () async {
      final adapter = RecordingAdapter(200, ok(null));
      final repository = ApiApprovalsRepository(clientFor(adapter));

      await repository.approve(21, consentVersion: '2026-10');
      await repository.withdraw(23, consentVersion: '2026-10');

      expect(adapter.requests.first.method, 'POST');
      expect(adapter.requests.first.uri.path, '/api/v2/guardian/approvals/21');
      expect(adapter.requests.first.data, {
        'decision': 'approve',
        'consent_version': '2026-10',
      });
      expect(adapter.requests.last.data, containsPair('decision', 'withdraw'));
    });

    test('answers null before the endpoint is deployed', () async {
      final repository = ApiApprovalsRepository(
        clientFor(RecordingAdapter(404, {'message': 'Not Found'})),
      );

      expect(await approvalsDeployed(repository), isFalse);
    });
  });

  group('ApprovalsCubit', () {
    test('splits the children into waiting and approved', () async {
      final cubit = ApprovalsCubit(FakeApprovals());
      await cubit.load();

      expect(cubit.state.waiting.single.learner.name, 'Asha Demo');
      expect(cubit.state.approved.single.learner.name, 'Cara Demo');
      await cubit.close();
    });

    test('approving moves the child to approved', () async {
      final repository = FakeApprovals();
      final cubit = ApprovalsCubit(repository);
      await cubit.load();

      expect(await cubit.approve(cubit.state.waiting.single), isTrue);

      expect(repository.decisions, [('approve', 21, '2026-10')]);
      expect(cubit.state.waiting, isEmpty);
      expect(cubit.state.busy, isEmpty);
      await cubit.close();
    });

    test('a refused decision says why and changes nothing', () async {
      final repository = FakeApprovals()
        ..error = ApiException('Verify your email first.', isApiMessage: true);
      final cubit = ApprovalsCubit(repository);
      await cubit.load();

      expect(await cubit.withdraw(cubit.state.approved.single), isFalse);

      expect(cubit.state.actionError!.errorMessage, 'Verify your email first.');
      expect(cubit.state.approved, hasLength(1));
      await cubit.close();
    });

    test('says when the server has no approvals yet', () async {
      final cubit = ApprovalsCubit(FakeApprovals()..answer = null);
      await cubit.load();

      expect(cubit.state.status, ApprovalsStatus.unavailable);
      await cubit.close();
    });
  });

  group('ApprovalsScreen', () {
    for (final Size size in [phone, desktop]) {
      testWidgets('lists the children waiting and approved '
          '(${size.width.toInt()} px)', (tester) async {
        await pumpScreen(
          tester,
          ApprovalsScreen.withRepository(FakeApprovals()),
          size: size,
        );

        expect(find.text('Waiting for your approval'), findsOneWidget);
        expect(find.text('Asha Demo'), findsOneWidget);
        expect(find.textContaining('Northcliff High'), findsNWidgets(2));
        expect(find.textContaining('NE-21'), findsOneWidget);
        expect(find.text('Approved'), findsOneWidget);
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('approving shows the consent text first', (tester) async {
      final repository = FakeApprovals();
      var changed = 0;
      await pumpScreen(
        tester,
        ApprovalsScreen.withRepository(repository, onChanged: () => changed++),
      );

      await tester.tap(find.text('Review and approve'));
      await tester.pumpAndSettle();
      expect(find.text('Before you approve'), findsOneWidget);
      expect(
        find.text('I agree that my child may use NavigateEd at school.'),
        findsOneWidget,
      );
      await tester.tap(find.text('I agree, approve'));
      await tester.pumpAndSettle();

      expect(repository.decisions.single.$1, 'approve');
      expect(changed, 1);
      expect(find.text('Review and approve'), findsNothing);
    });

    testWidgets('withdrawing asks first', (tester) async {
      final repository = FakeApprovals();
      await pumpScreen(tester, ApprovalsScreen.withRepository(repository));

      await tester.tap(find.text('Withdraw approval'));
      await tester.pumpAndSettle();
      expect(find.text('Withdraw approval?'), findsOneWidget);
      await tester.tap(find.text('Cancel'));
      await tester.pumpAndSettle();
      expect(repository.decisions, isEmpty);

      await tester.tap(find.text('Withdraw approval'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Withdraw approval').last);
      await tester.pumpAndSettle();
      expect(repository.decisions.single, ('withdraw', 23, '2026-10'));
    });

    testWidgets('empty and not-yet states', (tester) async {
      await pumpScreen(
        tester,
        ApprovalsScreen.withRepository(FakeApprovals()..answer = []),
      );
      expect(find.text('No children are linked to you yet.'), findsOneWidget);
    });

    testWidgets('every button names the child it is for', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pumpScreen(tester, ApprovalsScreen.withRepository(FakeApprovals()));

      expect(
        find.bySemanticsLabel('Review and approve, Asha Demo'),
        findsOneWidget,
      );
      expect(
        find.bySemanticsLabel('Withdraw approval, Cara Demo'),
        findsOneWidget,
      );
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
      semantics.dispose();
    });
  });
}
