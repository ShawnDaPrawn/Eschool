import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Context implements MeContextRepository {
  String role = 'Parent';
  bool enabled = true;
  @override
  Future<MeContext?> fetchContext() async => MeContext.fromJson({
    ...academicContextFixture(role: role),
    'user': {
      'id': 7,
      'first_name': 'Synthetic',
      'last_name': 'Actor',
      'name': 'Synthetic Actor',
      'email': 'actor@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school_id': 1,
    'feature_flags': {'module_switches': true},
    'modules': [
      if (enabled) {'key': 'tutor', 'read_only': false},
    ],
  });
}

void main() {
  testWidgets(
    'equal coalesced profile retires old reader; role and module withdrawal erase body',
    (tester) async {
      final repo = _Context();
      final session = MeContextCubit(
        repository: repo,
        cache: InMemoryMeContextCache(),
      );
      await session.load();
      addTearDown(session.close);
      final readers = <bool Function()>[];
      await tester.pumpWidget(
        BlocProvider.value(
          value: session,
          child: MaterialApp(
            home: TutorSessionBoundary(
              pinned: session.context!,
              builder: (_, active) {
                readers.add(active);
                return const Text('Private conversation canary');
              },
            ),
          ),
        ),
      );
      await tester.pump();
      final first = readers.last;
      expect(first(), isTrue);
      await session.load();
      expect(first(), isFalse);
      await tester.pump();
      expect(readers.last(), isTrue);
      repo.role = 'Facilitator';
      await session.load();
      await tester.pump();
      expect(find.text('Private conversation canary'), findsNothing);
      expect(readers.last(), isFalse);
      repo.role = 'Parent';
      repo.enabled = false;
      await session.load();
      await tester.pump();
      expect(find.text('Private conversation canary'), findsNothing);
    },
  );
}
