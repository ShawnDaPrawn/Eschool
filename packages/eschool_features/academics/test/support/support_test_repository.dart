import 'dart:async';
import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

class FakeSupport implements SupportRepository {
  @override
  int schoolId = 1;
  @override
  int accountId = 2;
  @override
  bool isActive = true;
  List<String> audiences = ['professional'];
  bool writable = true, deny = false, failWrite = false, hasPlan = false;
  int revision = 1;
  String? due;
  bool paginated = false;
  final reads = <String>[], writes = <String>[];
  Completer<SupportAccess>? delayedAccess;
  @override
  Future<SupportAccess> access() async {
    reads.add('access');
    if (deny) throw ApiException('Permission withdrawn', statusCode: 403);
    return delayedAccess?.future ??
        SupportAccess(
          audiences: audiences,
          canWrite: writable,
          today: '2026-02-10',
        );
  }

  @override
  Future<SupportConfiguration> configuration(
    String audience, {
    String search = '',
    int page = 1,
    int? learnerId,
    int? staffId,
  }) async {
    reads.add('configuration:$audience');
    return SupportConfiguration.fromJson({
      'audience': audience,
      'page': page,
      'last_page': paginated ? 2 : 1,
      'learners': [
        if (!paginated || learnerId == 3 || (page == 1 && search.isEmpty))
          {'id': 3, 'name': 'Synthetic Learner'},
      ],
      'staff': [
        {
          'id':
              paginated &&
                  (staffId == 5 ||
                      (staffId == null && (page == 2 || search.isNotEmpty)))
              ? 5
              : 4,
          'name':
              paginated &&
                  (staffId == 5 ||
                      (staffId == null && (page == 2 || search.isNotEmpty)))
              ? 'Other professional'
              : audience == 'principal'
              ? 'Named Counsellor'
              : 'Named Teacher',
        },
      ],
      'staff_last_page': paginated ? 2 : 1,
    });
  }

  @override
  Future<SupportCases> cases({int page = 1}) async {
    reads.add('cases');
    return SupportCases.fromJson({
      'page': 1,
      'last_page': 1,
      'items': [
        if (hasPlan)
          {
            'id': 10,
            'learner_id': 3,
            'learner_name': 'Synthetic Learner',
            'revision': revision,
            'state': 'active',
            'review_due_on': null,
          },
      ],
    });
  }

  @override
  Future<SupportAssignments> assignments({int page = 1}) async {
    reads.add('assignments');
    return SupportAssignments.fromJson({
      'page': 1,
      'last_page': 1,
      'items': [
        {
          'id': 20,
          'learner_id': 3,
          'learner_name': 'Synthetic Learner',
          'user_id': 4,
          'professional_name': 'Named Counsellor',
          'starts_on': '2026-02-10',
          'ends_on': '2026-03-10',
          'active': true,
          'revision': 1,
        },
      ],
    });
  }

  @override
  Future<SupportTeacherList> instructions({int page = 1}) async {
    reads.add('instructions');
    return SupportTeacherList.fromJson({
      'page': 1,
      'last_page': 1,
      'items': [
        {
          'id': 30,
          'learner_id': 3,
          'learner_name': 'Synthetic Learner',
          'revision': 1,
          'expires_on': '2026-03-10',
          'accommodations': 'Approved quiet seating',
        },
      ],
    });
  }

  SupportPlan record() => SupportPlan.fromJson({
    'id': 10,
    'learner_id': 3,
    'learner_name': 'Synthetic Learner',
    'revision': revision,
    'state': 'active',
    'review_due_on': due,
    'started_on': '2026-02-01',
    'sias_stage': 'SNA1',
    'needs': 'Private clinical needs',
    'accommodations': 'Private full plan',
    'reviews_has_more': false,
    'disclosures_has_more': false,
    'reviews': [],
    'disclosures': [],
  });
  @override
  Future<SupportPlan> plan(int id) async {
    reads.add('plan');
    return record();
  }

  @override
  Future<SupportReviews> reviews(int id, {int page = 1}) async {
    reads.add('reviews');
    return SupportReviews.fromJson({
      'plan_id': id,
      'page': 1,
      'last_page': 1,
      'items': [],
    });
  }

  @override
  Future<SupportVersions> versions(int id, {int page = 1}) async {
    reads.add('versions');
    return SupportVersions.fromJson({
      'plan_id': id,
      'page': 1,
      'last_page': 1,
      'items': [],
    });
  }

  @override
  Future<SupportDisclosures> disclosures(int id, {int page = 1}) async {
    reads.add('disclosures');
    return SupportDisclosures.fromJson({
      'plan_id': id,
      'page': 1,
      'last_page': 1,
      'items': [],
    });
  }

  @override
  Future<SupportWriteReceipt> write(
    String action,
    int? id,
    Map<String, dynamic> payload,
  ) async {
    writes.add(jsonEncode(payload));
    if (failWrite) {
      failWrite = false;
      throw ApiException('Connection lost', isTransportFailure: true);
    }
    hasPlan = true;
    if (action == 'review') {
      revision++;
      if (payload['next_review_on'] != null) {
        due = payload['next_review_on'] as String;
      }
    }
    return SupportWriteReceipt.fromJson({
      'id': 10,
      'revision': revision,
      'kind': action == 'review' ? 'review' : 'plan',
    });
  }
}
