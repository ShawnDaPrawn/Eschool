import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:crypto/crypto.dart';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

const teachingDraftId = '02c76bc2-30f2-47c2-a0c0-17cc38ea5e5f';
const teachingOperation = 'f22536dd-6f02-4d5e-874e-a3a54e2911b9';
const teachingPhotoId = '6144da0c-1e8e-4a5c-9938-937a47f6cf6e';

Map<String, dynamic> teachingConfigurationJson() => {
  'school_id': 1,
  'reader_id': 7,
  'today': '2026-10-26',
  'writable': true,
  'groups': [
    {'id': 3, 'name': 'Maths group', 'subject_name': 'Mathematics'},
  ],
};
Map<String, dynamic> teachingChoicesJson() => {
  'school_id': 1,
  'reader_id': 7,
  'group_id': 3,
  'sources': [
    {'id': 4, 'name': 'Approved fractions lesson'},
  ],
  'topics': [
    {'id': 6, 'name': 'Fractions'},
  ],
  'tasks': [
    {'id': 5, 'name': 'Fractions assessment', 'term_id': 2},
  ],
};
Map<String, dynamic> teachingSummaryJson({
  String state = 'ready',
  int revision = 1,
}) => {
  'school_id': 1,
  'reader_id': 7,
  'id': teachingDraftId,
  'operation_id': teachingOperation,
  'kind': 'worksheet',
  'state': state,
  'revision': revision,
  'group_id': 3,
  'content_version_id': 4,
  'learner_id': null,
  'task_id': null,
};
Map<String, dynamic> teachingDetailJson({
  String state = 'ready',
  int revision = 1,
}) => {
  ...teachingSummaryJson(state: state, revision: revision),
  'request': state == 'quarantined'
      ? null
      : {
          'kind': 'worksheet',
          'language': 'en',
          'brief': 'Private planning canary',
          'learner_id': null,
          'task_id': null,
          'photo_ids': <String>[],
          'topic_ids': <int>[],
        },
  'definition': state == 'quarantined'
      ? null
      : {
          'title': 'Private worksheet canary',
          'sections': [
            for (final level in ['support', 'core', 'extension'])
              {'level': level, 'body': 'Private $level working canary'},
          ],
        },
  'question_labels': <dynamic>[],
};

class FakeTeachingRepository implements TeachingRepository {
  @override
  final int schoolId = 1, readerId = 7;
  @override
  bool isActive = true;
  bool denied = false, loseGeneration = false, loseReview = false;
  String? allowanceMessage;
  bool loseUpload = false;
  final uploads = <TeachingPhotoIntent>[];
  Uint8List? uploadedBytes;
  int generations = 0, receiptReads = 0, reviewEffects = 0, configurations = 0;
  int publications = 0, officialMarkWrites = 0;
  final sentReviews = <TeachingReviewIntent>[];
  Completer<TeachingDraftDetail>? delayedRead;
  Completer<TeachingDraftDetail>? delayedGeneration;
  Map<String, dynamic>? current;
  final _receipts = <String, TeachingDraftMutation>{};

  void guard() {
    if (denied || !isActive) throw ApiException('withdrawn', statusCode: 403);
  }

  @override
  Future<TeachingDraftConfiguration> configuration({
    bool insights = false,
  }) async {
    guard();
    configurations++;
    return TeachingDraftConfiguration.fromJson(teachingConfigurationJson());
  }

  @override
  Future<TeachingDraftChoices> choices(
    int group, {
    bool insights = false,
  }) async {
    guard();
    return TeachingDraftChoices.fromJson(teachingChoicesJson());
  }

  @override
  Future<TeachingLearnerPage> learners(int group, {int page = 1}) async {
    guard();
    return TeachingLearnerPage.fromJson({
      'school_id': schoolId,
      'reader_id': readerId,
      'group_id': group,
      'items': [
        {'id': 9, 'name': 'Private learner canary', 'approved': true},
      ],
      'page': page,
      'last_page': 1,
    });
  }

  @override
  Future<TeachingDraftPage> drafts(int group, {int page = 1}) async {
    guard();
    return TeachingDraftPage.fromJson({
      'school_id': schoolId,
      'reader_id': readerId,
      'group_id': group,
      'items': current == null
          ? <dynamic>[]
          : [
              {
                for (final key in teachingSummaryJson().keys)
                  key: current![key],
              },
            ],
      'page': page,
      'last_page': 1,
    });
  }

