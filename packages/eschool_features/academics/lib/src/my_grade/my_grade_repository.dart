import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class MyGradeRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<MyGradeAccess> access();
  Future<MyGradeDashboard> dashboard(
    int grade, {
    String? onDate,
    int page = 1,
    int disciplinePage = 1,
  });
}

/// Each reader belongs to one fresh account/school epoch and private HTTP purpose.
class ApiMyGradeRepository implements MyGradeRepository {
  ApiMyGradeRepository(
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
  int? _year;
  @override
  bool get isActive =>
      schoolId > 0 &&
      readerId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Reopen My grade to check your access.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _scope(bool value) {
    if (!value) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final reply = await client.send(
      method: 'GET',
      url: '${AppConfig.apiV2Url}my-grade/$path',
      schoolId: schoolId,
      accountId: readerId,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (reply?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(reply!['data'] as Map);
  }

  @override
  Future<MyGradeAccess> access() async {
    final result = MyGradeAccess.fromJson(await _send('access'));
    _scope(result.schoolId == schoolId && result.readerId == readerId);
    _year = result.sessionYearId;
    return result;
  }

  @override
  Future<MyGradeDashboard> dashboard(
    int grade, {
    String? onDate,
    int page = 1,
    int disciplinePage = 1,
  }) async {
    if (grade < 1 ||
        page < 1 ||
        page > 1000 ||
        disciplinePage < 1 ||
        disciplinePage > 1000 ||
        (onDate != null && !RegExp(r'^\d{4}-\d{2}-\d{2}$').hasMatch(onDate))) {
      throw ArgumentError('Choose a grade, working date and valid page.');
    }
    final result = MyGradeDashboard.fromJson(
      await _send(
        'grades/$grade',
        query: {
          'on_date': ?onDate,
          'page': page,
          'discipline_page': disciplinePage,
        },
      ),
    );
    _scope(
      result.schoolId == schoolId &&
          result.readerId == readerId &&
          result.gradeLevelId == grade &&
          (_year == null || result.sessionYearId == _year),
    );
    return result;
  }
}
