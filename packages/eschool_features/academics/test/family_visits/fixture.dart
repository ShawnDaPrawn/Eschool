import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

export '../family_forms/fixture.dart' show mount, tap;

const meetingId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
const passId = '7cf89daf-864a-47b5-a502-c322dd9602a2';
const operationId = '15c695a5-cfd7-4e87-b379-5ed550fb9776';
Map<String, dynamic> configJson({
  int school = 1,
  int reader = 7,
  bool staff = false,
  bool manager = false,
  bool writable = true,
  bool children = true,
}) => {
  'school_id': school,
  'reader_id': reader,
  'timezone': 'Africa/Johannesburg',
  'writable': writable,
  'can_manage': manager,
  'can_host': staff,
  'children': [
    if (children && !staff) {'id': 11, 'name': 'Synthetic current learner'},
  ],
  'hosts': [
    {'id': 12, 'name': 'Synthetic meeting host'},
  ],
  'gate_rules': {
    'max_window_minutes': 240,
    'max_future_days': 30,
    'scan_max_age_seconds': 120,
    'revision': 0,
    'staff_approval_required': true,
    'collector_check_required': true,
  },
};
Map<String, dynamic> slotJson({
  String status = 'open',
  int revision = 1,
  int? learner,
  int? guardian,
  int host = 12,
}) => {
  'id': meetingId,
  'host_id': host,
  'host_name': 'Synthetic meeting host',
  'title': 'Synthetic parent meeting',
  'venue': 'Synthetic private room',
  'starts_at': '2026-11-01T15:00:00Z',
  'ends_at': '2026-11-01T15:15:00Z',
  'booking_closes_at': '2026-10-31T10:00:00Z',
  'status': status,
  'revision': revision,
  'learner_id': learner,
  'learner_name': learner == null ? null : 'Synthetic current learner',
  'guardian_user_id': guardian,
};
Map<String, dynamic> passJson({
  String status = 'pending',
  int revision = 1,
  int learner = 11,
  int requester = 7,
  int? approver,
}) => {
  'id': passId,
  'learner_id': learner,
  'learner_name': 'Synthetic current learner',
  'requested_by': requester,
  'approved_by': approver,
  'collector': 'Synthetic private collector',
  'reason': 'Synthetic private collection reason',
  'starts_at': '2026-10-30T08:15:00Z',
  'ends_at': '2026-10-30T09:15:00Z',
  'status': status,
  'revision': revision,
  'collected_at': null,
};
Map<String, dynamic> ok(Object data) => {
  'error': false,
  'message': 'OK',
  'code': 200,
  'data': data,
};

class Visits implements FamilyVisitRepository {
  Visits({this.staff = false, this.manager = false, this.writable = true});
  final bool staff, manager;
  bool writable, revoked = false, active = true, unknown = false;
  @override
  int get schoolId => 1;
  @override
  int get accountId => staff ? 12 : 7;
  @override
  bool get isActive => active;
  int configurations = 0, effects = 0;
  int? meetingLearner, passLearner;
  Completer<FamilyMeetingPage>? delayedMeetings;
  final mutations = <FamilyVisitMutation>[];
  final receipts = <String, FamilyVisitReceipt>{};
  Map<String, dynamic> slot = slotJson();
  Map<String, dynamic>? pass;
  @override
  Future<FamilyVisitConfiguration> configuration() async {
    configurations++;
    if (revoked)
      throw ApiException(
        'Your meeting access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    return FamilyVisitConfiguration.fromJson(
      configJson(
        reader: accountId,
        staff: staff,
        manager: manager,
        writable: writable,
      ),
    );
  }

  @override
  Future<FamilyMeetingPage> meetings({int? learnerId, int page = 1}) async {
    meetingLearner = learnerId;
    if (delayedMeetings != null) return delayedMeetings!.future;
    return FamilyMeetingPage.fromJson({
      'items': [slot],
      'next_page': null,
    });
  }

  @override
  Future<FamilyGatePassPage> passes({int? learnerId, int page = 1}) async {
    passLearner = learnerId;
    return FamilyGatePassPage.fromJson({
      'items': [if (pass != null) pass],
      'next_page': null,
    });
  }

  @override
  Future<FamilyVisitReceipt> execute(FamilyVisitMutation intent) async {
    mutations.add(intent);
    final operation = intent.body['operation_id'] as String;
    if (receipts.containsKey(operation)) return receipts[operation]!;
    effects++;
    final revision = (intent.body['revision'] as int? ?? 0) + 1;
    final id = intent.target ?? passId;
    final status = switch (intent.action) {
      FamilyVisitAction.book => 'booked',
      FamilyVisitAction.unbook => 'open',
      FamilyVisitAction.requestPass => 'pending',
      FamilyVisitAction.approvePass => 'approved',
      _ => 'cancelled',
    };
    if ([
      FamilyVisitAction.book,
      FamilyVisitAction.unbook,
      FamilyVisitAction.cancelMeeting,
    ].contains(intent.action)) {
      slot = slotJson(
        status: status,
        revision: revision,
        learner: status == 'booked' ? 11 : null,
        guardian: status == 'booked' ? 7 : null,
      );
    } else {
      pass = passJson(
        status: status,
        revision: revision,
        approver: status == 'approved' ? accountId : null,
      );
    }
    final receipt = FamilyVisitReceipt(id, status, revision);
    receipts[operation] = receipt;
    if (unknown) {
      unknown = false;
      throw ApiException('Synthetic lost committed response', statusCode: 503);
    }
    return receipt;
  }
}
