import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

/// Private person and aggregate school events contain references only.
class RecognitionInvalidation {
  const RecognitionInvalidation({required this.schoolId, this.learnerId});
  final int schoolId;
  final int? learnerId;
}

abstract interface class RecognitionRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Stream<RecognitionInvalidation> get changes;
  Future<RecognitionAccess> access(String module);
  Future<RecognitionConfiguration> configuration(String module);
  Future<RecognitionLearnerPage> learners(
    String module, {
    String search = '',
    int after = 0,
  });
  Future<RecognitionHouseCounters> counters(int year);
  Future<RecognitionPointHistory> points(
    int learner,
    int year, {
    required bool family,
    int? before,
  });
  Future<RecognitionAwardProfile> awards(
    int learner, {
    required bool family,
    int? before,
  });
  Future<RecognitionReference> issue(RecognitionIssue request);
  Future<RecognitionReference> reverse(
    int point,
    String operationId,
    String reason,
  );
}

class ApiRecognitionRepository implements RecognitionRepository {
  ApiRecognitionRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
    this.changes = const Stream.empty(),
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String _token;
  @override
  final Stream<RecognitionInvalidation> changes;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your recognition access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _module(String module) {
    if (!const ['house_points', 'awards'].contains(module)) {
      throw ArgumentError.value(module);
    }
  }

  void _id(int value) {
    if (value <= 0) throw ArgumentError.value(value);
  }

  Future<Map<String, dynamic>> _send(
    String module,
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? body,
  }) async {
    _module(module);
    _guard();
    final result = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}recognition/$module/$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: body,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (result?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  @override
  Future<RecognitionAccess> access(String module) async {
    final data = RecognitionAccess.fromJson(await _send(module, 'access'));
    if (data.accountId != accountId ||
        data.schoolId != schoolId ||
        data.module != module) {
      throw ApiException(
        'Your recognition access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    return data;
  }

  @override
  Future<RecognitionConfiguration> configuration(String module) async {
    final data = RecognitionConfiguration.fromJson(
      await _send(module, 'configuration'),
    );
    if (data.access.accountId != accountId ||
        data.access.schoolId != schoolId ||
        data.access.module != module) {
      throw ApiException(
        'Your recognition access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    return data;
  }

  @override
  Future<RecognitionLearnerPage> learners(
    String module, {
    String search = '',
    int after = 0,
  }) async {
    if (after < 0 || search.length > 100) {
      throw ArgumentError('Invalid learner search.');
    }
    return RecognitionLearnerPage.fromJson(
      await _send(
        module,
        'learners',
        query: {'search': search, 'after': after},
      ),
    );
  }

  @override
  Future<RecognitionHouseCounters> counters(int year) async {
    _id(year);
    final data = RecognitionHouseCounters.fromJson(
      await _send('house_points', 'counters', query: {'session_year_id': year}),
    );
    if (data.sessionYearId != year) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return data;
  }

  @override
  Future<RecognitionPointHistory> points(
    int learner,
    int year, {
    required bool family,
    int? before,
  }) async {
    _id(learner);
    _id(year);
    if (before != null) _id(before);
    return RecognitionPointHistory.fromJson(
      await _send(
        'house_points',
        'learners/$learner/points',
        query: {
          'session_year_id': year,
          'audience': family ? 'family' : 'staff',
          'before': ?before,
        },
      ),
    );
  }

  @override
  Future<RecognitionAwardProfile> awards(
    int learner, {
    required bool family,
    int? before,
  }) async {
    _id(learner);
    if (before != null) _id(before);
    return RecognitionAwardProfile.fromJson(
      await _send(
        'awards',
        'learners/$learner/awards',
        query: {'audience': family ? 'family' : 'staff', 'before': ?before},
      ),
    );
  }

  @override
  Future<RecognitionReference> issue(RecognitionIssue request) async =>
      RecognitionReference.fromJson(
        await _send('house_points', 'points', body: request.toJson()),
      );
  @override
  Future<RecognitionReference> reverse(
    int point,
    String operationId,
    String reason,
  ) async {
    _id(point);
    return RecognitionReference.fromJson(
      await _send(
        'house_points',
        'points/$point/reverse',
        body: RecognitionCorrection(
          operationId: operationId,
          reason: reason,
        ).toJson(),
      ),
    );
  }
}
