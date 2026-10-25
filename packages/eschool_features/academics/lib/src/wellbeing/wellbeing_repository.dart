import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class WellbeingRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<WellbeingAccess> access();
  Future<WellbeingCasePage> cases(int page);
  Future<WellbeingReportPage> reports(int page);
  Future<WellbeingCase> caseRecord(int id, {int? before});
  Future<WellbeingReport> report(int id, {int? before});
  Future<WellbeingCaseChoicePage> professionals(
    int id,
    String date, {
    String search = '',
    int page = 1,
    int? selected,
  });
  Future<WellbeingCaseChoicePage> incidentChoices(
    int id, {
    int page = 1,
    int? selected,
  });
  Future<List<WellbeingAppointment>> appointments(
    int id,
    String from,
    String to,
  );
  Future<void> write(
    String action,
    int id,
    Map<String, dynamic> payload, {
    int? appointment,
  });
}

/// Private clinical data lives only in this reader's authorization generation.
class ApiWellbeingRepository implements WellbeingRepository {
  ApiWellbeingRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId;
  @override
  final int accountId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive)
      throw ApiException(
        'Your clinical access changed. Reopen this area after refreshing your account.',
        statusCode: 403,
        isApiMessage: true,
      );
  }

  Future<dynamic> _send(
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? payload,
  }) async {
    _guard();
    final response = await client.send(
      method: payload == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}wellbeing/$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: payload,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    return response?['data'];
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is! Map) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(value);
  }

  @override
  Future<WellbeingAccess> access() async =>
      WellbeingAccess.fromJson(_map(await _send('access')));
  @override
  Future<WellbeingCasePage> cases(int page) async => WellbeingCasePage.fromJson(
    _map(await _send('cases', query: {'page': page})),
  );
  @override
  Future<WellbeingReportPage> reports(int page) async =>
      WellbeingReportPage.fromJson(
        _map(await _send('reports', query: {'page': page})),
      );
  @override
  Future<WellbeingCase> caseRecord(int id, {int? before}) async {
    final result = WellbeingCase.fromJson(
      _map(
        await _send('cases/$id', query: {if (before != null) 'before': before}),
      ),
    );
    if (result.id != id)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return result;
  }

  @override
  Future<WellbeingReport> report(int id, {int? before}) async {
    final result = WellbeingReport.fromJson(
      _map(
        await _send(
          'reports/$id',
          query: {if (before != null) 'before': before},
        ),
      ),
    );
    if (result.id != id)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return result;
  }

  @override
  Future<WellbeingCaseChoicePage> professionals(
    int id,
    String date, {
    String search = '',
    int page = 1,
    int? selected,
  }) async => WellbeingCaseChoicePage.fromJson(
    _map(
      await _send(
        'cases/$id/professionals',
        query: {
          'on_date': date,
          'page': page,
          if (search.trim().isNotEmpty) 'search': search.trim(),
          if (selected != null) 'selected': selected,
        },
      ),
    ),
  );
  @override
  Future<WellbeingCaseChoicePage> incidentChoices(
    int id, {
    int page = 1,
    int? selected,
  }) async => WellbeingCaseChoicePage.fromJson(
    _map(
      await _send(
        'cases/$id/incident-choices',
        query: {'page': page, if (selected != null) 'selected': selected},
      ),
    ),
  );
  @override
  Future<List<WellbeingAppointment>> appointments(
    int id,
    String from,
    String to,
  ) async {
    final rows = await _send(
      'cases/$id/appointments',
      query: {'from': from, 'to': to},
    );
    if (rows is! List) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    final result = rows
        .map((row) => WellbeingAppointment.fromJson(_map(row)))
        .toList();
    if (result.any((row) => row.supportCaseId != id))
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return result;
  }

  @override
  Future<void> write(
    String action,
    int id,
    Map<String, dynamic> payload, {
    int? appointment,
  }) async {
    if (id <= 0) throw ArgumentError.value(id);
    final path = switch (action) {
      'entry' => 'cases/$id/entries',
      'status' => 'cases/$id/status',
      'link' => 'cases/$id/incident-link',
      'respond' => 'reports/$id/replies',
      'appointment' when appointment == null => 'cases/$id/appointments',
      'appointment' when appointment! > 0 =>
        'cases/$id/appointments/$appointment',
      _ => throw ArgumentError.value(action),
    };
    final receipt = _map(await _send(path, payload: payload));
    if (action == 'appointment' && receipt['support_case_id'] != id ||
        ['status', 'link', 'respond'].contains(action) && receipt['id'] != id ||
        action == 'entry' && receipt['case_revision'] is! int)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  }
}
