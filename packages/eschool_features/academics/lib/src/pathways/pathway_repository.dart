import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class PathwayRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<PathwayConfiguration> configuration();
  Future<PathwayReferencePage> references({int page = 1});
  Future<PathwayReference> read(String id);
  Future<PathwayProjection> project(
    PathwayReference reference,
    PathwayProjectionRequest input,
  );
}

/// Pure private calculations never persist a scenario or change school marks.
class ApiPathwayRepository implements PathwayRepository {
  ApiPathwayRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String _token;
  PathwayConfiguration? _configuration;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _denied() => throw ApiException(
    'Your pathway access changed. Reopen this screen.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _denied();
  }

  void _scope(int school, int reader) {
    _guard();
    if (school != schoolId || reader != accountId) _denied();
  }

  void _uuid(String value) {
    if (!RegExp(
      r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
    ).hasMatch(value)) {
      throw const FormatException('Invalid pathway reference.');
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final response = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}pathways$path',
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      data: body,
      queryParameters: query,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (response?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  @override
  Future<PathwayConfiguration> configuration() async {
    _configuration = null;
    final value = PathwayConfiguration.fromJson(await _send('/configuration'));
    _scope(value.schoolId, value.readerId);
    if (value.children.length > 200 ||
        value.subjects.length > 500 ||
        value.children.any((c) => c.id < 1) ||
        value.children.map((c) => c.id).toSet().length !=
            value.children.length ||
        value.subjects.any((s) => s.id < 1) ||
        value.subjects.map((s) => s.id).toSet().length != value.subjects.length)
      _denied();
    _configuration = value;
    return value;
  }

  void _reference(PathwayReference row) {
    _uuid(row.id);
    final uri = Uri.tryParse(row.sourceUrl);
    if (row.revision < 1 ||
        row.status.value != 'published' ||
        !['programme', 'subject_choice_guide'].contains(row.kind.value) ||
        uri == null ||
        uri.scheme != 'https' ||
        uri.host.isEmpty ||
        uri.userInfo.isNotEmpty ||
        row.title.isEmpty ||
        row.guidance.isEmpty ||
        _configuration == null)
      _denied();
    final rules = row.rules;
    final List<int> ids;
    if (row.kind.value == 'programme') {
      if (rules.subjects == null ||
          rules.subjects!.isEmpty ||
          rules.subjects!.length > 50 ||
          rules.bands == null ||
          rules.bands!.isEmpty ||
          rules.bestN == null ||
          rules.bestN! < 1 ||
          rules.bestN! > 20 ||
          rules.minimumScore == null)
        _denied();
      ids = rules.subjects!.map((s) => s.subjectId).toList();
    } else {
      if (rules.compulsorySubjectIds == null || rules.electiveGroups == null)
        _denied();
      ids = [
        ...rules.compulsorySubjectIds!,
        for (final group in rules.electiveGroups!) ...group.subjectIds,
      ];
    }
    if (ids.toSet().length != ids.length ||
        ids.any((id) => !_configuration!.subjects.any((s) => s.id == id)))
      _denied();
  }

  @override
  Future<PathwayReferencePage> references({int page = 1}) async {
    if (_configuration == null || page < 1 || page > 100000) _denied();
    final result = PathwayReferencePage.fromJson(
      await _send('/references', query: {'page': page}),
    );
    _scope(result.schoolId, result.readerId);
    if (result.items.length > 25 ||
        (result.nextPage != null && result.nextPage != page + 1))
      _denied();
    for (final row in result.items) {
      _reference(row);
    }
    return result;
  }

  @override
  Future<PathwayReference> read(String id) async {
    _uuid(id);
    if (_configuration == null) _denied();
    final result = PathwayReferenceResult.fromJson(
      await _send('/references/$id'),
    );
    _scope(result.schoolId, result.readerId);
    if (result.reference.id != id) _denied();
    _reference(result.reference);
    return result.reference;
  }

  @override
  Future<PathwayProjection> project(
    PathwayReference reference,
    PathwayProjectionRequest input,
  ) async {
    final config = await configuration();
    if (!config.children.any((c) => c.id == input.learnerId)) _denied();
    _reference(reference);
    if (input.revision != reference.revision) _denied();
    final fresh = await read(reference.id);
    if (fresh.revision != reference.revision)
      throw ApiException(
        'This pathway source changed. Reload its requirements.',
        statusCode: 409,
        isApiMessage: true,
      );
    final result = PathwayProjection.fromJson(
      await _send(
        '/references/${reference.id}/projection',
        method: 'POST',
        body: input.toJson(),
      ),
    );
    _scope(result.schoolId, result.readerId);
    if (result.learnerId != input.learnerId ||
        result.referenceId != reference.id ||
        result.revision != reference.revision ||
        result.kind.value != reference.kind.value ||
        result.source_.value != input.source_.value)
      _denied();
    if (reference.kind.value == 'programme') {
      if (result.score == null || result.selection != null) _denied();
      final expected = reference.rules.subjects!
          .map((s) => s.subjectId)
          .toSet();
      final ids = result.score!.subjects.map((s) => s.subjectId).toList();
      if (ids.length != expected.length ||
          ids.toSet().length != ids.length ||
          ids.any((id) => !expected.contains(id)) ||
          result.releasedEvidence.any((e) => !expected.contains(e.subjectId)))
        _denied();
    } else if (result.score != null ||
        result.selection == null ||
        result.releasedEvidence.isNotEmpty) {
      _denied();
    }
    return result;
  }
}
