import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

const operation = '00000000-0000-4000-8000-000000001701';
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));
  test('ticket reads bind current holder, learner, school and actor', () async {
    final server = TicketFakeServer(named: true);
    final repository = server.repository();
    final rows = await repository.tickets();
    expect(rows.items.single.namedLearnerName, 'Asha Synthetic');
    expect(rows.items.single.unitPriceCents, 1250);
    for (final request in server.api.requests) {
      expect(request.headers['X-School-Id'].toString(), '1');
      expect(request.headers['X-Account-Id'].toString(), '21');
    }
    server.forgedOwner = 999;
    await expectLater(repository.tickets(), throwsA(isA<ApiException>()));
  });
  test(
    'transfer returns a safe original acknowledgement and moves no wallet money',
    () async {
      final server = TicketFakeServer();
      final repository = server.repository();
      final balances = Map<int, int>.from(server.commerce.balances);
      await repository.tickets();
      final preview = await repository.preview(1701, 'SYNTHETIC-RECEIVE-99');
      expect(preview.recipientName, 'Gina Synthetic');
      final ack = await repository.transfer(
        preview,
        'SYNTHETIC-RECEIVE-99',
        operation,
      );
      expect(ack.status, AdmissionTransferAckStatusEnum.transferred);
      expect(server.writes, 1);
      expect(server.commerce.balances, balances);
      expect((await repository.tickets()).items, isEmpty);
      final request = server.api.requests.singleWhere(
        (q) => q.uri.path.endsWith('/1701/transfer'),
      );
      expect(request.headers['Idempotency-Key'], operation);
      expect((request.data as Map)['revision'], 0);
    },
  );
  test(
    'lost transfer response retains only original UUID and recovers without another transfer',
    () async {
      final server = TicketFakeServer()..loseReply = true;
      final first = TicketCubit(server.repository());
      await first.load();
      await first.preview(1701, 'SYNTHETIC-RECEIVE-99');
      expect(await first.transfer(), isFalse);
      expect(first.hasPending, isTrue);
      expect(server.writes, 1);
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getKeys().single, startsWith('wallet_pending_v1_1_21_'));
      expect(prefs.getKeys().join(), isNot(contains('RECEIVE')));
      expect(prefs.getKeys().join(), isNot(contains('Gina')));
      await first.close();
      final restored = TicketCubit(server.repository());
      await restored.load();
      expect(restored.hasPending, isTrue);
      expect(await restored.recover(), isTrue);
      expect(restored.hasPending, isFalse);
      expect(restored.state.page!.items, isEmpty);
      expect(server.writes, 1);
      await restored.close();
    },
  );
  test(
    'revocation and background suspension erase admission QR and receiving codes',
    () async {
      final server = TicketFakeServer();
      final cubit = TicketCubit(server.repository());
      await cubit.load();
      await cubit.receivingCode();
      expect(cubit.state.receiving, isNotNull);
      cubit.suspend();
      expect(cubit.state.page, isNull);
      expect(cubit.state.receiving, isNull);
      expect(cubit.state.preview, isNull);
      await cubit.load();
      server.allowed = false;
      expect(await cubit.load(), isFalse);
      expect(cubit.state.accessLost, isTrue);
      expect(cubit.state.page, isNull);
      await cubit.close();
    },
  );
  for (final profile in [(21, false), (11, true), (31, false), (41, false)]) {
    testWidgets('profile ${profile.$1} reads its private admission wallet', (
      tester,
    ) async {
      final server = TicketFakeServer(actor: profile.$1, learner: profile.$2);
      await tester.pumpWidget(
        MaterialApp(home: TicketScreen(repository: server.repository())),
      );
      await tester.pumpAndSettle();
      expect(find.text('Synthetic school admission'), findsWidgets);
      expect(find.byType(QrImageView), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
}
