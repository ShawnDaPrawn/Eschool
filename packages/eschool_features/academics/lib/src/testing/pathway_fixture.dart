import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// In-memory source fixtures; no real admission policy or persisted marks.
class PathwaySmokeFixture {
  PathwaySmokeFixture({this.reader = 21});
  final int reader;
  static const programme = 'b63cbd29-266d-470f-b428-4b1fcfa5d46f';
  static const guide = 'a63cbd29-266d-470f-b428-4b1fcfa5d46f';
  bool allowed = true;
  int revision = 1;
  final calculations = <Map<String, dynamic>>[];
  Object result(Object data) => allowed
      ? {'error': false, 'message': 'OK', 'data': data, 'code': 200}
      : const FakeResponse({
          'error': true,
          'message': 'Access withdrawn',
        }, status: 403);
  Map<String, dynamic> configuration() => {
    'school_id': 1,
    'reader_id': reader,
    'writable': false,
    'can_manage': false,
    'children': [
      {'id': 11, 'name': 'Synthetic current learner'},
    ],
    'subjects': [
      {'id': 4, 'name': 'Synthetic Mathematics'},
      {'id': 5, 'name': 'Synthetic Language'},
      {'id': 6, 'name': 'Synthetic Life Orientation'},
    ],
    'years': [
      {'id': 2, 'name': 'Synthetic year'},
    ],
    'terms': [
      {'id': 3, 'name': 'Synthetic term', 'session_year_id': 2},
    ],
  };
  Map<String, dynamic> reference({bool selection = false}) => {
    'id': selection ? guide : programme,
    'revision': revision,
    'kind': selection ? 'subject_choice_guide' : 'programme',
    'status': 'published',
    'title': selection ? 'Synthetic Grade 9 guide' : 'Synthetic programme',
    'institution': selection ? null : 'Synthetic institution',
    'source_title': 'Synthetic reviewed source',
    'source_url': 'https://institution.example.test/source',
    'source_year': 2026,
    'reviewed_on': '2026-10-01',
    'expires_on': '2027-10-01',
    'guidance': 'Synthetic planning rules for testing only.',
    'rules': selection
        ? {
            'compulsory_subject_ids': [4],
            'elective_groups': [
              {
                'title': 'Synthetic elective',
                'choose': 1,
                'subject_ids': [5, 6],
              },
            ],
          }
        : {
            'subjects': [
              {'subject_id': 4, 'minimum_bp': 5000, 'counts_for_score': true},
              {'subject_id': 6, 'minimum_bp': null, 'counts_for_score': false},
            ],
            'bands': [
              {'minimum_bp': 5000, 'points': 4},
              {'minimum_bp': 0, 'points': 0},
            ],
            'best_n': 1,
            'minimum_score': 4,
          },
  };
  Map<String, dynamic> projection(
    Map<String, dynamic> body, {
    bool selection = false,
  }) {
    final released = body['source'] == 'released';
    final mark = selection
        ? null
        : released
        ? 6250
        : ((body['marks'] as List).first as Map)['percentage_bp'] as int?;
    final ids = (body['subject_ids'] as List? ?? []).cast<int>();
    final complete = mark != null;
    final points = complete ? (mark >= 5000 ? 4 : 0) : null;
    return {
      'school_id': 1,
      'reader_id': reader,
      'learner_id': body['learner_id'],
      'reference_id': selection ? guide : programme,
      'revision': revision,
      'kind': selection ? 'subject_choice_guide' : 'programme',
      'source': body['source'],
      'score': selection
          ? null
          : {
              'projected_score': points,
              'known_subtotal': points ?? 0,
              'minimum_score': 4,
              'status': !complete
                  ? 'unknown'
                  : points == 4
                  ? 'meets_published_minimum'
                  : 'below_published_minimum',
              'missing_subject_ids': complete ? [] : [4],
              'below_minimum_subject_ids': complete && mark < 5000 ? [4] : [],
              'counted_subject_ids': complete ? [4] : [],
              'subjects': [
                {
                  'subject_id': 4,
                  'percentage_bp': mark,
                  'points': points,
                  'minimum_bp': 5000,
                  'counts_for_score': true,
                },
                {
                  'subject_id': 6,
                  'percentage_bp': null,
                  'points': null,
                  'minimum_bp': null,
                  'counts_for_score': false,
                },
              ],
            },
      'selection': !selection
          ? null
          : {
              'valid':
                  ids.contains(4) &&
                  ids.where((id) => id == 5 || id == 6).length == 1,
              'selected_subject_ids': ids,
              'missing_compulsory_subject_ids': ids.contains(4) ? [] : [4],
              'groups': [
                {
                  'title': 'Synthetic elective',
                  'choose': 1,
                  'selected': ids.where((id) => id == 5 || id == 6).length,
                  'valid': ids.where((id) => id == 5 || id == 6).length == 1,
                },
              ],
            },
      'released_evidence': !released
          ? []
          : [
              {
                'subject_id': 4,
                'percentage_bp': 6250,
                'grade_name': 'Synthetic released grade',
                'coverage_bp': 7500,
                'provisional': true,
                'released_at': '2026-10-01T12:00:00Z',
                'release_id': 1,
              },
            ],
    };
  }

  Object calculate(RequestOptions request, bool selection) {
    if (!allowed) return result({});
    final body = Map<String, dynamic>.from(
      request.data is String
          ? jsonDecode(request.data as String) as Map
          : request.data as Map,
    );
    calculations.add(body);
    return result(projection(body, selection: selection));
  }

  Map<String, Object> get routes => {
    'GET v2/pathways/configuration': (_) => result(configuration()),
    'GET v2/pathways/references': (_) => result({
      'school_id': 1,
      'reader_id': reader,
      'items': [reference(), reference(selection: true)],
      'next_page': null,
    }),
    for (final selection in [false, true]) ...{
      'GET v2/pathways/references/${selection ? guide : programme}': (_) =>
          result({
            'school_id': 1,
            'reader_id': reader,
            'reference': reference(selection: selection),
          }),
      'POST v2/pathways/references/${selection ? guide : programme}/projection':
          (RequestOptions r) => calculate(r, selection),
    },
  };
}
