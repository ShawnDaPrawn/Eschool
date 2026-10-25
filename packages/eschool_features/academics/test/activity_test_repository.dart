import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';

Map<String, dynamic> activityGroupFixture({int school = 1, int id = 7}) => {
  'id': id,
  'school_id': school,
  'session_year_id': 2,
  'parent_id': null,
  'name': 'Hockey',
  'discipline': 'Sport',
  'season': '2026',
  'starts_on': '2026-01-01',
  'ends_on': '2026-12-31',
  'gender': 'any',
  'minimum_grade': null,
  'maximum_grade': null,
  'minimum_age': null,
  'maximum_age': null,
  'active': true,
  'revision': 1,
};
Map<String, dynamic> activitySessionFixture() => {
  'id': 9,
  'activity_group_id': 7,
  'on_date': '2026-02-10',
  'name': 'Practice',
  'venue': 'Field',
  'status': 'scheduled',
  'revision': 1,
};
Map<String, dynamic> activityMaterialFixture() => {
  'id': 11,
  'activity_group_id': 7,
  'author_id': 12,
  'title': 'Fixture note',
  'markdown': '# Bring your kit',
  'published': true,
  'revision': 1,
};
Map<String, dynamic> activityDetailFixture({
  bool staff = true,
  int school = 1,
}) => {
  'group': activityGroupFixture(school: school),
  'sessions': [activitySessionFixture()],
  'materials': [
    {...activityMaterialFixture(), 'html': '<h1>Bring your kit</h1>'},
  ],
  'can_manage': false,
  'can_capture': staff,
};
Map<String, dynamic> activityRosterFixture() => {
  'memberships': [
    for (final id in [17, 18])
      {
        'id': id,
        'activity_group_id': 7,
        'learner_id': id,
        'role': 'member',
        'active': true,
        'starts_on': '2026-01-01',
        'ends_on': '2026-12-31',
        'reason': 'Synthetic membership',
        'revision': 1,
        'learner_name': id == 17 ? 'Asha Learner' : 'Ben Learner',
      },
  ],
  'staff': [],
  'attendance': [],
  'session_roster': {
    'session_id': 9,
    'group_id': 7,
    'session_revision': 1,
    'learners': [
      for (final id in [17, 18])
        {
          'learner_id': id,
          'learner_name': id == 17 ? 'Asha Learner' : 'Ben Learner',
          'status': null,
          'revision': 0,
        },
    ],
  },
};

class FakeActivityRepository implements ActivityRepository {
  @override
  int schoolId = 1;
  @override
  int accountId = 12;
  @override
  bool isActive = true;
  bool staff = true;
  Object? readError, writeError;
  Future<ActivityGroupDetail> Function()? readDetail;
  int rosterReads = 0;
  Map<String, dynamic>? rosterData;
  final writes = <Map<String, dynamic>>[];
  @override
  Future<ActivityGroupList> groups({int page = 1}) async {
    if (readError != null) throw readError!;
    return ActivityGroupList.fromJson({
      'items': [activityGroupFixture()],
      'page': page,
      'last_page': page,
      'total': 1,
    });
  }

  @override
  Future<ActivityGroupDetail> detail(int group) async {
    if (readError != null) throw readError!;
    return readDetail != null
        ? readDetail!()
        : ActivityGroupDetail.fromJson(activityDetailFixture(staff: staff));
  }

  @override
  Future<ActivityGroupRoster> roster(int group, {int? sessionId}) async {
    rosterReads++;
    if (readError != null) throw readError!;
    return ActivityGroupRoster.fromJson(rosterData ?? activityRosterFixture());
  }

  void capture(Map<String, dynamic> input) {
    writes.add(input);
    if (writeError != null) throw writeError!;
  }

  @override
  Future<ActivitySession> session(
    int group,
    int? id,
    ActivitySessionWrite input,
  ) async {
    capture(input.toJson());
    return ActivitySession.fromJson({
      ...activitySessionFixture(),
      ...input.toJson(),
    });
  }

  @override
  Future<ActivityMaterial> material(
    int group,
    int? id,
    ActivityMaterialWrite input,
  ) async {
    capture(input.toJson());
    return ActivityMaterial.fromJson({
      ...activityMaterialFixture(),
      ...input.toJson(),
    });
  }

  @override
  Future<ActivityAttendanceReceipt> attendance(
    int session,
    ActivityAttendanceWrite input,
  ) async {
    capture(input.toJson());
    return ActivityAttendanceReceipt.fromJson({
      'session_revision': 2,
      'items': [],
      'unrecorded_count': 1,
    });
  }
}
