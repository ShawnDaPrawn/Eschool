import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Repository implements MeContextRepository {
  MeContext? answer;
  @override
  Future<MeContext?> fetchContext() async => answer;
}

MeContext _context({bool chat = true, bool readOnly = false}) =>
    MeContext.fromJson({
      'feature_flags': {'module_switches': true},
      'modules': [
        if (chat) {'key': 'chat', 'read_only': readOnly},
      ],
    });

void main() {
  test(
    'old servers keep their navigation, configured servers govern writes',
    () {
      expect(allowsSchoolModule(null, 'chat'), isTrue);
      expect(
        allowsSchoolModule(MeContext.fromJson({'modules': []}), 'chat'),
        isTrue,
      );
      expect(allowsSchoolModule(_context(chat: false), 'chat'), isFalse);
      expect(allowsSchoolModule(_context(readOnly: true), 'chat'), isTrue);
      expect(
        allowsSchoolModule(_context(readOnly: true), 'chat', write: true),
        isFalse,
      );
    },
  );

  testWidgets(
    'refresh removes a navigation item and prevents building a disabled page',
    (tester) async {
      final repository = _Repository()..answer = _context();
      final cubit = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      addTearDown(cubit.close);
      await cubit.load();
      var builds = 0;
      await tester.pumpWidget(
        BlocProvider.value(
          value: cubit,
          child: MaterialApp(
            home: Column(
              children: [
                const ModuleVisibility(
                  module: 'chat',
                  child: Text('Chat menu'),
                ),
                Expanded(
                  child: ModuleAccessPage(
                    module: 'chat',
                    builder: (_) {
                      builds++;
                      return const Text('Chat content');
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      );
      expect(find.text('Chat menu'), findsOneWidget);
      expect(find.text('Chat content'), findsOneWidget);
      repository.answer = _context(chat: false);
      await cubit.load();
      await tester.pumpAndSettle();
      expect(find.text('Chat menu'), findsNothing);
      expect(find.text('Chat content'), findsNothing);
      final stoppedAt = builds;
      await tester.pump();
      expect(builds, stoppedAt);
    },
  );

  testWidgets('a direct route refuses writes to a read-only module', (
    tester,
  ) async {
    final repository = _Repository()..answer = _context(readOnly: true);
    final cubit = MeContextCubit(
      repository: repository,
      cache: InMemoryMeContextCache(),
    );
    addTearDown(cubit.close);
    await cubit.load();
    await tester.pumpWidget(
      BlocProvider.value(
        value: cubit,
        child: MaterialApp(
          onGenerateRoute: (_) => guardModuleRoute(
            AdaptivePageRoute(builder: (_) => const Text('write')),
            'chat',
            write: true,
          ),
        ),
      ),
    );
    expect(find.text('write'), findsNothing);
    expect(
      find.text('This feature is read-only at your school.'),
      findsOneWidget,
    );
  });
}
