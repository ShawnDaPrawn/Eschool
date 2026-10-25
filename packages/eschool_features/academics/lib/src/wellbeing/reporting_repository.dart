import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ReportingRepository {
  bool get isActive;
  Future<WellbeingAccess> access();
  Future<IncidentPublicConfiguration> configuration(String slug);
  Future<IncidentSubmissionReceipt> submit(Map<String, dynamic> input);
  Future<IncidentPublicView> read(String secret, {int? before});
  Future<IncidentPublicView> identify(
    String secret,
    int revision, {
    int? learner,
  });
  Future<void> reply(String secret, Map<String, dynamic> input);
  Future<IncidentPhoto> uploadPhoto(
    String secret,
    Uint8List bytes,
    String operation,
  );
  Uri anonymousForm(String slug);
}

class ApiReportingRepository implements ReportingRepository {
  ApiReportingRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  final int schoolId, accountId;
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
        'Your reporting access changed. Reopen this area after refreshing your account.',
        statusCode: 403,
        isApiMessage: true,
      );
  }

  Future<Map<String, dynamic>> _send(String path, {Object? body}) async {
    _guard();
    final result = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}wellbeing/$path',
      schoolId: schoolId,
      accountId: accountId,
      data: body,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (result?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(result!['data']);
  }

  String _slug(String value) {
    if (!RegExp(r'^[a-z0-9][a-z0-9-]*$').hasMatch(value))
      throw ArgumentError.value(value, 'school');
    return value;
  }

  @override
  Future<WellbeingAccess> access() async =>
      WellbeingAccess.fromJson(await _send('access'));
  @override
  Future<IncidentPublicConfiguration> configuration(String slug) async =>
      IncidentPublicConfiguration.fromJson(
        await _send('public/${_slug(slug)}/configuration'),
      );
  @override
  Future<IncidentSubmissionReceipt> submit(Map<String, dynamic> input) async =>
      IncidentSubmissionReceipt.fromJson(
        await _send(
          'own/reports',
          body: IncidentSubmissionInput.fromJson(input).toJson(),
        ),
      );
  @override
  Future<IncidentPublicView> read(String secret, {int? before}) async =>
      IncidentPublicView.fromJson(
        await _send(
          'own/follow-up',
          body: {'secret': secret, if (before != null) 'before': before},
        ),
      );
  @override
  Future<IncidentPublicView> identify(
    String secret,
    int revision, {
    int? learner,
  }) async => IncidentPublicView.fromJson(
    await _send(
      'own/identify',
      body: {
        'secret': secret,
        'revision': revision,
        'identify_consent': true,
        if (learner != null) 'learner_id': learner,
      },
    ),
  );
  @override
  Future<void> reply(String secret, Map<String, dynamic> input) async {
    final receipt = IncidentReplyReceipt.fromJson(
      await _send('own/follow-up/replies', body: {...input, 'secret': secret}),
    );
    if (receipt.operationId != input['operation_id'] || !receipt.posted)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  }

  @override
  Future<IncidentPhoto> uploadPhoto(
    String secret,
    Uint8List bytes,
    String operation,
  ) async {
    if (bytes.isEmpty || bytes.length > 4194304) {
      throw ApiException(
        'Choose a JPEG or PNG photo up to 4 MB.',
        isApiMessage: true,
      );
    }
    return IncidentPhoto.fromJson(
      await _send(
        'own/photos',
        body: FormData.fromMap({
          'secret': secret,
          'operation_id': operation,
          'photo': MultipartFile.fromBytes(bytes, filename: 'report-photo.jpg'),
        }),
      ),
    );
  }

  @override
  Uri anonymousForm(String slug) {
    _guard();
    final base = Uri.parse(AppConfig.apiV2Url);
    return Uri(
      scheme: base.scheme,
      host: base.host,
      port: base.hasPort ? base.port : null,
      path: '/school/${_slug(slug)}/report',
    );
  }
}
