import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class MasteryRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<MasteryConfiguration> configuration();
  Future<MasteryRoster> roster(int group, {int page = 1});
  Future<MasterySubjectList> subjects(int learner);
  Future<MasteryView> staff(int group, int learner, int subject);
  Future<MasteryView> family(int learner, int subject);
}

class ApiMasteryRepository implements MasteryRepository {
  ApiMasteryRepository(
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
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your account or school changed. Reopen Mastery.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  Future<Map<String, dynamic>> _get(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: 'GET',
      url: '${AppConfig.apiV2Url}growth/mastery/$path',
      schoolId: schoolId,
      queryParameters: query,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    final data = result?['data'];
    if (data is! Map) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(data);
  }

  @override
  Future<MasteryConfiguration> configuration() async =>
      MasteryConfiguration.fromJson(await _get('configuration'));
  @override
  Future<MasteryRoster> roster(int group, {int page = 1}) async {
    final result = MasteryRoster.fromJson(
      await _get('groups/$group/learners', query: {'page': page}),
    );
    if (result.groupId != group || result.currentPage != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<MasterySubjectList> subjects(int learner) async {
    final result = MasterySubjectList.fromJson(
      await _get('learners/$learner/subjects'),
    );
    if (result.learnerId != learner) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<MasteryView> staff(int group, int learner, int subject) async =>
      _checked(
        await _get('groups/$group/learners/$learner'),
        learner,
        subject,
        'staff',
      );
  @override
  Future<MasteryView> family(int learner, int subject) async => _checked(
    await _get('learners/$learner', query: {'subject_id': subject}),
    learner,
    subject,
    'family',
  );
  MasteryView _checked(
    Map<String, dynamic> data,
    int learner,
    int subject,
    String audience,
  ) {
    final result = MasteryView.fromJson(data);
    if (result.learnerId != learner ||
        result.subjectId != subject ||
        result.audience.value != audience) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }
}
