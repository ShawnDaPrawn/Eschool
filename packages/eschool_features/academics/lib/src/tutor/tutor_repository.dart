import '../media/private_image_dimensions.dart';
import 'dart:convert';
import 'dart:typed_data';

import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

bool tutorUuid(String value) => RegExp(
  r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[1-8][a-fA-F0-9]{3}-[89aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$',
).hasMatch(value);

/// Frozen exact turn. A transport failure never creates a replacement UUID.
class TutorTurnIntent {
  TutorTurnIntent(this.session, TutorTurnWrite input)
    : operation = input.operationId,
      _body = jsonEncode(input.toJson()) {
    if (!tutorUuid(session) || !tutorUuid(operation)) {
      throw const FormatException('Invalid tutor operation.');
    }
  }
  final String session, operation, _body;
  Map<String, dynamic> get body =>
      Map<String, dynamic>.from(jsonDecode(_body) as Map);
}

class TutorPhotoIntent {
  TutorPhotoIntent({
    required this.session,
    required this.operation,
    required Uint8List bytes,
    required this.x,
    required this.y,
    required this.width,
    required this.height,
    required this.approved,
  }) : bytes = Uint8List.fromList(bytes).asUnmodifiableView() {
    if (!tutorUuid(session) ||
        !tutorUuid(operation) ||
        bytes.isEmpty ||
        bytes.length > 4194304 ||
        x < 0 ||
        y < 0 ||
        width < 1 ||
        height < 1 ||
        !approved) {
      throw const FormatException('Approve the exact working crop first.');
    }
  }
  final String session, operation;
  final Uint8List bytes;
  final int x, y, width, height;
  final bool approved;
  FormData form() => FormData.fromMap({
    'operation_id': operation,
    'approved': approved,
    'crop_x': x,
    'crop_y': y,
    'crop_width': width,
    'crop_height': height,
    'photo': MultipartFile.fromBytes(bytes, filename: 'working.png'),
  });
}

abstract interface class TutorRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<TutorConfiguration> configuration();
  Future<TutorSessionList> sessions({int page = 1});
  Future<TutorSessionResult> create(TutorSessionWrite input);
  Future<TutorSessionDetail> read(String session, {int page = 1});
  Future<TutorTurnResult> send(TutorTurnIntent intent);
  Future<TutorTurnResult> status(String session, String operation);
  Future<TutorSessionResult> close(String session, String operation);
  Future<TutorPhotoReceipt> upload(TutorPhotoIntent intent);
  Future<Uint8List> preview(String session, TutorPhotoReceipt reference);
  Future<void> revoke(String session, String photo, String operation);
}

/// Optional deterministic extension; existing chat repositories stay compatible.
abstract interface class TutorCheckpointRepository {
  Future<TutorCheckpointDetail> issueCheckpoint(
    String session,
    String operation,
  );
  Future<TutorCheckpointDetail> checkpoint(String session, String checkpoint);
  Future<TutorCheckpointDetail> answerCheckpoint(
    String session,
    String checkpoint,
    TutorCheckpointAnswerWrite input,
  );
}

