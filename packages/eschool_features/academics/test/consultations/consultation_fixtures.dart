import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

Map<String, dynamic> consultationSummaryJson({
  String title = 'Synthetic family consultation',
  bool writable = true,
  bool live = false,
}) => {
  'id': 9,
  'revision': 2,
  'status': 'open',
  'type': 'survey',
  'title': title,
  'instructions': 'Synthetic private instructions.',
  'session_year_id': 2,
  'anonymous': true,
  'results_visibility': live ? 'public_live' : 'after_close',
  'opens_at': '2026-02-10T08:00:00Z',
  'closes_at': '2027-02-11T08:00:00Z',
  'ended_at': null,
  'writable': writable,
};

class FakeConsultationRepository implements ConsultationRepository {
  bool active = true,
      writable = true,
      deny = false,
      submitted = false,
      live = false;
  bool unknownOnce = false, commitUnknown = true;
  String title = 'Synthetic family consultation';
  int accessCalls = 0, ballotCalls = 0, resultsCalls = 0;
  Completer<ConsultationBallot>? pendingBallot;
  final posts = <List<ConsultationVoteAnswer>>[];
  @override
  int get schoolId => 1;
  @override
  int get accountId => 7;
  @override
  bool get isActive => active;
  void guard() {
    if (deny) throw ApiException('Revoked audience', statusCode: 403);
  }

  @override
  Future<ConsultationAccess> access() async {
    guard();
    accessCalls++;
    return ConsultationAccess(
      schoolId: 1,
      accountId: 7,
      sessionYearId: 2,
      today: '2026-02-10',
      timezone: 'Africa/Johannesburg',
      writable: writable,
      canManage: false,
    );
  }

  @override
  Future<ConsultationPage> listing({int page = 1}) async {
    guard();
    return ConsultationPage.fromJson({
      'items': [
        consultationSummaryJson(title: title, writable: writable, live: live),
      ],
      'page': page,
      'last_page': 2,
    });
  }

  ConsultationBallot ballotValue() => ConsultationBallot.fromJson({
    ...consultationSummaryJson(title: title, writable: writable, live: live),
    'open': true,
    'participated': submitted,
    'questions': [
      {
        'id': 3,
        'kind': 'single',
        'label': 'Synthetic fixture time',
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
        'label': 'Synthetic private suggestion',
        'required': true,
        'max_choices': 1,
        'options': [],
      },
    ],
  });
  @override
  Future<ConsultationBallot> ballot(int id) async {
    guard();
    ballotCalls++;
    return pendingBallot?.future ?? Future.value(ballotValue());
  }

  @override
  Future<ConsultationVoteReceipt> vote(
    int id,
    List<ConsultationVoteAnswer> answers,
  ) async {
    guard();
    posts.add(answers);
    if (unknownOnce) {
      unknownOnce = false;
      submitted = commitUnknown;
      throw ApiException('Synthetic uncertain network outcome');
    }
    if (submitted) throw ApiException('Already recorded', statusCode: 409);
    submitted = true;
    return ConsultationVoteReceipt(participated: true);
  }

  @override
  Future<ConsultationResults> results(int id) async {
    guard();
    resultsCalls++;
    return ConsultationResults.fromJson({
      'id': 9,
      'closed': false,
      'anonymous': true,
      'response_count': 4,
      'questions': [
        {
          'id': 3,
          'kind': 'single',
          'label': 'Synthetic fixture time',
          'required': true,
          'max_choices': 1,
          'options': [
            {'id': 11, 'label': 'Morning', 'statement': null},
            {'id': 12, 'label': 'Afternoon', 'statement': null},
          ],
          'answered': 3,
          'counts': [
            {'option_id': 11, 'count': 2},
            {'option_id': 12, 'count': 1},
          ],
        },
        {
          'id': 4,
          'kind': 'text',
          'label': 'Synthetic private suggestion',
          'required': true,
          'max_choices': 1,
          'options': [],
          'answered': 2,
          'counts': [],
          'texts': ['UNAUTHORIZED OTHER PERSON ANSWER'],
        },
      ],
    });
  }
}
