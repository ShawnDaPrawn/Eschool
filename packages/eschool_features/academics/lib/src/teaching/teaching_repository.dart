import 'dart:convert';
import 'dart:typed_data';

import 'package:crypto/crypto.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

import '../tutor/tutor_repository.dart';

/// Immutable human intent survives an uncertain response without replacing its UUID.
class TeachingGenerationIntent {
  TeachingGenerationIntent(this.group, TeachingDraftGeneration input)
    : operation = input.operationId,
      _body = jsonEncode(input.toJson()) {
    if (group < 1 || !tutorUuid(operation)) {
      throw const FormatException('Invalid teaching request.');
    }
  }
  final int group;
  final String operation, _body;
  Map<String, dynamic> get body => jsonDecode(_body) as Map<String, dynamic>;
}

class TeachingReviewIntent {
  TeachingReviewIntent(this.draft, TeachingDraftReview input)
    : operation = input.operationId,
      _body = jsonEncode(input.toJson()) {
    if (!tutorUuid(draft) || !tutorUuid(operation)) {
      throw const FormatException('Invalid teaching review.');
    }
  }
  final String draft, operation, _body;
  Map<String, dynamic> get body => jsonDecode(_body) as Map<String, dynamic>;
}

class TeachingPhotoIntent {
  TeachingPhotoIntent(this.group, this.learner, this.crop) {
    if (group < 1 || learner < 1 || !crop.approved) {
      throw const FormatException('Choose the learner and approved working.');
    }
  }
  final int group, learner;
  final TutorPhotoIntent crop;
}

abstract interface class TeachingRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<TeachingDraftConfiguration> configuration({bool insights = false});
  Future<TeachingDraftChoices> choices(int group, {bool insights = false});
  Future<TeachingLearnerPage> learners(int group, {int page = 1});
  Future<TeachingDraftPage> drafts(int group, {int page = 1});
  Future<TeachingDraftDetail> generate(TeachingGenerationIntent intent);
  Future<TeachingDraftDetail> read(String draft);
  Future<TeachingDraftDetail> receipt(String operation);
  Future<TeachingDraftMutation> review(TeachingReviewIntent intent);
  Future<TaskInsightDetail> insights(int group, int task);
  Future<TeachingPhotoReceipt> upload(TeachingPhotoIntent intent);
  Future<Uint8List> photo(TeachingPhotoReceipt reference);
  Future<void> revoke(String photo, String operation);
  Future<Uint8List> pdf(String draft, {bool answers = false});
}

class ApiTeachingRepository implements TeachingRepository {
  ApiTeachingRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();

