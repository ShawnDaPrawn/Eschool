import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/marks/mark_release_dialog.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'mark_widgets_test.dart' show pumpMarks;
import 'marks_test.dart';

class ReleaseRepository extends FakeMarks {
  final reads = <int>[];
  final pending = <int, Completer<AcademicReleasedData>>{};
  final commits = <AcademicReleaseCommitWrite>[];
  bool conflict = false;
  @override
  Future<AcademicReleasePeriodsData> periods(int learner) async =>
      AcademicReleasePeriodsData.fromJson({
        ...periodsFixture(),
        'learner_id': learner,
      });
  @override
  Future<AcademicReleasedData> released(int learner, int year, int? term) {
    reads.add(learner);
    return (pending[learner] ??= Completer<AcademicReleasedData>()).future;
  }

  @override
  Future<AcademicReleasePreviewData> releasePreview(
    int group,
    AcademicReleaseWrite input,
  ) async => AcademicReleasePreviewData.fromJson(releasePreviewFixture());
  @override
  Future<AcademicReleaseData> release(
    int group,
    AcademicReleaseCommitWrite input,
  ) async {
    commits.add(input);
    if (conflict) {
      throw ApiException(
        'The preview changed',
        statusCode: 409,
        isApiMessage: true,
      );
    }
    return AcademicReleaseData.fromJson(releaseFixture());
  }
}

void main() {
  testWidgets('a late child response never appears in the new child screen', (
    tester,
  ) async {
    final repository = ReleaseRepository();
    Widget child(int id) => ReleasedMarksScreen(
      repository: repository,
      learner: id,
      learnerName: 'Child $id',
    );
    final selected = ValueNotifier<int>(11);
    addTearDown(selected.dispose);
    await pumpMarks(
      tester,
      repository,
      const Size(390, 844),
      settle: false,
      screen: ValueListenableBuilder<int>(
        valueListenable: selected,
        builder: (_, id, _) => child(id),
      ),
    );
    await tester.pump();
    final oldResponse = repository.pending[11]!;
    selected.value = 12;
    await tester.pump();
    await tester.pump();
    expect(find.text('Mathematics'), findsNothing);
    oldResponse.complete(AcademicReleasedData.fromJson(releasedFixture(11)));
    repository.pending[12]!.complete(
      AcademicReleasedData.fromJson({
        ...releasedFixture(12),
        'results': <Object>[],
      }),
    );
    await tester.pumpAndSettle();
    expect(find.text('Mathematics'), findsNothing);
    expect(find.text('No released results are available yet.'), findsOneWidget);
    expect(repository.reads, [11, 12]);
    expect(tester.takeException(), isNull);
  });

  testWidgets(
    'release requires preview and a conflict requires a fresh preview',
    (tester) async {
      final repository = ReleaseRepository()..conflict = true;
      await pumpMarks(
        tester,
        repository,
        const Size(390, 844),
        screen: Builder(
          builder: (context) => Scaffold(
            body: TextButton(
              onPressed: () => showDialog<bool>(
                context: context,
                builder: (_) => MarkReleaseDialog(
                  repository: repository,
                  group: 1,
                  term: 1,
                  learners: const {11: 'Learner 11'},
                ),
              ),
              child: const Text('Open release'),
            ),
          ),
        ),
      );
      await tester.tap(find.text('Open release'));
      await tester.pumpAndSettle();
      Finder release() =>
          find.widgetWithText(FilledButton, 'Release selected results');
      expect(tester.widget<FilledButton>(release()).onPressed, isNull);
      await tester.tap(find.text('Preview release'));
      await tester.pumpAndSettle();
      expect(find.text('75.05%'), findsOneWidget);
      await tester.tap(release());
      await tester.pumpAndSettle();
      expect(repository.commits.single.fingerprint, 'preview-fingerprint');
      expect(find.text('The preview changed'), findsOneWidget);
      expect(tester.widget<FilledButton>(release()).onPressed, isNull);
      repository.conflict = false;
      await tester.tap(find.text('Preview release'));
      await tester.pumpAndSettle();
      await tester.tap(release());
      await tester.pumpAndSettle();
      expect(repository.commits.length, 2);
      expect(find.byType(MarkReleaseDialog), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
}
