import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

import '../media/private_image_dimensions.dart';
import '../tutor/tutor_repository.dart';

int? examMarkUnits(String text) {
  final value = text.trim();
  if (!RegExp(r'^\d{1,3}(?:\.\d{1,3})?$').hasMatch(value)) return null;
  final parts = value.split('.');
  final units =
      int.parse(parts[0]) * 1000 +
      (parts.length == 1 ? 0 : int.parse(parts[1].padRight(3, '0')));
  return units <= 100000 ? units : null;
}

String examMarkText(int units) {
  final fraction = (units % 1000)
      .toString()
      .padLeft(3, '0')
      .replaceFirst(RegExp(r'0+$'), '');
  return '${units ~/ 1000}${fraction.isEmpty ? '' : '.$fraction'}';
}

/// Exact human intent: retries cannot accidentally replace an operation UUID.
class ExamWriteIntent {
  ExamWriteIntent(this.path, Map<String, dynamic> body)
    : operation = body['operation_id'] as String,
      _body = jsonEncode(body) {
    if (!tutorUuid(operation) || !RegExp(r'^[a-zA-Z0-9/_-]+$').hasMatch(path)) {
      throw const FormatException('Invalid exam operation.');
    }
  }
  final String path, operation, _body;
  Map<String, dynamic> get body => jsonDecode(_body) as Map<String, dynamic>;
}

class ExamPhotoIntent {
  ExamPhotoIntent(this.group, this.version, this.question, this.crop) {
    if (group < 1 ||
        !tutorUuid(version) ||
        question.isEmpty ||
        !crop.approved) {
      throw const FormatException('Approve the question working first.');
    }
  }
  final int group;
  final String version, question;
  final TutorPhotoIntent crop;
  FormData form() => FormData.fromMap({
    'operation_id': crop.operation,
    'question_number': question,
    'approved': true,
    'share_with_teacher': true,
    'crop_x': crop.x,
    'crop_y': crop.y,
    'crop_width': crop.width,
    'crop_height': crop.height,
    'photo': MultipartFile.fromBytes(crop.bytes, filename: 'working.png'),
  });
}

abstract interface class ExamRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<ExamCoachConfiguration> configuration();
  Future<ExamCoachStaffChoices> choices(int group);
  Future<ExamCoachPaperList> papers(
    int group, {
    required bool staff,
    int page = 1,
  });
  Future<ExamCoachPaperVersion> paper(
    int group,
    String version, {
    required bool staff,
  });
  Future<ExamCoachPaperReceipt> writePaper(ExamWriteIntent intent);
  Future<ExamCoachOperationReceipt> operation(int group, String operation);
  Future<Uint8List> pdf(
    int group,
    String version, {
    required bool staff,
    bool memo = false,
  });
  Future<ExamCoachExamDates> dates(int group);
  Future<ExamCoachPlanReceipt> createPlan(int group, ExamCoachPlanWrite input);
  Future<ExamCoachPlanList> plans({int page = 1});
  Future<ExamCoachStudyPlan> plan(String id);
  Future<ExamCoachAnswerList> answers({int? group, int page = 1});
  Future<ExamCoachAnswerDetail> answer(String id, {int? group});
  Future<ExamCoachAnswerDetail> answerOperation(String operation);
  Future<ExamCoachAnswerUploadReceipt> upload(ExamPhotoIntent intent);
  Future<Uint8List> photo(String id, {int? group});
  Future<ExamCoachAnswerDetail> feedback(
    String id,
    ExamCoachAnswerFeedbackWrite input,
  );
  Future<ExamCoachAnswerReviewReceipt> review(
    int group,
    String id,
    ExamWriteIntent intent,
  );
  Future<void> revoke(String id, String operation);
}

