import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  const operation = '5a17978d-5bab-4e3b-a283-c28b03b9937d';
  test('signed points retain exact operation identity', () {
    final json = {
      'operation_id': operation,
      'learner_id': 2,
      'session_year_id': 7,
      'point_category_id': 4,
      'points': -5,
      'reason': 'Synthetic correction',
      'public': false,
    };
    final request = RecognitionIssue.fromJson(json);
    expect(request.points, -5);
    expect(request.toJson(), json);
    expect(RecognitionReference.fromJson({'id': 9, 'revision': 9}).toJson(), {
      'id': 9,
      'revision': 9,
    });
  });
  test('presentation dates remain school calendar strings', () {
    final request = RecognitionPresent.fromJson({
      'operation_id': operation,
      'learner_id': 2,
      'session_year_id': 7,
      'award_definition_id': 4,
      'award_nomination_id': null,
      'presented_on': '2026-12-31',
      'event': null,
    });
    expect(request.presentedOn, '2026-12-31');
    expect(request.toJson()['presented_on'], '2026-12-31');
    expect(request.toJson()['award_nomination_id'], isNull);
  });
  test(
    'zero counters and signed history are separate from permanent awards',
    () {
      final counters = RecognitionHouseCounters.fromJson({
        'session_year_id': 7,
        'revision': 0,
        'items': [
          {
            'id': 3,
            'name': 'Mountain',
            'emblem': 'mountain',
            'primary_colour': '#123456',
            'secondary_colour': '#ffffff',
            'active': true,
            'points': 0,
            'revision': 0,
          },
        ],
      });
      expect(counters.revision, 0);
      expect(counters.items.single.points, 0);
      expect(counters.toJson().toString(), isNot(contains('learner_id')));
      final history = RecognitionPointHistory.fromJson({
        'items': [
          {
            'id': 9,
            'house_id': 3,
            'house_name': 'Mountain',
            'category_name': 'Helping',
            'points': -5,
            'reason': 'Correction',
            'on_date': '2026-12-31',
            'public': false,
            'reverses_id': 8,
          },
        ],
        'next_before': null,
      });
      expect(history.items.single.reversesId, 8);
      expect(history.items.single.onDate, '2026-12-31');
    },
  );
  test('permanent awards preserve outside source without point history', () {
    final profile = RecognitionAwardProfile.fromJson({
      'items': [
        {
          'id': 9,
          'session_year_id': 7,
          'title': 'Regional music',
          'criteria': 'External achievement',
          'presented_on': '2026-12-31',
          'event': null,
          'outside_organisation': 'Synthetic Music',
          'source_kind': 'outside',
        },
      ],
      'next_before': null,
    });
    expect(profile.items.single.sourceKind, 'outside');
    expect(profile.items.single.presentedOn, '2026-12-31');
    expect(profile.toJson().toString(), isNot(contains('points')));
  });
}
