import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
export 'package:eschool_academics/testing.dart' show masteryFixture;

class FakeMasteryRepository implements MasteryRepository {
  @override
  final int schoolId = 1, readerId = 12;
  @override
  bool isActive = true;
  Object? error;
  bool visible = true;
  int lastPage = 1;
  Future<MasteryView> Function(int learner, int subject)? fetchFamily;
  void check() {
    if (error != null) throw error!;
  }

  @override
  Future<MasteryConfiguration> configuration() async {
    check();
    return MasteryConfiguration.fromJson({
      'session_year_id': 2,
      'can_manage': false,
      'settings': {
        'family_marks_enabled': visible,
        'revision': 0,
        'policy_version': 'recent20-v1',
      },
      'groups': [
        {
          'id': 9,
          'name': 'Mathematics A',
          'subject_id': 4,
          'subject_name': 'Mathematics',
          'grade_level_id': 7,
          'grade_name': 'Grade 7',
          'session_year_id': 2,
          'year_name': '2026',
        },
      ],
    });
  }

  @override
  Future<MasteryRoster> roster(int group, {int page = 1}) async {
    check();
    return MasteryRoster.fromJson({
      'group_id': group,
      'current_page': page,
      'last_page': lastPage,
      'total': lastPage,
      'learners': [
        {
          'learner_id': page == 1 ? 11 : 13,
          'display_name': page == 1 ? 'Asha Synthetic' : 'Ben Synthetic',
        },
      ],
    });
  }

  @override
  Future<MasterySubjectList> subjects(int learner) async {
    check();
    return MasterySubjectList.fromJson({
      'learner_id': learner,
      'subjects': [
        {'id': 4, 'name': 'Mathematics'},
        {'id': 5, 'name': 'Science'},
      ],
    });
  }

  @override
  Future<MasteryView> staff(int group, int learner, int subject) async {
    check();
    return MasteryView.fromJson(
      masteryFixture(learner: learner, subject: subject, staff: true),
    );
  }

  @override
  Future<MasteryView> family(int learner, int subject) async {
    check();
    if (fetchFamily != null) return fetchFamily!(learner, subject);
    return MasteryView.fromJson(
      masteryFixture(learner: learner, subject: subject, visible: visible),
    );
  }
}