class _TutorClient {
  _TutorClient(this.client, this.schoolId, this.readerId, this.contextActive)
    : token = client.authToken();
  final ApiClient client;
  final int schoolId, readerId;
  final bool Function() contextActive;
  final String token;
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == token);
  void guard() {
    if (!isActive) {
      throw ApiException(
        'Reopen the tutor to check your account and school.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void scope(bool matches) {
    if (!matches)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
  }

  Future<Map<String, dynamic>> send(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    guard();
    final value = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}tutor/$path',
      data: body,
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    guard();
    if (value?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    final result = Map<String, dynamic>.from(value!['data'] as Map);
    scope(result['school_id'] == schoolId && result['reader_id'] == readerId);
    return result;
  }
}

class ApiTutorRepository implements TutorRepository, TutorCheckpointRepository {
  ApiTutorRepository(
    ApiClient client, {
    required int schoolId,
    required int readerId,
    required bool Function() contextActive,
  }) : _client = _TutorClient(client, schoolId, readerId, contextActive);
  final _TutorClient _client;
  int? _learner;
  @override
  int get schoolId => _client.schoolId;
  @override
  int get readerId => _client.readerId;
  @override
  bool get isActive => _client.isActive;
  void _uuid(String id) {
    if (!tutorUuid(id)) throw ArgumentError('Invalid tutor reference.');
  }

  TutorCheckpointDetail _checkpoint(Map<String, dynamic> body, String session) {
    final value = TutorCheckpointDetail.fromJson(body);
    _client.scope(
      value.sessionId == session &&
          (_learner == null || value.learnerId == _learner) &&
          tutorUuid(value.id) &&
          tutorUuid(value.operationId),
    );
    if (value.state.value == 'ready') {
      _client.scope(
        value.correct == null &&
            value.explanation == null &&
            value.answeredAt == null &&
            value.verifiedBy == null,
      );
    } else {
      _client.scope(
        value.state.value == 'answered' &&
            value.correct != null &&
            value.verifiedBy == 'approved_answer_definition',
      );
    }
    return value;
  }

  @override
  Future<TutorCheckpointDetail> issueCheckpoint(
    String session,
    String operation,
  ) async {
    _uuid(session);
    _uuid(operation);
    final value = _checkpoint(
      await _client.send(
        'sessions/$session/checkpoints',
        body: {'operation_id': operation},
      ),
      session,
    );
    _client.scope(value.operationId == operation);
    return value;
  }

  @override
  Future<TutorCheckpointDetail> checkpoint(
    String session,
    String checkpoint,
  ) async {
    _uuid(session);
    _uuid(checkpoint);
    final value = _checkpoint(
      await _client.send('sessions/$session/checkpoints/$checkpoint'),
      session,
    );
    _client.scope(value.id == checkpoint);
    return value;
  }

  @override
  Future<TutorCheckpointDetail> answerCheckpoint(
    String session,
    String checkpoint,
    TutorCheckpointAnswerWrite input,
  ) async {
    _uuid(session);
    _uuid(checkpoint);
    _uuid(input.operationId);
    final value = _checkpoint(
      await _client.send(
        'sessions/$session/checkpoints/$checkpoint/answer',
        body: input.toJson(),
      ),
      session,
    );
    _client.scope(
      value.id == checkpoint &&
          value.answerOperationId == input.operationId &&
          value.state.value == 'answered',
    );
    return value;
  }

  @override
  Future<TutorConfiguration> configuration() async {
    final value = TutorConfiguration.fromJson(
      await _client.send('configuration'),
    );
    _client.scope(_learner == null || value.learnerId == _learner);
    _learner = value.learnerId;
    return value;
  }

  @override
  Future<TutorSessionList> sessions({int page = 1}) async {
    if (page < 1 || page > 1000) throw ArgumentError.value(page);
    final result = TutorSessionList.fromJson(
      await _client.send('sessions', query: {'page': page}),
    );
    _client.scope(
      result.page == page && (_learner == null || result.learnerId == _learner),
    );
    return result;
  }

  @override
  Future<TutorSessionResult> create(TutorSessionWrite input) async {
    _uuid(input.operationId);
    final value = TutorSessionResult.fromJson(
      await _client.send('sessions', body: input.toJson()),
    );
    _client.scope(
      value.groupId == input.groupId &&
          (_learner == null || value.learnerId == _learner),
    );
    return value;
  }

  @override
  Future<TutorSessionDetail> read(String session, {int page = 1}) async {
    _uuid(session);
    if (page < 1 || page > 1000) throw ArgumentError.value(page);
    final result = TutorSessionDetail.fromJson(
      await _client.send('sessions/$session', query: {'page': page}),
    );
    _client.scope(
      result.id == session &&
          result.page == page &&
          (_learner == null || result.learnerId == _learner),
    );
    for (final photo in result.pendingPhotos) {
      _client.scope(photo.schoolId == schoolId && photo.readerId == readerId);
    }
    for (final turn in result.turns) {
      _client.scope(
        tutorUuid(turn.id) && tutorUuid(turn.operationId) && turn.provisional,
      );
      if (const {'quarantined', 'purged'}.contains(turn.state.value)) {
        _client.scope(
          turn.question == null &&
              turn.working == null &&
              turn.response == null,
        );
      }
      if (turn.response != null) {
        _client.scope(
          turn.state.value == 'completed' &&
              const {
                'hint',
                'question',
                'correction',
                'refusal',
              }.contains(turn.response!.kind.value),
        );
      }
    }
    return result;
  }

  TutorTurnResult _turn(Map<String, dynamic> data, String operation) {
    final value = TutorTurnResult.fromJson(data);
    _client.scope(value.operationId == operation && value.provisional);
    if (value.state.value == 'quarantined' || value.state.value == 'purged') {
      _client.scope(
        value.question == null &&
            value.working == null &&
            value.response == null,
      );
    }
    if (value.response != null) {
      _client.scope(
        value.state.value == 'completed' &&
            const {
              'hint',
              'question',
              'correction',
              'refusal',
            }.contains(value.response!.kind.value),
      );
    }
    return value;
  }

  @override
  Future<TutorTurnResult> send(TutorTurnIntent intent) async => _turn(
    await _client.send(
      'sessions/${intent.session}/messages',
      body: intent.body,
    ),
    intent.operation,
  );
  @override
  Future<TutorTurnResult> status(String session, String operation) async {
    _uuid(session);
    _uuid(operation);
    return _turn(
      await _client.send('sessions/$session/turns/$operation'),
      operation,
    );
  }

  @override
  Future<TutorSessionResult> close(String session, String operation) async {
    _uuid(session);
    _uuid(operation);
    final result = TutorSessionResult.fromJson(
      await _client.send(
        'sessions/$session/close',
        body: {'operation_id': operation},
      ),
    );
    _client.scope(result.id == session && result.state.value == 'closed');
    return result;
  }

  @override
  Future<TutorPhotoReceipt> upload(TutorPhotoIntent intent) async {
    final result = TutorPhotoReceipt.fromJson(
      await _client.send(
        'sessions/${intent.session}/photos',
        body: intent.form(),
      ),
    );
    _client.scope(
      result.operationId == intent.operation &&
          result.mime.value == 'image/png',
    );
    return result;
  }

  @override
  Future<Uint8List> preview(String session, TutorPhotoReceipt reference) async {
    _uuid(session);
    _uuid(reference.id);
    _client.guard();
    _client.scope(
      reference.schoolId == schoolId && reference.readerId == readerId,
    );
    final bytes = await _client.client.getBytes(
      url:
          '${AppConfig.apiV2Url}tutor/sessions/$session/photos/${reference.id}',
      schoolId: schoolId,
      accountId: readerId,
      requestTimeout: const Duration(seconds: 20),
    );
    _client.guard();
    _client.scope(
      bytes.length <= privateWorkingMaximumBytes &&
          sha256.convert(bytes).toString() == reference.sha256,
    );
    return bytes;
  }

  @override
  Future<void> revoke(String session, String photo, String operation) async {
    _uuid(session);
    _uuid(photo);
    _uuid(operation);
    final result = TutorPhotoRevocation.fromJson(
      await _client.send(
        'sessions/$session/photos/$photo/revoke',
        body: {'operation_id': operation},
      ),
    );
    _client.scope(result.id == photo && result.revoked);
  }
}

/// Parent code has no learner chat/write methods on this interface.
abstract interface class TutorSummaryRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<ContentLibrary> subjects(int learner, {int page = 1});
  Future<TutorParentSummary> summary(int learner, int subject);
}

