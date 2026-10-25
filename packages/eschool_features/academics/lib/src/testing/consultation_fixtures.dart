import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic transport for full-app consultation flows; never makes a network call.
class ConsultationSmokeFixture {
  ConsultationSmokeFixture({required this.accountId});
  final int accountId;
  bool revoked = false, submitted = false;
  int votes = 0;
  Map<String, dynamic>? lastVote;
  Map<String, dynamic> summary() => {
    'id': 9,
    'revision': 2,
    'status': 'open',
    'type': 'survey',
    'title': 'Synthetic school consultation',
    'instructions': 'Synthetic private consultation instructions.',
    'session_year_id': 7,
    'anonymous': true,
    'results_visibility': 'after_close',
    'opens_at': '2026-02-10T08:00:00Z',
    'closes_at': '2027-02-10T08:00:00Z',
    'ended_at': null,
    'writable': true,
  };
  Object guarded(Object data) => revoked
      ? const FakeResponse({
          'error': true,
          'message': 'Audience withdrawn',
        }, status: 403)
      : {'error': false, 'message': 'Synthetic result', 'data': data};
  Map<String, Object> get routes => {
    'GET v2/consultations/access': (_) => guarded({
      'school_id': 1,
      'account_id': accountId,
      'session_year_id': 7,
      'today': '2026-10-25',
      'timezone': 'Africa/Johannesburg',
      'writable': true,
      'can_manage': false,
    }),
    'GET v2/consultations': (_) => guarded({
      'items': [summary()],
      'page': 1,
      'last_page': 1,
    }),
    'GET v2/consultations/9': (_) => guarded({
      ...summary(),
      'open': true,
      'participated': submitted,
      'questions': [
        {
          'id': 3,
          'kind': 'single',
          'label': 'Choose a school activity time',
          'required': true,
          'max_choices': 1,
          'options': [
            {'id': 11, 'label': 'Morning', 'statement': null},
            {'id': 12, 'label': 'Afternoon', 'statement': null},
          ],
        },
        {
          'id': 4,
          'kind': 'text',
          'label': 'Your private suggestion',
          'required': true,
          'max_choices': 1,
          'options': [],
        },
      ],
    }),
    'POST v2/consultations/9/vote': (RequestOptions request) {
      if (revoked) {
        return guarded({});
      }
      votes++;
      lastVote = Map<String, dynamic>.from(request.data as Map);
      if (submitted)
        return const FakeResponse({'message': 'Already recorded'}, status: 409);
      submitted = true;
      return guarded({'participated': true});
    },
  };
}
