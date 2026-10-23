import 'dart:async';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

Map<String, Object?> handleJson({String? handle}) => {
  'handle': handle,
  'proposal': null,
  'can_change_after': null,
};

class _Handles implements HandlesRepository {
  HandleState? value = HandleState.fromJson(handleJson());
  List<GuardianHandleProposal> requests = [];
  final List<String> choices = [];
  final List<(int, bool)> decisions = [];
  Object? chooseError;
  Object? requestsError;
  Completer<void>? deciding;

  @override
  Future<HandleState?> fetch() async => value;
  @override
  Future<HandleState> choose(String handle) async {
    if (chooseError != null) throw chooseError!;
    choices.add(handle);
    value = HandleState.fromJson({
      ...handleJson(),
      'proposal': {
        'id': 1,
        'handle': handle,
        'status': 'pending',
        'expires_at': '2026-11-01T12:00:00Z',
      },
    });
    return value!;
  }

  @override
  Future<List<GuardianHandleProposal>> proposals() async {
    if (requestsError != null) throw requestsError!;
    return requests;
  }

  @override
  Future<void> decide(int id, {required bool approve}) async {
    decisions.add((id, approve));
    if (deciding != null) await deciding!.future;
    requests = requests.where((request) => request.id != id).toList();
  }
}

void main() {
  test(
    'repository decodes the generated contract and sends the chosen handle',
    () async {
      final adapter = RecordingAdapter(200, ok(handleJson(handle: 'orbit')));
      final repository = ApiHandlesRepository(clientFor(adapter));
      expect((await repository.fetch())!.handle, 'orbit');
      await repository.choose('orbit');
      expect(adapter.requests.last.method, 'PUT');
      expect(adapter.requests.last.data, {'handle': 'orbit'});
      expect(adapter.requests.last.uri.path, '/api/v2/me/handle');
    },
  );

  test('old-server endpoint remains unavailable', () async {
    expect(
      await handlesDeployed(
        ApiHandlesRepository(
          clientFor(RecordingAdapter(404, {'message': 'Not found'})),
        ),
      ),
      isFalse,
    );
  });

  testWidgets('learner proposes a handle and sees the guardian step', (
    tester,
  ) async {
    final repository = _Handles();
    await pumpScreen(
      tester,
      HandlesScreen.withRepository(repository, guardian: false),
    );
    await tester.enterText(find.byType(TextFormField), 'orbit');
    await tester.tap(find.text('Choose handle'));
    await tester.pumpAndSettle();
    expect(repository.choices, ['orbit']);
    expect(repository.value!.handle, isNull);
    expect(
      find.textContaining('Waiting for guardian approval'),
      findsOneWidget,
    );
  });

  for (final approve in [true, false]) {
    testWidgets(
      'guardian ${approve ? 'approves' : 'rejects'} once while a decision is pending',
      (tester) async {
        final repository = _Handles()
          ..requests = [
            GuardianHandleProposal.fromJson({
              'id': 21,
              'handle': 'orbit',
              'expires_at': '2026-11-01T12:00:00Z',
              'learner': {'id': 5, 'name': 'Asha'},
            }),
          ]
          ..deciding = Completer<void>();
        await pumpScreen(
          tester,
          HandlesScreen.withRepository(repository, guardian: true),
        );
        await tester.tap(find.text(approve ? 'Approve' : 'Reject'));
        await tester.pump();
        expect(repository.decisions, [(21, approve)]);
        final button = find.widgetWithText(
          approve ? FilledButton : TextButton,
          approve ? 'Approve' : 'Reject',
        );
        expect((tester.widget(button) as ButtonStyleButton).onPressed, isNull);
        repository.deciding!.complete();
        await tester.pumpAndSettle();
        expect(find.text('No pending requests'), findsOneWidget);
      },
    );
  }

  testWidgets(
    'a failed choice preserves the typed handle and explains the failure',
    (tester) async {
      final repository = _Handles()
        ..chooseError = ApiException('Already in use', isApiMessage: true);
      await pumpScreen(
        tester,
        HandlesScreen.withRepository(repository, guardian: false),
      );
      await tester.enterText(find.byType(TextFormField), 'orbit');
      await tester.tap(find.text('Choose handle'));
      await tester.pumpAndSettle();
      expect(find.text('Already in use'), findsOneWidget);
      expect(
        tester
            .widget<TextFormField>(find.byType(TextFormField))
            .controller!
            .text,
        'orbit',
      );
    },
  );

  testWidgets('an unverified guardian can still manage their own handle', (
    tester,
  ) async {
    final repository = _Handles()
      ..requestsError = ApiException(
        'Verify your email to review handle requests.',
        isApiMessage: true,
      );
    await pumpScreen(
      tester,
      HandlesScreen.withRepository(repository, guardian: true),
    );
    expect(
      find.text('Verify your email to review handle requests.'),
      findsOneWidget,
    );
    expect(find.text('Choose handle'), findsOneWidget);
  });
}
