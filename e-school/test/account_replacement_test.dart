import 'dart:async';

import 'package:eschool/app/sessionWatcher.dart';
import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

class _MemoryAuth extends AuthRepository {
  @override
  bool getIsLogIn() => false;
  @override
  Future<void> setJwtToken(String value) async {}
  @override
  Future<void> setIsLogIn(bool value) async {}
  @override
  Future<void> setIsStudentLogIn(bool value) async {}
  @override
  Future<void> setStudentDetails(Student value) async {}
  @override
  Future<void> setParentDetails(Parent value) async {}
}

class _DeferredContext implements MeContextRepository {
  final answers = List.generate(2, (_) => Completer<MeContext?>());
  int calls = 0;
  @override
  Future<MeContext?> fetchContext() => answers[calls++].future;
}

Map<String, dynamic> contextFor(int id) => {
  'user': {
    'id': id,
    'first_name': 'Parent',
    'last_name': '$id',
    'name': 'Parent $id',
    'email': 'parent$id@example.test',
    'image': null,
    'roles': ['Parent'],
  },
  'children': [
    {
      'id': id + 100,
      'user_id': id + 200,
      'first_name': 'Child',
      'last_name': '$id',
      'name': 'Child $id',
      'image': null,
      'school': {
        'id': 0,
        'name': 'Test',
        'short_name': null,
        'crest_url': null,
      },
    },
  ],
};

void main() {
  testWidgets(
    'accepting an invitation while signed in discards the prior parent and children',
    (tester) async {
      NotificationUtility.enabled = false;
      final auth = AuthCubit(_MemoryAuth());
      final store = InMemorySelectionStore();
      final children = ChildSelectionCubit(store: store);
      final schools = SchoolSelectionCubit(store: store);
      final source = _DeferredContext();
      final cache = InMemoryMeContextCache();
      final me = MeContextCubit(
        repository: source,
        cache: cache,
        contextIdentity: () => auth.state is Authenticated
            ? (auth.state as Authenticated).sessionGeneration
            : null,
      );
      final configuration = AppConfigurationCubit(SystemRepository());
      addTearDown(() async {
        await auth.close();
        await children.close();
        await schools.close();
        await me.close();
        await configuration.close();
        NotificationUtility.enabled = true;
      });
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [
            BlocProvider.value(value: auth),
            BlocProvider.value(value: children),
            BlocProvider.value(value: schools),
            BlocProvider.value(value: me),
            BlocProvider.value(value: configuration),
          ],
          child: const SessionWatcher(child: SizedBox()),
        ),
      );
      void signIn(int id) => auth.authenticateUser(
        jwtToken: '',
        isStudent: false,
        parent: Parent.fromJson({
          'id': id,
          'children': [
            {'id': id + 100, 'first_name': 'Child $id'},
          ],
        }),
        student: Student.fromJson({}),
      );
      signIn(1);
      await tester.pump();
      source.answers[0].complete(MeContext.fromJson(contextFor(1)));
      await tester.pumpAndSettle();
      expect(me.context?.user?.id, 1);
      expect(children.state.children.single.id, 101);

      // Invitation acceptance uses this exact AuthCubit entry point, including
      // cookie mode's empty token. Both states are Authenticated.
      signIn(2);
      await tester.pumpAndSettle();
      expect(source.calls, 2);
      expect(me.context, isNull);
      expect(children.state.children.map((child) => child.id), [102]);
      expect(cache.read(), isNull);
      source.answers[1].complete(MeContext.fromJson(contextFor(2)));
      await tester.pumpAndSettle();
      expect(me.context?.user?.id, 2);
      await tester.pumpWidget(const SizedBox());
    },
  );
}
