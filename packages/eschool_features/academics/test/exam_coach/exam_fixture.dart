import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

const examVersion = '138314b5-9394-408c-9cb8-405c4f2b23ac';
const examAnswer = 'bd1d21bc-4a71-4125-88e6-a32596c79916';
const examOperation = 'de100bf4-c237-4d13-ae2c-21da6c1bda5a';
const examPlan = 'fd2047ce-1d76-43de-9d37-d7c0286e782b';
Map<String, dynamic> examGroup() => {
  'id': 3,
  'name': 'Current fractions group',
  'subject_name': 'Mathematics',
};
Map<String, dynamic> examConfiguration({
  bool staff = true,
  bool enabled = true,
}) => {
  'reader_id': 7,
  'learner_id': staff ? null : 9,
  'staff_groups': staff ? [examGroup()] : [],
  'learner_groups': staff ? [] : [examGroup()],
  'writable': true,
  'photo_feedback_enabled': enabled,
};
Map<String, dynamic> examPaper({
  bool staff = true,
  String state = 'published',
}) => {
  'id': 2,
  'version_id': examVersion,
  'number': 1,
  'title': 'Fractions licensed paper',
  'framework': 'school',
  'paper_year': 2025,
  'paper_code': 'MATH-P1',
  'state': state,
  'licence_kind': 'permission',
  'licence_reference': 'Synthetic permission',
  'questions': [
    {
      'number': '1',
      'prompt': 'Explain one half.',
      'max_units': 1000,
      'topic_ids': [6],
      if (staff) 'memo': 'PRIVATE-STAFF-MEMO-CANARY',
    },
  ],
  if (staff) ...{
    'licence': {
      'kind': 'permission',
      'reference': 'Synthetic permission',
      'statement': 'The school obtained synthetic test permission.',
      'confirmed': true,
    },
    'revision': 1,
    'group_ids': [3],
    'can_edit': true,
  },
};
Map<String, dynamic> examDetail({
  bool staff = false,
  String state = 'completed',
  DateTime? expiry,
}) => {
  'id': examAnswer,
  'operation_id': examOperation,
  'state': state,
  'failure_code': null,
  'response': state == 'completed'
      ? {
          'question_token': 'a' * 64,
          'mark_units': 500,
          'confidence_basis_points': 7300,
          'feedback': 'PRIVATE-FEEDBACK-CANARY',
        }
      : null,
  'review': null,
  if (state != 'quarantined') ...{
    'group_id': 3,
    'paper_version_id': examVersion,
    'question_number': '1',
    'revision': 2,
    'expires_at':
        (expiry ?? DateTime.now().toUtc().add(const Duration(days: 1)))
            .toIso8601String(),
    'working': 'PRIVATE-WORKING-CANARY',
    'provisional': true,
  },
};
final examPixels = base64Decode(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
);

class FakeExamRepository implements ExamRepository {
  bool active = true,
      staff = true,
      enabled = true,
      readonly = false,
      unknownReview = false;
  bool receiptNotRecorded = false;
  String answerState = 'completed';
  DateTime? expiry;
  Completer<ExamCoachPaperVersion>? delayedPaper;
  final writes = <ExamWriteIntent>[];
  final planWrites = <ExamCoachPlanWrite>[];
  final reviewed = <String>[];
  int configurationCalls = 0, paperCalls = 0, photoCalls = 0, receiptCalls = 0;
  int get schoolId => 1;
  int get readerId => 7;
  bool get isActive => active;
  Future<ExamCoachConfiguration> configuration() async {
    configurationCalls++;
    return ExamCoachConfiguration.fromJson({
      ...examConfiguration(staff: staff, enabled: enabled),
      'writable': !readonly,
    });
  }

  Future<ExamCoachStaffChoices> choices(int group) async =>
      ExamCoachStaffChoices.fromJson({
        'group_id': 3,
        'frameworks': ['school'],
        'topics': [
          {'id': 6, 'name': 'Fractions'},
        ],
        'audience_groups': [examGroup()],
        'writable': !readonly,
      });
  Future<ExamCoachPaperList> papers(
    int group, {
    required bool staff,
    int page = 1,
  }) async => ExamCoachPaperList.fromJson({
    'group_id': 3,
    'papers': [
      {
        'id': 2,
        'version_id': examVersion,
        'number': 1,
        'title': 'Fractions licensed paper',
        'paper_year': 2025,
        'paper_code': 'MATH-P1',
        'state': 'published',
        'licence_kind': 'permission',
        'revision': 1,
        'can_edit': staff,
      },
    ],
    'page': page,
    'has_more': false,
  });
  Future<ExamCoachPaperVersion> paper(
    int group,
    String version, {
    required bool staff,
  }) async {
    paperCalls++;
    return delayedPaper == null
        ? ExamCoachPaperVersion.fromJson(examPaper(staff: staff))
        : await delayedPaper!.future;
  }

