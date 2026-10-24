import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:eschool_academics/testing.dart';
export 'package:eschool_academics/testing.dart';

class FakeMarks implements MarksRepository {
  AcademicMatrixData current = markMatrix();
  Future<AcademicMatrixData> Function(int group)? loader;
  ApiException? captureError;
  List<AcademicMarkWrite> writes = [];
  List<Completer<AcademicPreviewData>> previews = [];
  bool delayPreviews = false;
  @override
  Future<AcademicMatrixData> matrix(
    int group,
    int term, {
    int page = 1,
  }) async => loader == null ? current : loader!(group);
  @override
  Future<AcademicCaptureData> capture(
    int group,
    int task,
    AcademicMarkWrite input, {
    bool correction = false,
  }) async {
    writes.add(input);
    if (captureError != null) throw captureError!;
    return AcademicCaptureData(marks: [], recomputePending: true);
  }

  @override
  Future<AcademicPreviewData> preview(
    int group,
    int task,
    AcademicMarkWrite input,
  ) async {
    if (delayPreviews) {
      final pending = Completer<AcademicPreviewData>();
      previews.add(pending);
      return pending.future;
    }
    return previewResult(7500);
  }

  AcademicPreviewData previewResult(int bp) => AcademicPreviewData(
    previews: [
      AcademicLearnerPreview(
        learnerId: 11,
        result: AcademicMarkResult.fromJson(result(bp)),
      ),
    ],
  );
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

void main() {
  test(
    'exact entry and bounded rectangular paste reject unsupported values',
    () {
      expect(markUnits('7.50', 750), 750);
      expect(markUnits('7,50', 750), 750);
      expect(markUnits('0', 750), 0);
      expect(markUnits('', 750), isNull);
      for (final invalid in ['7.51', '-1', '1e2', 'NaN', '1.001', '=SUM(A1)']) {
        expect(() => markUnits(invalid, 750), throwsFormatException);
      }
      expect(markPaste('5.00\t10.00\r\n6.00\t11.00\r\n'), [
        ['5.00', '10.00'],
        ['6.00', '11.00'],
      ]);
      expect(() => markPaste('5\t10\n6'), throwsFormatException);
    },
  );

  late FakeMarks repository;
  late MarkGridCubit cubit;
  setUp(() {
    repository = FakeMarks();
    cubit = MarkGridCubit(repository);
  });
  tearDown(() async {
    await cubit.close();
  });

  test(
    'pasted question scores retain exact text and original optimistic revisions',
    () async {
      await cubit.load(1, 1);
      cubit.paste('5.01\t10.00\n6.25\t11.50', row: 0, column: 0);
      expect(cubit.state.drafts[11]!.values[7], '5.01');
      expect(await cubit.save(), isTrue);
      final input = repository.writes.single;
      expect(input.rows.first.expectedRevision, 0);
      expect(input.rows.first.questionMarks.first.mark, '5.01');
      expect(input.rows.last.questionMarks.last.mark, '11.50');
      expect(cubit.state.dirty, isFalse);
    },
  );

  test(
    'one invalid cell or read-only row rejects an entire paste without changing drafts',
    () async {
      await cubit.load(1, 1);
      cubit.paste('5\t10\n8\t11', row: 0, column: 0);
      expect(cubit.state.drafts, isEmpty);
      expect(cubit.state.error, isNotNull);
      repository.current = markMatrix(moderated: true);
      await cubit.load(1, 1);
      cubit.paste('5\t10\n6\t11', row: 0, column: 0);
      expect(cubit.state.drafts, isEmpty);
      expect(cubit.canEdit(cubit.state.matrix!.learners.last), isFalse);
    },
  );

  test(
    'conflicts retain the draft and disable blind retry until explicit reload',
    () async {
      await cubit.load(1, 1);
      cubit.paste('5\t10', row: 0, column: 0);
      repository.captureError = ApiException(
        'A mark changed',
        statusCode: 409,
        isApiMessage: true,
      );
      expect(await cubit.save(), isFalse);
      expect(cubit.state.conflict, isTrue);
      expect(cubit.state.drafts[11]!.values[7], '5');
      expect(await cubit.save(), isFalse);
      expect(repository.writes, hasLength(1));
      await cubit.load(1, 1);
      expect(cubit.state.conflict, isFalse);
    },
  );

  test(
    'network failure keeps drafts while revoked access clears learner data',
    () async {
      await cubit.load(1, 1);
      cubit.paste('5\t10', row: 0, column: 0);
      repository.captureError = ApiException('No connection');
      await cubit.save();
      expect(cubit.state.dirty, isTrue);
      expect(cubit.state.conflict, isFalse);
      repository.captureError = ApiException('Access ended', statusCode: 403);
      await cubit.save();
      expect(cubit.state.matrix, isNull);
      expect(cubit.state.drafts, isEmpty);
    },
  );

  test('a late load cannot replace a new group context', () async {
    final old = Completer<AcademicMatrixData>();
    repository.loader = (group) =>
        group == 1 ? old.future : Future.value(markMatrix(group: group));
    final first = cubit.load(1, 1);
    await cubit.load(2, 1);
    old.complete(markMatrix());
    await first;
    expect(cubit.state.group, 2);
    expect(cubit.state.matrix!.groupId, 2);
  });

  test('late previews cannot replace the result of newer input', () async {
    repository.delayPreviews = true;
    await cubit.load(1, 1);
    cubit.paste('5\t10', row: 0, column: 0);
    final first = cubit.preview();
    cubit.paste('7\t11', row: 0, column: 0);
    final second = cubit.preview();
    repository.previews.last.complete(repository.previewResult(9000));
    await second;
    repository.previews.first.complete(repository.previewResult(7500));
    await first;
    expect(cubit.state.previews[11]!.runningBp, 9000);
  });

  test('locked terms cannot be changed by entry, paste or save', () async {
    repository.current = markMatrix(locked: true);
    await cubit.load(1, 1);
    final learner = cubit.state.matrix!.learners.first;
    cubit.change(learner, field: 7, value: '5');
    cubit.paste('5\t10', row: 0, column: 0);
    expect(cubit.state.dirty, isFalse);
    expect(await cubit.save(), isFalse);
    expect(repository.writes, isEmpty);
  });
}