  final ApiClient client;
  final bool Function() contextActive;
  final String _token;
  @override
  final int schoolId, readerId;
  @override
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);

  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Reopen the teaching workspace to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _scope(bool valid) {
    if (!valid) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
  }

  void _uuid(String value) {
    if (!tutorUuid(value)) throw ArgumentError('Invalid teaching reference.');
  }

  void _page(int page) {
    if (page < 1 || page > 1000) throw ArgumentError.value(page);
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final response = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}$path',
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      data: body,
      queryParameters: query,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    _scope(response?['data'] is Map);
    final data = Map<String, dynamic>.from(response!['data'] as Map);
    _scope(data['school_id'] == schoolId && data['reader_id'] == readerId);
    return data;
  }

  @override
  Future<TeachingDraftConfiguration> configuration({
    bool insights = false,
  }) async => TeachingDraftConfiguration.fromJson(
    await _send(
      '${insights ? 'test-insights' : 'teaching-assistant'}/configuration',
    ),
  );

  @override
  Future<TeachingDraftChoices> choices(
    int group, {
    bool insights = false,
  }) async {
    if (group < 1) throw ArgumentError.value(group);
    final result = TeachingDraftChoices.fromJson(
      await _send(
        '${insights ? 'test-insights' : 'teaching-assistant'}/groups/$group',
      ),
    );
    _scope(result.groupId == group);
    return result;
  }

  @override
  Future<TeachingLearnerPage> learners(int group, {int page = 1}) async {
    _page(page);
    final result = TeachingLearnerPage.fromJson(
      await _send(
        'teaching-assistant/groups/$group/learners',
        query: {'page': page},
      ),
    );
    _scope(result.groupId == group && result.page == page);
    return result;
  }

  @override
  Future<TeachingDraftPage> drafts(int group, {int page = 1}) async {
    _page(page);
    final result = TeachingDraftPage.fromJson(
      await _send(
        'teaching-assistant/drafts',
        query: {'group_id': group, 'page': page},
      ),
    );
    _scope(result.groupId == group && result.page == page);
    for (final row in result.items) {
      _scope(
        row.groupId == group &&
            row.schoolId == schoolId &&
            row.readerId == readerId,
      );
    }
    return result;
  }

  TeachingDraftDetail _draft(Map<String, dynamic> data) {
    final result = TeachingDraftDetail.fromJson(data);
    _scope(tutorUuid(result.id) && tutorUuid(result.operationId));
    if (result.state.value == 'quarantined') {
      _scope(
        result.request == null &&
            result.definition == null &&
            result.questionLabels.isEmpty,
      );
    }
    if (result.definition != null) {
      _scope(
        const {
          'ready',
          'approved',
          'published',
          'applied',
        }.contains(result.state.value),
      );
      _scope(result.request != null);
    }
    if (result.request != null) {
      _scope(
        result.request!.kind.value == result.kind.value &&
            result.request!.learnerId == result.learnerId &&
            result.request!.taskId == result.taskId,
      );
    }
    return result;
  }

  @override
  Future<TeachingDraftDetail> generate(TeachingGenerationIntent intent) async {
    final result = _draft(
      await _send(
        'teaching-assistant/groups/${intent.group}/drafts',
        body: intent.body,
      ),
    );
    _scope(
      result.groupId == intent.group &&
          result.operationId == intent.operation &&
          result.kind.value == intent.body['kind'] &&
          result.contentVersionId == intent.body['content_version_id'] &&
          result.learnerId == intent.body['learner_id'] &&
          result.taskId == intent.body['task_id'],
    );
    return result;
  }

  @override
  Future<TeachingDraftDetail> read(String draft) async {
    _uuid(draft);
    final result = _draft(await _send('teaching-assistant/drafts/$draft'));
    _scope(result.id == draft);
    return result;
  }

  @override
  Future<TeachingDraftDetail> receipt(String operation) async {
    _uuid(operation);
    final result = _draft(
      await _send('teaching-assistant/operations/$operation'),
    );
    _scope(result.operationId == operation);
    return result;
  }

  @override
  Future<TeachingDraftMutation> review(TeachingReviewIntent intent) async {
    final result = TeachingDraftMutation.fromJson(
      await _send(
        'teaching-assistant/drafts/${intent.draft}/review',
        body: intent.body,
      ),
    );
    _scope(
      result.id == intent.draft && result.writeOperationId == intent.operation,
    );
    return result;
  }

  @override
  Future<TaskInsightDetail> insights(int group, int task) async {
    if (group < 1 || task < 1) {
      throw ArgumentError('Choose the group and task.');
    }
    final result = TaskInsightDetail.fromJson(
      await _send('test-insights/groups/$group/tasks/$task'),
    );
    _scope(result.summary.groupId == group && result.summary.taskId == task);
    return result;
  }

  @override
  Future<TeachingPhotoReceipt> upload(TeachingPhotoIntent intent) async {
    final form = intent.crop.form();
    form.fields.add(MapEntry('learner_id', '${intent.learner}'));
    final result = TeachingPhotoReceipt.fromJson(
      await _send(
        'teaching-assistant/groups/${intent.group}/photos',
        body: form,
      ),
    );
    _scope(
      result.operationId == intent.crop.operation &&
          result.mime.value == 'image/png',
    );
    return result;
  }

  Future<Uint8List> _bytes(String path) async {
    _guard();
    final result = await client.getBytes(
      url: '${AppConfig.apiV2Url}$path',
      schoolId: schoolId,
      accountId: readerId,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    return result;
  }

  @override
  Future<Uint8List> photo(TeachingPhotoReceipt reference) async {
    _uuid(reference.id);
    _scope(reference.schoolId == schoolId && reference.readerId == readerId);
    final result = await _bytes('teaching-assistant/photos/${reference.id}');
    _scope(
      result.length <= 1048576 &&
          sha256.convert(result).toString() == reference.sha256,
    );
    return result;
  }

  @override
  Future<void> revoke(String photo, String operation) async {
    _uuid(photo);
    _uuid(operation);
    final result = TeachingPhotoRevocation.fromJson(
      await _send(
        'teaching-assistant/photos/$photo/revoke',
        body: {'operation_id': operation},
      ),
    );
    _scope(
      result.id == photo &&
          result.revoked &&
          result.writeOperationId == operation,
    );
  }

  @override
  Future<Uint8List> pdf(String draft, {bool answers = false}) async {
    _uuid(draft);
    final result = await _bytes(
      'teaching-assistant/drafts/$draft/pdf?include_answers=${answers ? 1 : 0}',
    );
    _scope(
      result.length >= 5 &&
          result.length < 10485760 &&
          ascii.decode(result.take(5).toList()) == '%PDF-',
    );
    return result;
  }
}