class ApiExamRepository implements ExamRepository {
  ApiExamRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, readerId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || _token == client.authToken());
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Reopen Exam Coach to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _check(bool valid) {
    if (!valid) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
  }

  void _id(String value) {
    if (!tutorUuid(value)) {
      throw const FormatException('Invalid exam reference.');
    }
  }

  void _group(int value) {
    if (value < 1) throw ArgumentError.value(value);
  }

  void _page(int value) {
    if (value < 1 || value > 1000) throw ArgumentError.value(value);
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final value = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}exam-coach/$path',
      data: body,
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    _check(value?['data'] is Map);
    return Map<String, dynamic>.from(value!['data'] as Map);
  }

  String _root(int group, bool staff) {
    _group(group);
    return '${staff ? 'staff/' : ''}groups/$group';
  }

  String _answerPath(String id, int? group) {
    _id(id);
    return '${group == null ? '' : '${_root(group, true)}/'}answers/$id';
  }

  @override
  Future<ExamCoachConfiguration> configuration() async {
    final value = ExamCoachConfiguration.fromJson(await _send('configuration'));
    _check(value.readerId == readerId);
    return value;
  }

  @override
  Future<ExamCoachStaffChoices> choices(int group) async {
    final value = ExamCoachStaffChoices.fromJson(
      await _send('${_root(group, true)}/choices'),
    );
    _check(value.groupId == group);
    return value;
  }

  @override
  Future<ExamCoachPaperList> papers(
    int group, {
    required bool staff,
    int page = 1,
  }) async {
    _page(page);
    final value = ExamCoachPaperList.fromJson(
      await _send('${_root(group, staff)}/papers', query: {'page': page}),
    );
    _check(value.groupId == group && value.page == page);
    return value;
  }

  @override
  Future<ExamCoachPaperVersion> paper(
    int group,
    String version, {
    required bool staff,
  }) async {
    _id(version);
    final value = ExamCoachPaperVersion.fromJson(
      await _send('${_root(group, staff)}/versions/$version'),
    );
    _check(
      value.versionId == version &&
          (staff || value.questions.every((q) => q.memo == null)),
    );
    return value;
  }

  @override
  Future<ExamCoachPaperReceipt> writePaper(ExamWriteIntent intent) async {
    if (!RegExp(
      r'^staff/groups/[1-9][0-9]*/papers(?:/[1-9][0-9]*(?:/versions(?:/[a-fA-F0-9-]{36}/publish)?|/withdraw))?$',
    ).hasMatch(intent.path)) {
      throw const FormatException('Invalid paper operation.');
    }
    return ExamCoachPaperReceipt.fromJson(
      await _send(intent.path, body: intent.body),
    );
  }

  @override
  Future<ExamCoachOperationReceipt> operation(
    int group,
    String operation,
  ) async {
    _id(operation);
    final value = ExamCoachOperationReceipt.fromJson(
      await _send('${_root(group, true)}/operations/$operation'),
    );
    _check(value.operationId == operation);
    return value;
  }

  Future<Uint8List> _bytes(String path) async {
    _guard();
    final value = await client.getBytes(
      url: '${AppConfig.apiV2Url}exam-coach/$path',
      schoolId: schoolId,
      accountId: readerId,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    return value;
  }

  @override
  Future<Uint8List> pdf(
    int group,
    String version, {
    required bool staff,
    bool memo = false,
  }) async {
    _id(version);
    if (memo && !staff) throw ArgumentError('Staff memo required.');
    final value = await _bytes(
      '${_root(group, staff)}/versions/$version/${memo ? 'memo' : 'paper'}',
    );
    _check(
      value.length >= 5 &&
          value.length <= 10485760 &&
          ascii.decode(value.take(5).toList()) == '%PDF-',
    );
    return value;
  }

  @override
  Future<ExamCoachExamDates> dates(int group) async {
    final value = ExamCoachExamDates.fromJson(
      await _send('${_root(group, false)}/exams'),
    );
    _check(value.groupId == group);
    return value;
  }

  @override
  Future<ExamCoachPlanReceipt> createPlan(
    int group,
    ExamCoachPlanWrite input,
  ) async => ExamCoachPlanReceipt.fromJson(
    await _send('${_root(group, false)}/plans', body: input.toJson()),
  );
  @override
  Future<ExamCoachPlanList> plans({int page = 1}) async {
    _page(page);
    final value = ExamCoachPlanList.fromJson(
      await _send('plans', query: {'page': page}),
    );
    _check(value.page == page);
    return value;
  }

  @override
  Future<ExamCoachStudyPlan> plan(String id) async {
    _id(id);
    final value = ExamCoachStudyPlan.fromJson(await _send('plans/$id'));
    _check(value.id == id);
    return value;
  }

  @override
  Future<ExamCoachAnswerList> answers({int? group, int page = 1}) async {
    _page(page);
    final value = ExamCoachAnswerList.fromJson(
      await _send(
        '${group == null ? '' : '${_root(group, true)}/'}answers',
        query: {'page': page},
      ),
    );
    _check(
      value.page == page &&
          (group == null || value.answers.every((a) => a.groupId == group)),
    );
    return value;
  }

  @override
  Future<ExamCoachAnswerDetail> answer(String id, {int? group}) async {
    final value = ExamCoachAnswerDetail.fromJson(
      await _send(_answerPath(id, group)),
    );
    _check(value.id == id && (group == null || value.groupId == group));
    return value;
  }

  @override
  Future<ExamCoachAnswerDetail> answerOperation(String operation) async {
    _id(operation);
    final value = ExamCoachAnswerDetail.fromJson(
      await _send('answer-operations/$operation'),
    );
    _check(value.operationId == operation);
    return value;
  }

  @override
  Future<ExamCoachAnswerUploadReceipt> upload(ExamPhotoIntent intent) async {
    final value = ExamCoachAnswerUploadReceipt.fromJson(
      await _send(
        '${_root(intent.group, false)}/versions/${intent.version}/answers',
        body: intent.form(),
      ),
    );
    _check(value.operationId == intent.crop.operation);
    return value;
  }

  @override
  Future<Uint8List> photo(String id, {int? group}) async {
    final value = await _bytes('${_answerPath(id, group)}/photo');
    privateImageDimensions(
      value,
      maximumBytes: privateWorkingMaximumBytes,
      maximumEdge: 768,
      maximumPixels: 768 * 768,
    );
    return value;
  }

  @override
  Future<ExamCoachAnswerDetail> feedback(
    String id,
    ExamCoachAnswerFeedbackWrite input,
  ) async {
    final value = ExamCoachAnswerDetail.fromJson(
      await _send('${_answerPath(id, null)}/feedback', body: input.toJson()),
    );
    _check(value.id == id && value.operationId == input.operationId);
    return value;
  }

  @override
  Future<ExamCoachAnswerReviewReceipt> review(
    int group,
    String id,
    ExamWriteIntent intent,
  ) async {
    final path = '${_answerPath(id, group)}/review';
    if (intent.path != path) throw ArgumentError('Review intent changed.');
    final value = ExamCoachAnswerReviewReceipt.fromJson(
      await _send(path, body: intent.body),
    );
    _check(value.answerId == id);
    return value;
  }

  @override
  Future<void> revoke(String id, String operation) async {
    _id(operation);
    final value = ExamCoachAnswerRevocation.fromJson(
      await _send(
        '${_answerPath(id, null)}/revoke',
        body: {'operation_id': operation},
      ),
    );
    _check(value.id == id && value.state.value == 'purged');
  }
}
