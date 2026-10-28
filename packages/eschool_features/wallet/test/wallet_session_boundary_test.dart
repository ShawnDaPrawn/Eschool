import 'package:eschool_wallet/eschool_wallet.dart';
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
  'modules': [
    if (enabled) {'key': 'wallet', 'read_only': readOnly},
    if (vendors) {'key': 'vendors', 'read_only': vendorReadOnly},
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
}