class ApiTutorSummaryRepository implements TutorSummaryRepository {
  ApiTutorSummaryRepository(
    ApiClient client, {
    required int schoolId,
    required int readerId,
    required bool Function() contextActive,
  }) : _client = _TutorClient(client, schoolId, readerId, contextActive);
  final _TutorClient _client;
  @override
  int get schoolId => _client.schoolId;
  @override
  int get readerId => _client.readerId;
  @override
  bool get isActive => _client.isActive;
  @override
  Future<ContentLibrary> subjects(int learner, {int page = 1}) async {
    _client.guard();
    if (learner <= 0 || page < 1 || page > 1000)
      throw ArgumentError('Invalid learning summary.');
    final response = await _client.client.send(
      method: 'GET',
      url: '${AppConfig.apiV2Url}academics/content/learners/$learner',
      queryParameters: {'page': page},
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _client.guard();
    if (response?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    final result = ContentLibrary.fromJson(
      Map<String, dynamic>.from(response!['data'] as Map),
    );
    _client.scope(result.learnerId == learner && result.page == page);
    return result;
  }

  @override
  Future<TutorParentSummary> summary(int learner, int subject) async {
    if (learner <= 0 || subject <= 0)
      throw ArgumentError('Invalid learning summary.');
    final result = TutorParentSummary.fromJson(
      await _client.send('parent/learners/$learner/subjects/$subject/summary'),
    );
    _client.scope(
      result.learnerId == learner &&
          result.subjectId == subject &&
          !result.containsRawChat,
    );
    return result;
  }
}
