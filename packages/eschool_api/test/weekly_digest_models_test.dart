import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('an empty weekly page retains a nullable pagination cursor', () {
    final page = WeeklyDigestPage.fromJson({
      'items': <dynamic>[],
      'total': 0,
      'next_before': null,
    });
    expect(page.nextBefore, isNull);
    expect(page.toJson()['next_before'], isNull);
  });
  test(
    'a home action preserves the exact child and optional authorized material',
    () {
      final action = WeeklyDigestHomeAction.fromJson({
        'kind': 'practice',
        'learner_id': 11,
        'label': 'Open school materials together',
        'content_version_id': 9,
      });
      expect(action.kind, WeeklyDigestHomeActionKindEnum.practice);
      expect(action.learnerId, 11);
      expect(action.contentVersionId, 9);
      expect(
        WeeklyDigestHomeAction.fromJson({
          ...action.toJson(),
          'content_version_id': null,
        }).contentVersionId,
        isNull,
      );
    },
  );
  test('an unfamiliar home action has a safe unknown enum value', () {
    final action = WeeklyDigestHomeAction.fromJson({
      'kind': 'future_kind',
      'learner_id': 11,
      'label': 'A future action',
      'content_version_id': null,
    });
    expect(action.kind, WeeklyDigestHomeActionKindEnum.unknownDefaultOpenApi);
  });
}
