import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';
import 'content_context_test.dart' show ContextRepository;
import 'content_widgets_test.dart' show frame;
import 'content_offline_test.dart' show OfflineContentRepository, fresh;

void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets('offline reader expires while visible at $width', (
      tester,
    ) async {
      final repo = OfflineContentRepository();
      var clock = repo.time;
      final cache = ContentReaderCache(
        repo,
        store: () async => MemoryContentStore(),
        clock: () => clock,
      );
      addTearDown(cache.close);
      final reader = ContentReader.fromJson({
        ...fresh(clock).toJson(),
        'offline_until': clock
            .add(const Duration(seconds: 2))
            .toIso8601String(),
      });
      await cache.save(reader, requestedAt: clock);
      repo.error = ApiException('offline', isTransportFailure: true);
      await frame(
        tester,
        LearningContentReaderScreen(
          repository: repo,
          learner: 11,
          version: 11,
          title: 'Learning content',
          cache: cache,
        ),
        width,
      );
      expect(find.text('One half equals two quarters.'), findsOneWidget);
      expect(repo.reads, 0);
      expect(
        find.text(
          'Saved offline copy. Connect to check current access. This copy expires within one day.',
        ),
        findsOneWidget,
      );
      clock = clock.add(const Duration(seconds: 3));
      await tester.pump(const Duration(seconds: 3));
      await tester.pumpAndSettle();
      expect(find.text('One half equals two quarters.'), findsNothing);
      expect(find.text('Fractions'), findsNothing);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    });
  }
  testWidgets(
    'global session guard purges after school/account replacement even with library closed',
    (tester) async {
      final schools = SchoolSelectionCubit(
        store: InMemorySelectionStore(schoolId: 1),
      );
      final repo = ContextRepository();
      final me = MeContextCubit(
        repository: repo,
        cache: InMemoryMeContextCache(),
      );
      await me.load();
      addTearDown(schools.close);
      addTearDown(me.close);
      var purges = 0;
      await tester.pumpWidget(
        MultiBlocProvider(
          providers: [
            BlocProvider.value(value: schools),
            BlocProvider.value(value: me),
          ],
          child: MaterialApp(
            home: ContentCacheSessionGuard(
              purge: () async {
                purges++;
              },
              child: const Scaffold(body: Text('Home')),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(purges, 0);
      await schools.follow(2);
      await tester.pumpAndSettle();
      expect(purges, 1);
      repo.account = 13;
      await me.load();
      await tester.pumpAndSettle();
      expect(purges, 2);
      await me.clear();
      await tester.pumpAndSettle();
      expect(purges, 3);
    },
  );
}