  Future<ExamCoachPaperReceipt> writePaper(ExamWriteIntent intent) async {
    writes.add(intent);
    return ExamCoachPaperReceipt.fromJson({
      'id': 2,
      'version_id': examVersion,
      'revision': 2,
      'state': 'draft',
    });
  }

  Future<ExamCoachOperationReceipt> operation(
    int group,
    String operation,
  ) async {
    receiptCalls++;
    if (receiptNotRecorded)
      throw ApiException('Not recorded yet', statusCode: 404);
    return ExamCoachOperationReceipt.fromJson({
      'kind': 'answer_review',
      'operation_id': operation,
      'paper': null,
      'review': {
        'id': operation,
        'answer_id': examAnswer,
        'revision': 3,
        'mark_units': 750,
      },
    });
  }

  Future<Uint8List> pdf(
    int group,
    String version, {
    required bool staff,
    bool memo = false,
  }) async => Uint8List.fromList(utf8.encode('%PDF-synthetic'));
  Future<ExamCoachExamDates> dates(int group) async =>
      ExamCoachExamDates.fromJson({
        'group_id': 3,
        'exams': [
          {
            'id': 4,
            'name': 'Published exam',
            'exam_on': '2026-12-02',
            'schedule_signature': 'b' * 64,
          },
        ],
      });
  Future<ExamCoachPlanReceipt> createPlan(
    int group,
    ExamCoachPlanWrite input,
  ) async {
    planWrites.add(input);
    return ExamCoachPlanReceipt.fromJson({
      'id': examPlan,
      'kind': 'study_plan',
      'state': 'ready',
    });
  }

  Future<ExamCoachPlanList> plans({int page = 1}) async =>
      ExamCoachPlanList(plans: [], page: page, hasMore: false);
  Future<ExamCoachStudyPlan> plan(String id) async =>
      ExamCoachStudyPlan.fromJson({
        'id': examPlan,
        'learner_id': 9,
        'group_id': 3,
        'subject_id': 2,
        'exam_on': '2026-12-02',
        'papers': [],
        'days': [
          {
            'date': '2026-12-01',
            'topic_id': 6,
            'minutes': 20,
            'topic_name': 'Fractions',
          },
        ],
      });
  Future<ExamCoachAnswerList> answers({
    int? group,
    int page = 1,
  }) async => ExamCoachAnswerList.fromJson({
    'page': page,
    'has_more': false,
    'answers': [
      {
        'id': examAnswer,
        'operation_id': examOperation,
        'state': answerState,
        'failure_code': null,
        'group_id': 3,
        'paper_version_id': examVersion,
        'question_number': '1',
        'revision': 2,
        'expires_at': DateTime.now()
            .toUtc()
            .add(const Duration(days: 1))
            .toIso8601String(),
        if (staff) ...{'learner_id': 9, 'learner_name': 'Synthetic learner'},
      },
    ],
  });
  Future<ExamCoachAnswerDetail> answer(String id, {int? group}) async =>
      ExamCoachAnswerDetail.fromJson(
        examDetail(staff: staff, state: answerState, expiry: expiry),
      );
  Future<ExamCoachAnswerDetail> answerOperation(String operation) =>
      answer(examAnswer);
  Future<ExamCoachAnswerUploadReceipt> upload(ExamPhotoIntent intent) async =>
      ExamCoachAnswerUploadReceipt.fromJson({
        'id': examAnswer,
        'operation_id': intent.crop.operation,
        'state': 'prepared',
      });
  Future<Uint8List> photo(String id, {int? group}) async {
    photoCalls++;
    return examPixels;
  }

  Future<ExamCoachAnswerDetail> feedback(
    String id,
    ExamCoachAnswerFeedbackWrite input,
  ) => answer(id);
  Future<ExamCoachAnswerReviewReceipt> review(
    int group,
    String id,
    ExamWriteIntent intent,
  ) async {
    writes.add(intent);
    reviewed.add(intent.operation);
    if (unknownReview) {
      unknownReview = false;
      throw ApiException('Synthetic response lost', isTransportFailure: true);
    }
    return ExamCoachAnswerReviewReceipt.fromJson({
      'id': intent.operation,
      'answer_id': id,
      'revision': 3,
      'mark_units': 750,
    });
  }

  Future<void> revoke(String id, String operation) async {}
}
