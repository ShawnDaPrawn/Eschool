import 'dart:async';
import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

class FakeWellbeing implements WellbeingRepository {
  @override
  int schoolId = 1;
  @override
  int accountId = 2;
  @override
  bool isActive = true;
  bool deny = false, writable = true, offlineOnce = false, conflictOnce = false;
  int revision = 1;
  String body = 'Private clinical baseline',
      triage = 'Private reported concern';
  final writes = <String>[], reads = <String>[];
  final appointmentRows = <WellbeingAppointment>[];
  Completer<WellbeingCase>? delayedCase;
  void guard() {
    if (deny) throw ApiException('Assignment withdrawn', statusCode: 403);
  }

  @override
  Future<WellbeingNoticePage> notices(int page) async {
    guard();
    return WellbeingNoticePage(
      items: [],
      page: page,
      lastPage: page,
      unreadCount: 0,
    );
  }

  @override
  Future<WellbeingNoticeReadReceipt> readNotice(int id) async {
    guard();
    return WellbeingNoticeReadReceipt(
      id: id,
      readAt: DateTime.utc(2026, 2, 10),
    );
  }

  @override
  Future<WellbeingAccess> access() async {
    guard();
    reads.add('access');
    return WellbeingAccess(
      canConfigure: false,
      professional: true,
      canTriage: true,
      canCasework: true,
      canWrite: writable,
      today: '2026-02-10',
      schoolSlug: 'synthetic',
      emergencyGuidance: 'Speak to a trusted adult in an emergency.',
      timezone: 'Africa/Johannesburg',
    );
  }

  @override
  Future<WellbeingCasePage> cases(int page) async {
    guard();
    return WellbeingCasePage.fromJson({
      'items': [
        {
          'id': 10,
          'learner_id': 3,
          'learner_name': 'Synthetic Learner',
          'status': 'open',
          'opened_at': '2026-02-10T08:00:00Z',
          'revision': revision,
        },
      ],
      'page': 1,
      'last_page': 1,
    });
  }

  @override
  Future<WellbeingReportPage> reports(int page) async {
    guard();
    return WellbeingReportPage.fromJson({
      'items': [
        {
          'id': 11,
          'category_id': 1,
          'category_name': 'Synthetic incident',
          'anonymous': true,
          'urgency': 'routine',
          'status': 'open',
          'revision': revision,
          'created_at': '2026-02-10T08:00:00Z',
        },
      ],
      'page': 1,
      'last_page': 1,
    });
  }

  WellbeingCase record() => WellbeingCase.fromJson({
    'id': 10,
    'learner_id': 3,
    'learner_name': 'Synthetic Learner',
    'incident_report_id': null,
    'status': 'open',
    'opened_at': '2026-02-10T08:00:00Z',
    'revision': revision,
    'has_older': false,
    'next_before': null,
    'entries': [
      {
        'id': 1,
        'author_id': 2,
        'author_name': 'Synthetic counsellor',
        'supersedes_id': null,
        'kind': 'note',
        'body': body,
        'happened_at': '2026-02-10T08:00:00Z',
        'created_at': '2026-02-10T08:00:00Z',
      },
    ],
  });
  @override
  Future<WellbeingCase> caseRecord(int id, {int? before}) async {
    guard();
    reads.add('case:$id');
    return delayedCase?.future ?? record();
  }

  @override
  Future<WellbeingReport> report(int id, {int? before}) async {
    guard();
    return WellbeingReport.fromJson({
      'id': 11,
      'category_id': 1,
      'category_name': 'Synthetic incident',
      'anonymous': true,
      'reporter_user_id': null,
      'learner_id': null,
      'reporter_name': null,
      'learner_name': null,
      'body': triage,
      'urgency': 'routine',
      'status': 'open',
      'identified_at': null,
      'revision': revision,
      'created_at': '2026-02-10T08:00:00Z',
      'threads': [],
      'has_older': false,
      'next_before': null,
    });
  }

  @override
  Future<WellbeingCaseChoicePage> professionals(
    int id,
    String date, {
    String search = '',
    int page = 1,
    int? selected,
  }) async {
    guard();
    reads.add('professionals:$date');
    return WellbeingCaseChoicePage(
      items: [WellbeingNamedChoice(id: 2, name: 'Synthetic counsellor')],
      page: 1,
      lastPage: 1,
      selectedAvailable: true,
    );
  }

  @override
  Future<WellbeingCaseChoicePage> incidentChoices(
    int id, {
    int page = 1,
    int? selected,
  }) async {
    guard();
    return WellbeingCaseChoicePage(items: [], page: 1, lastPage: 1);
  }

  @override
  Future<List<WellbeingAppointment>> appointments(
    int id,
    String from,
    String to,
  ) async {
    guard();
    return List.of(appointmentRows);
  }

  @override
  Future<void> write(
    String action,
    int id,
    Map<String, dynamic> payload, {
    int? appointment,
  }) async {
    guard();
    writes.add(jsonEncode(payload));
    if (offlineOnce) {
      offlineOnce = false;
      throw ApiException('Connection lost');
    }
    if (conflictOnce) {
      conflictOnce = false;
      revision++;
      body = 'Current private clinical record';
      throw ApiException('Conflict', statusCode: 409);
    }
    revision++;
    if (action == 'entry') body = payload['body'];
    if (action == 'appointment') {
      appointmentRows.removeWhere((row) => row.id == (appointment ?? 1));
      appointmentRows.add(
        WellbeingAppointment.fromJson({
          'id': appointment ?? 1,
          'support_case_id': id,
          ...payload,
        }),
      );
    }
  }
}
