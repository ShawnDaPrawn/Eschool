import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';

const cardId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
final pixels = base64Decode(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
);

class Cards implements CardRepository {
  @override
  final int schoolId = 1, accountId = 7;
  @override
  bool isActive = true;
  bool frozen = false, writable = true, denied = false, freezeOffline = false;
  int proofs = 0, uploads = 0;
  final operations = <String>[];
  Completer<SecureCardCredential>? delayed;
  SecureCard card() => SecureCard.fromJson({
    'id': cardId,
    'school_id': 1,
    'person_id': 7,
    'type': 'staff',
    'status': frozen ? 'frozen' : 'active',
    'revision': frozen ? 2 : 1,
    'issued_at': '2026-10-25T10:00:00Z',
    'frozen_at': frozen ? '2026-10-25T10:00:01Z' : null,
    'replaced_by': null,
    'has_chip': false,
  });
  SecureCardCredential proof() => SecureCardCredential.fromJson({
    'card': card().toJson(),
    'credential': 'NEQ1.test.stub',
    'expires_at': '2026-10-25T10:00:25Z',
    'key_id': cardId,
    'public_key': 'synthetic-public',
  });
  @override
  Future<SecureCardConfiguration> configuration() async {
    if (denied) throw ApiException('revoked', statusCode: 403);
    return SecureCardConfiguration(
      canManage: false,
      canManageReaders: false,
      writable: writable,
      types: ['staff'],
    );
  }

  @override
  Future<SecureCardPage> mine({int page = 1}) async =>
      SecureCardPage(items: [card()], nextPage: null);
  @override
  Future<SecureCardCredential> credential(String id) async {
    proofs++;
    return delayed?.future ?? proof();
  }

  @override
  Future<SecureCard> freeze(String id, String operation, String reason) async {
    operations.add(operation);
    if (freezeOffline) throw ApiException('offline', isTransportFailure: true);
    frozen = true;
    return card();
  }

  @override
  Future<Uint8List> portrait(String id) async => pixels;
  @override
  Future<SecureCardPortraitReceipt> upload(
    int person,
    String type,
    String operation,
    Uint8List bytes,
  ) async {
    uploads++;
    return SecureCardPortraitReceipt(
      personId: person,
      portraitId: cardId,
      revision: 1,
    );
  }
}

Future<void> mount(
  WidgetTester tester,
  Cards repo, {
  Duration Function()? now,
  Future<Uint8List?> Function()? select,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: SchoolCardsScreen(
        repository: repo,
        people: const [CardPersonChoice(7, 'Synthetic staff', 'staff')],
        schoolName: 'Synthetic school',
        monotonicNow: now,
        selectPhoto: select,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, String text) async {
  final found = find.text(text).first;
  FocusManager.instance.primaryFocus?.unfocus();
  await Scrollable.ensureVisible(tester.element(found), alignment: 0.5);
  await tester.pumpAndSettle();
  await tester.tap(found.hitTestable());
  await tester.pump();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('private digital card and freeze fit $width', (tester) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = Cards();
      await mount(tester, repo);
      await tap(tester, 'Show secure code');
      await tester.pump();
      expect(find.byType(QrImageView), findsOneWidget);
      await tap(tester, 'Freeze card');
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Synthetic lost card');
      await tester.pump();
      await tester.tap(find.widgetWithText(FilledButton, 'Freeze card'));
      await tester.pumpAndSettle();
      expect(repo.frozen, isTrue);
      expect(find.byType(QrImageView), findsNothing);
      expect(
        find.text('Contact your school to restore or replace this card.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('background erases pixels, proof and an open reason dialog', (
    tester,
  ) async {
    final repo = Cards();
    await mount(tester, repo);
    await tap(tester, 'Show secure code');
    await tester.pump();
    await tap(tester, 'Freeze card');
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), 'Private reason');
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.byType(QrImageView), findsNothing);
    expect(find.text('Private reason'), findsNothing);
    expect(find.text('Synthetic staff'), findsNothing);
    repo.denied = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pump();
    await tester.pumpAndSettle();
    expect(
      find.text('Your card access changed. Reopen this screen.'),
      findsWidgets,
    );
    expect(find.text('Synthetic staff'), findsNothing);
  });
  testWidgets('an old proof response cannot restore a background card', (
    tester,
  ) async {
    final repo = Cards()..delayed = Completer<SecureCardCredential>();
    await mount(tester, repo);
    await tap(tester, 'Show secure code');
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    repo.delayed!.complete(repo.proof());
    await tester.pump();
    expect(find.byType(QrImageView), findsNothing);
    expect(find.text('Synthetic staff'), findsNothing);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pump();
    await tester.pumpAndSettle();
  });
  testWidgets('old proof expires while renewal is pending', (tester) async {
    var elapsed = Duration.zero;
    final repo = Cards();
    await mount(tester, repo, now: () => elapsed);
    await tap(tester, 'Show secure code');
    await tester.pump();
    expect(find.byType(QrImageView), findsOneWidget);
    repo.delayed = Completer<SecureCardCredential>();
    elapsed = const Duration(seconds: 20);
    await tester.pump(const Duration(seconds: 1));
    elapsed = const Duration(seconds: 26);
    await tester.pump(const Duration(seconds: 1));
    expect(find.byType(QrImageView), findsNothing);
    repo.delayed!.complete(repo.proof());
    await tester.pump();
    await tester.pumpWidget(const SizedBox());
  });
  testWidgets(
    'freeze transport retry retains the operation and hides the proof',
    (tester) async {
      final repo = Cards()..freezeOffline = true;
      await mount(tester, repo);
      await tap(tester, 'Show secure code');
      await tester.pump();
      await tap(tester, 'Freeze card');
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'Lost');
      await tester.pump();
      await tester.tap(find.widgetWithText(FilledButton, 'Freeze card'));
      await tester.pumpAndSettle();
      expect(find.byType(QrImageView), findsNothing);
      expect(find.text('Retry card freeze'), findsOneWidget);
      repo.freezeOffline = false;
      await tap(tester, 'Retry card freeze');
      await tester.pumpAndSettle();
      expect(repo.operations.length, 2);
      expect(repo.operations.first, repo.operations.last);
    },
  );
  testWidgets(
    'read-only retains card reading and freeze but disables fresh code',
    (tester) async {
      final repo = Cards()..writable = false;
      await mount(tester, repo);
      expect(find.text('Synthetic staff'), findsWidgets);
      final button = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Show secure code'),
      );
      expect(button.onPressed, isNull);
      expect(
        tester
            .widget<OutlinedButton>(
              find.widgetWithText(OutlinedButton, 'Freeze card'),
            )
            .onPressed,
        isNotNull,
      );
    },
  );
}