  @override
  Future<TeachingDraftDetail> generate(TeachingGenerationIntent intent) async {
    guard();
    generations++;
    if (allowanceMessage != null) {
      throw ApiException(
        allowanceMessage!,
        statusCode: 429,
        isApiMessage: true,
      );
    }
    current = teachingDetailJson()..['operation_id'] = intent.operation;
    if (loseGeneration) {
      loseGeneration = false;
      throw ApiException('uncertain', statusCode: 500);
    }
    return delayedGeneration?.future ?? TeachingDraftDetail.fromJson(current!);
  }

  @override
  Future<TeachingDraftDetail> read(String draft) async {
    guard();
    return delayedRead?.future ??
        TeachingDraftDetail.fromJson(current ?? teachingDetailJson());
  }

  @override
  Future<TeachingDraftDetail> receipt(String operation) async {
    guard();
    receiptReads++;
    return TeachingDraftDetail.fromJson(current ?? teachingDetailJson());
  }

  @override
  Future<TeachingDraftMutation> review(TeachingReviewIntent intent) async {
    guard();
    sentReviews.add(intent);
    if (_receipts.containsKey(intent.operation)) {
      return _receipts[intent.operation]!;
    }
    current ??= teachingDetailJson();
    final body = intent.body;
    if (body['expected_revision'] != current!['revision']) {
      throw ApiException('stale', statusCode: 409);
    }
    reviewEffects++;
    final action = body['action'];
    if (action == 'edit') {
      current!['definition'] = jsonDecode(jsonEncode(body['definition']));
    }
    current!['state'] = switch (action) {
      'approve' => 'approved',
      'publish_practice' => 'published',
      'confirm_marks' => 'applied',
      'discard' => 'discarded',
      _ => 'ready',
    };
    if (action == 'publish_practice') publications++;
    if (action == 'confirm_marks') officialMarkWrites++;
    current!['revision'] = (current!['revision'] as int) + 1;
    final result = TeachingDraftMutation.fromJson({
      ...teachingSummaryJson(
        state: current!['state'] as String,
        revision: current!['revision'] as int,
      ),
      'write_operation_id': intent.operation,
    });
    _receipts[intent.operation] = result;
    if (loseReview) {
      loseReview = false;
      throw ApiException('uncertain', statusCode: 500);
    }
    return result;
  }

  @override
  Future<TaskInsightDetail> insights(int group, int task) async {
    guard();
    return TaskInsightDetail.fromJson({
      'school_id': schoolId,
      'reader_id': readerId,
      'id': 1,
      'fingerprint': 'a' * 64,
      'computed_at': '2026-10-26T12:00:00Z',
      'summary': {
        'group_id': group,
        'task_id': task,
        'canonical_task_id': 5,
        'learner_count': 1,
        'has_question_evidence': false,
        'questions': [
          {
            'question_id': 8,
            'number': '1',
            'topic_keys': <String>[],
            'max_mark': '10.00',
            'evidence_count': 0,
            'unknown_count': 1,
            'missed_count': 0,
            'average_mark': null,
          },
        ],
      },
    });
  }

  @override
  Future<TeachingPhotoReceipt> upload(TeachingPhotoIntent intent) async {
    guard();
    uploads.add(intent);
    uploadedBytes = Uint8List.fromList(intent.crop.bytes);
    if (loseUpload) {
      loseUpload = false;
      throw ApiException('uncertain', statusCode: 500);
    }
    return TeachingPhotoReceipt.fromJson({
      'school_id': schoolId,
      'reader_id': readerId,
      'id': teachingPhotoId,
      'sha256': sha256.convert(uploadedBytes!).toString(),
      'mime': 'image/png',
      'operation_id': intent.crop.operation,
    });
  }

  @override
  Future<Uint8List> photo(TeachingPhotoReceipt reference) async {
    guard();
    return Uint8List.fromList(uploadedBytes!);
  }

  @override
  Future<void> revoke(String photo, String operation) async {
    guard();
  }

  @override
  Future<Uint8List> pdf(String draft, {bool answers = false}) async {
    guard();
    return Uint8List.fromList(ascii.encode('%PDF-synthetic'));
  }
}
