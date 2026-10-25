import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _Repository implements MeContextRepository {
  String role = 'Parent';
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
      {'key': 'cards', 'read_only': false},
    ],
  });
}

void main() {
  testWidgets(
    'coalesced refresh invalidates old card authority even with identical payload',
    (tester) async {
      final repository = _Repository();
      final watched = MeContextCubit(
        repository: repository,
        cache: InMemoryMeContextCache(),
      );
      await watched.load();
      addTearDown(watched.close);
      final active = <bool Function()>[];
      await tester.pumpWidget(
        BlocProvider.value(
          value: watched,
          child: MaterialApp(
            home: CardSessionBoundary(
              pinned: watched.context!,
              builder: (_, scope) {
                active.add(scope);
                return const Text('Private card body');
              },
            ),
          ),
        ),
      );
      await tester.pump();
      final original = active.last;
      expect(original(), isTrue);
      await watched.load(); // Loading and Loaded arrive before the next frame.
      expect(original(), isFalse);
      await tester.pump();
      expect(active.last(), isTrue);
      expect(active.length, 2);
      repository.role = 'Facilitator';
      await watched.load();
      await tester.pump();
      expect(active.last(), isFalse);
      expect(find.text('Private card body'), findsNothing);
    },
  );
}
