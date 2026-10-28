import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

MeContext _context({
  int school = 1,
  int actor = 21,
  String role = 'Parent',
  bool enabled = true,
  bool readOnly = false,
  bool vendors = false,
  bool vendorReadOnly = false,
  bool tickets = false,
  bool payments = false,
  bool paymentsReadOnly = false,
  bool child = true,
}) => MeContext.fromJson({
  'user': {
    'id': actor,
    'first_name': 'Synthetic',
    'last_name': 'Parent',
    'name': 'Synthetic Parent',
    'email': 'synthetic@example.test',
    'image': null,
    'roles': [role],
  },
  'current_school': {
    'id': school,
    'name': 'Synthetic School',
    'short_name': null,
    'crest_url': null,
    'tagline': null,
    'branding': {'theme_color': null, 'secondary_color': null},
    'timezone': 'Africa/Johannesburg',
    'session_year': null,
  },
  'feature_flags': {'module_switches': true},
  'children': [
    if (child)
      {
        'id': 101,
        'user_id': 31,
        'first_name': 'Asha',
        'last_name': 'Synthetic',
        'name': 'Asha Synthetic',
        'image': null,
        'homeroom': null,
        'student_number': null,
        'school': {
          'id': school,
          'name': 'Synthetic School',
          'short_name': null,
          'crest_url': null,
        },
      },
  ],
  'modules': [
    if (enabled) {'key': 'wallet', 'read_only': readOnly},
    if (vendors) {'key': 'vendors', 'read_only': vendorReadOnly},
    if (tickets) {'key': 'ticketing', 'read_only': false},
    if (payments) {'key': 'wallet_payments', 'read_only': paymentsReadOnly},
  ],
});

class _Context implements MeContextRepository {
  MeContext current = _context(vendors: true);
  @override
  Future<MeContext?> fetchContext() async => current;
}

void main() {
  test(
    'school, holder, role, disabled module and access-mode changes invalidate a pinned context',
    () {
      final pinned = _context();
      expect(walletAccessStillCurrent(pinned, _context()), isTrue);
      for (final other in [
        _context(school: 2),
        _context(actor: 22),
        _context(role: 'Student'),
        _context(enabled: false),
        _context(readOnly: true),
        _context(vendors: true),
        null,
      ]) {
        expect(walletAccessStillCurrent(pinned, other), isFalse);
      }
    },
  );
  test('vendor pause invalidates a pinned storefront', () {
    expect(
      walletAccessStillCurrent(
        _context(vendors: true),
        _context(vendors: true, vendorReadOnly: true),
      ),
      isFalse,
    );
    expect(
      walletAccessStillCurrent(_context(vendors: true), _context()),
      isFalse,
    );
  });
  for (final change in ['school', 'account', 'logout', 'vendor pause']) {
    testWidgets(
      '$change clears the mounted private view and permanently invalidates its generation',
      (tester) async {
        final repository = _Context();
        final session = MeContextCubit(
          repository: repository,
          cache: InMemoryMeContextCache(),
        );
        await session.load();
        addTearDown(session.close);
        final pinned = session.context!;
        late bool Function() originalActive;
        await tester.pumpWidget(
          BlocProvider.value(
            value: session,
            child: MaterialApp(
              home: WalletSessionBoundary(
                pinned: pinned,
                builder: (_, active) {
                  originalActive = active;
                  return const Text('Synthetic private wallet history');
                },
              ),
            ),
          ),
        );
        expect(find.text('Synthetic private wallet history'), findsOneWidget);
        expect(originalActive(), isTrue);
        if (change == 'logout') {
          await session.clear();
        } else {
          repository.current = change == 'school'
              ? _context(school: 2)
              : change == 'vendor pause'
              ? _context(vendors: true, vendorReadOnly: true)
              : _context(actor: 22);
          await session.load();
        }
        await tester.pumpAndSettle();
        expect(find.text('Synthetic private wallet history'), findsNothing);
        expect(originalActive(), isFalse);
        await tester.pumpWidget(const SizedBox.shrink());
        expect(originalActive(), isFalse);
      },
    );
  }
  for (final change in ['school', 'guardian child', 'role', 'ticket module']) {
    testWidgets('$change erases credentials on a pushed admission route', (
      tester,
    ) async {
      SharedPreferences.setMockInitialValues({});
      final repository = _Context()
        ..current = _context(vendors: true, tickets: true);
      final session = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final pinned = session.context!;
      final server = TicketFakeServer();
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: WalletSessionBoundary(
              pinned: pinned,
              builder: (_, active) => CommerceScreen(
                repository: server.commerce.repository(),
                privateRouteBuilder: (child) => BlocProvider.value(
                  value: session,
                  child: WalletSessionBoundary(
                    pinned: pinned,
                    builder: (_, active) => child,
                  ),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.text('Admission tickets'));
      await tester.tap(find.text('Admission tickets'));
      await tester.pumpAndSettle();
      expect(find.text('Synthetic school admission'), findsWidgets);
      expect(find.byType(QrImageView), findsOneWidget);
      repository.current = _context(
        vendors: true,
        tickets: change != 'ticket module',
        school: change == 'school' ? 2 : 1,
        role: change == 'role' ? 'Teacher' : 'Parent',
        child: change != 'guardian child',
      );
      await session.load();
      await tester.pumpAndSettle();
      expect(find.byType(QrImageView), findsNothing);
      expect(find.text('Synthetic school admission'), findsNothing);
      expect(
        find.text('Your wallet access changed. Reopen Wallets.'),
        findsWidgets,
      );
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
  for (final change in [
    'school',
    'guardian child',
    'payment pause',
    'payment disabled',
  ]) {
    testWidgets('$change erases credentials on a pushed wallet payment route', (
      tester,
    ) async {
      SharedPreferences.setMockInitialValues({});
      final repository = _Context()
        ..current = _context(vendors: true, payments: true);
      final session = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final pinned = session.context!;
      final server = WalletPaymentFakeServer()..issued = true;
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: WalletSessionBoundary(
              pinned: pinned,
              builder: (_, active) => CommerceScreen(
                repository: server.commerce.repository(),
                privateRouteBuilder: (child) => BlocProvider.value(
                  value: session,
                  child: WalletSessionBoundary(
                    pinned: pinned,
                    builder: (_, active) => child,
                  ),
                ),
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.text('Wallet payments'));
      await tester.tap(find.text('Wallet payments'));
      await tester.pumpAndSettle();
      await tester.tap(find.byType(ListTile));
      await tester.pumpAndSettle();
      expect(
        find.text('Payment status: Awaiting counter confirmation'),
        findsOneWidget,
      );
      expect(find.byType(QrImageView), findsOneWidget);
      repository.current = _context(
        vendors: true,
        payments: change != 'payment disabled',
        paymentsReadOnly: change == 'payment pause',
        school: change == 'school' ? 2 : 1,
        role: change == 'role' ? 'Teacher' : 'Parent',
        child: change != 'guardian child',
      );
      await session.load();
      await tester.pumpAndSettle();
      expect(find.byType(QrImageView), findsNothing);
      expect(
        find.text('Payment status: Awaiting counter confirmation'),
        findsNothing,
      );
      expect(
        find.text('Your wallet access changed. Reopen Wallets.'),
        findsWidgets,
      );
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
}
