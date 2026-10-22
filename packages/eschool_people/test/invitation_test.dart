import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

Map<String, Object?> invitationJson({bool needsPassword = true}) => {
  'school': school(1, 'Northcliff High', shortName: 'NHS'),
  'email': 'lerato@family.test',
  'learners': [
    {'id': 21, 'name': 'Asha Demo'},
    {'id': 23, 'name': 'Cara Demo'},
  ],
  'expires_at': '2026-10-29T08:00:00Z',
  'needs_password': needsPassword,
};

const Map<String, Object?> signIn = {
  'error': false,
  'message': 'User logged-in!',
  'data': {'id': 7, 'first_name': 'Lerato', 'children': <Object>[]},
  'token': 'new-parent-token',
  'code': 200,
};

class FakeInvitations implements InvitationRepository {
  GuardianInvitation? answer = GuardianInvitation.fromJson(invitationJson());
  Object? acceptError;
  final List<(String, String?)> accepted = [];

  @override
  Future<GuardianInvitation?> fetch(String token) async => answer;

  @override
  Future<Map<String, dynamic>> accept(String token, {String? password}) async {
    if (acceptError != null) {
      throw acceptError!;
    }
    accepted.add((token, password));
    return signIn;
  }
}

void main() {
  group('ApiInvitationRepository', () {
    test('reads the invitation without a token', () async {
      final adapter = RecordingAdapter(200, ok(invitationJson()));

      final invitation = await ApiInvitationRepository(
        clientFor(adapter),
      ).fetch('abc 123');

      final request = adapter.requests.single;
      expect(request.uri.path, '/api/v2/guardian/invitations/abc%20123');
      expect(request.headers.containsKey('Authorization'), isFalse);
      expect(invitation!.school!.name, 'Northcliff High');
      expect(invitation.learners.map((l) => l.name), [
        'Asha Demo',
        'Cara Demo',
      ]);
      expect(invitation.needsPassword, isTrue);
      expect(invitation.expiresAt, DateTime.utc(2026, 10, 29, 8));
    });

    test('an unknown, expired or used link reads as none', () async {
      final repository = ApiInvitationRepository(
        clientFor(RecordingAdapter(404, {'message': 'Not Found'})),
      );

      expect(await repository.fetch('old'), isNull);
    });

    test('accepting sends the password and the device, and signs in', () async {
      final adapter = RecordingAdapter(200, signIn);

      final answer = await ApiInvitationRepository(
        clientFor(adapter),
        deviceFields: () async => {
          'fcm_id': 'device',
          'device_type': 'android',
        },
      ).accept('abc', password: 'Secret-pass-1');

      final request = adapter.requests.single;
      expect(request.method, 'POST');
      expect(request.uri.path, '/api/v2/guardian/invitations/abc/accept');
      expect(request.data, {
        'password': 'Secret-pass-1',
        'fcm_id': 'device',
        'device_type': 'android',
      });
      expect(answer['token'], 'new-parent-token');
    });
  });

  group('InvitationScreen', () {
    late FakeInvitations repository;
    late List<Map<String, dynamic>> signedIn;

    Widget screen() => InvitationScreen.withRepository(
      repository,
      token: 'abc',
      onAccepted: (_, answer) => signedIn.add(answer),
    );

    setUp(() {
      repository = FakeInvitations();
      signedIn = [];
    });

    for (final Size size in [phone, desktop]) {
      testWidgets('shows the school and the children '
          '(${size.width.toInt()} px)', (tester) async {
        await pumpScreen(tester, screen(), size: size);

        expect(find.text('Northcliff High'), findsOneWidget);
        expect(find.text('Asha Demo'), findsOneWidget);
        expect(find.text('Cara Demo'), findsOneWidget);
        expect(find.textContaining('lerato@family.test'), findsOneWidget);
        expect(find.textContaining('29 Oct 2026'), findsOneWidget);
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('a new parent sets a password, then is signed in', (
      tester,
    ) async {
      await pumpScreen(tester, screen());

      await tester.tap(find.text('Accept and sign in'));
      await tester.pumpAndSettle();
      expect(find.text('Use at least 8 characters.'), findsOneWidget);

      await tester.enterText(find.byType(TextFormField).at(0), 'Secret-pass-1');
      await tester.enterText(find.byType(TextFormField).at(1), 'Secret-pass-2');
      await tester.tap(find.text('Accept and sign in'));
      await tester.pumpAndSettle();
      expect(find.text('The passwords do not match.'), findsOneWidget);

      await tester.enterText(find.byType(TextFormField).at(1), 'Secret-pass-1');
      await tester.tap(find.text('Accept and sign in'));
      // The app moves on from here; the button spins until it does.
      await tester.pump();
      await tester.pump();

      expect(repository.accepted, [('abc', 'Secret-pass-1')]);
      expect(signedIn.single['token'], 'new-parent-token');
    });

    testWidgets('a parent with an account just accepts', (tester) async {
      repository.answer = GuardianInvitation.fromJson(
        invitationJson(needsPassword: false),
      );
      await pumpScreen(tester, screen());

      expect(find.byType(TextFormField), findsNothing);
      await tester.tap(find.text('Accept and sign in'));
      await tester.pump();
      await tester.pump();

      expect(repository.accepted, [('abc', null)]);
    });

    testWidgets('a refused acceptance says why and keeps the form', (
      tester,
    ) async {
      repository
        ..answer = GuardianInvitation.fromJson(
          invitationJson(needsPassword: false),
        )
        ..acceptError = ApiException(
          'This invitation was already used.',
          isApiMessage: true,
        );
      await pumpScreen(tester, screen());

      await tester.tap(find.text('Accept and sign in'));
      await tester.pumpAndSettle();

      expect(find.text('This invitation was already used.'), findsOneWidget);
      expect(find.text('Accept and sign in'), findsOneWidget);
      expect(signedIn, isEmpty);
    });

    testWidgets('an old link says to ask for a new one', (tester) async {
      repository.answer = null;
      await pumpScreen(tester, screen());

      expect(
        find.textContaining('expired or was already used'),
        findsOneWidget,
      );
    });

    testWidgets('every control has a name', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pumpScreen(tester, screen());

      expect(find.bySemanticsLabel('Create a password'), findsOneWidget);
      expect(find.bySemanticsLabel('Type the password again'), findsOneWidget);
      expect(find.byTooltip('Show password'), findsNWidgets(1));
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
      semantics.dispose();
    });
  });
}
