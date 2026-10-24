import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class ReportRepository {
  int get schoolId;
  Future<AcademicReportConfigurationData> configuration({int? year});
  Future<AcademicReportRosterData> roster(int year, int room, int? term);
  Future<AcademicReportPreviewData> preview(AcademicReportSelection input);
  Future<AcademicReportBatchData> prepare(
    AcademicReportPrepareWrite input,
    String key,
  );
  Future<AcademicReportBatchesData> batches(int year, {int page = 1});
  Future<AcademicReportDetailData> batch(int id);
  Future<AcademicReportSourceData> source(int id);
  Future<AcademicReportReleasePreviewData> releasePreview(int id);
  Future<AcademicReportBatchData> action(
    int id,
    String action,
    AcademicReportActionWrite input,
    String key,
  );
  Future<AcademicReportCommentsData> comments(int year, {int page = 1});
  Future<AcademicReportCommentData> comment(
    int card,
    AcademicReportCommentWrite input,
    String key,
  );
  Future<AcademicReportTemplate> template(
    AcademicReportTemplateWrite input,
    String key,
  );
  Future<AcademicReportSavedRevision> bank(
    AcademicReportCommentBankWrite input,
    String key,
  );
  Future<AcademicReportFamilyData> family(int learner, {int page = 1});
  Future<Uint8List> familyPdf(int card, int learner);
  Future<Uint8List> staffPdf(int batch, int card);
  Future<void> read(int card);
}

class ApiReportRepository implements ReportRepository {
  ApiReportRepository(this.client, {required this.schoolId});
  final ApiClient client;
  @override
  final int schoolId;
  String get _base => '${AppConfig.apiV2Url}academics/reports/';
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? query,
    Object? data,
    String? key,
  }) async {
    final envelope = await client.send(
      method: method,
      url: '$_base$path',
      schoolId: schoolId,
      queryParameters: query,
      data: data,
      idempotencyKey: key,
    );
    final result = envelope?['data'];
    if (result is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result);
  }

  @override
  Future<AcademicReportConfigurationData> configuration({int? year}) async =>
      AcademicReportConfigurationData.fromJson(
        await _send('configuration', query: {'session_year_id': ?year}),
      );
  @override
  Future<AcademicReportRosterData> roster(
    int year,
    int room,
    int? term,
  ) async => AcademicReportRosterData.fromJson(
    await _send(
      'roster',
      query: {
        'session_year_id': year,
        'class_section_id': room,
        'term_id': ?term,
      },
    ),
  );
  @override
  Future<AcademicReportPreviewData> preview(
    AcademicReportSelection input,
  ) async => AcademicReportPreviewData.fromJson(
    await _send('preview', method: 'POST', data: input.toJson()),
  );
  @override
  Future<AcademicReportBatchData> prepare(
    AcademicReportPrepareWrite input,
    String key,
  ) async => AcademicReportBatchData.fromJson(
    await _send('prepare', method: 'POST', data: input.toJson(), key: key),
  );
  @override
  Future<AcademicReportBatchesData> batches(int year, {int page = 1}) async =>
      AcademicReportBatchesData.fromJson(
        await _send('batches', query: {'session_year_id': year, 'page': page}),
      );
  @override
  Future<AcademicReportDetailData> batch(int id) async =>
      AcademicReportDetailData.fromJson(await _send('batches/$id'));
  @override
  Future<AcademicReportSourceData> source(int id) async =>
      AcademicReportSourceData.fromJson(
        await _send('batches/$id/source-preview'),
      );
  @override
  Future<AcademicReportReleasePreviewData> releasePreview(int id) async =>
      AcademicReportReleasePreviewData.fromJson(
        await _send('batches/$id/release-preview'),
      );
  @override
  Future<AcademicReportBatchData> action(
    int id,
    String action,
    AcademicReportActionWrite input,
    String key,
  ) async {
    if (![
      'refresh',
      'grade-sign',
      'principal-sign',
      'release',
      'retry',
    ].contains(action)) {
      throw ArgumentError.value(action);
    }
    return AcademicReportBatchData.fromJson(
      await _send(
        'batches/$id/$action',
        method: 'POST',
        data: input.toJson(),
        key: key,
      ),
    );
  }

  @override
  Future<AcademicReportCommentsData> comments(int year, {int page = 1}) async =>
      AcademicReportCommentsData.fromJson(
        await _send('comments', query: {'session_year_id': year, 'page': page}),
      );
  @override
  Future<AcademicReportCommentData> comment(
    int card,
    AcademicReportCommentWrite input,
    String key,
  ) async => AcademicReportCommentData.fromJson(
    await _send(
      'cards/$card/comment',
      method: 'POST',
      data: input.toJson(),
      key: key,
    ),
  );
  @override
  Future<AcademicReportTemplate> template(
    AcademicReportTemplateWrite input,
    String key,
  ) async => AcademicReportTemplate.fromJson(
    await _send('templates', method: 'PUT', data: input.toJson(), key: key),
  );
  @override
  Future<AcademicReportSavedRevision> bank(
    AcademicReportCommentBankWrite input,
    String key,
  ) async => AcademicReportSavedRevision.fromJson(
    await _send('comment-banks', method: 'PUT', data: input.toJson(), key: key),
  );
  @override
  Future<AcademicReportFamilyData> family(int learner, {int page = 1}) async =>
      AcademicReportFamilyData.fromJson(
        await _send('learners/$learner', query: {'page': page}),
      );
  @override
  Future<void> read(int card) async {
    await _send('cards/$card/read', method: 'POST');
  }

  @override
  Future<Uint8List> familyPdf(int card, int learner) async {
    final access = AcademicReportAccessData.fromJson(
      await _send('cards/$card/access'),
    );
    if (access.id != card || access.learnerId != learner) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return _bytes(card, access.downloadUrl, staff: false);
  }

  @override
  Future<Uint8List> staffPdf(int batchId, int card) async {
    final detail = await batch(batchId);
    final row = detail.cards.where((row) => row.id == card).firstOrNull;
    if (row?.downloadUrl == null) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return _bytes(card, row!.downloadUrl!, staff: true);
  }

  Future<Uint8List> _bytes(
    int card,
    String download, {
    required bool staff,
  }) async {
    final url = validatedReportDownload(
      base: _base,
      download: download,
      school: schoolId,
      card: card,
      staff: staff,
    );
    final bytes = await client.getBytes(url: url, schoolId: schoolId);
    if (bytes.length < 5 || String.fromCharCodes(bytes.take(5)) != '%PDF-') {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return bytes;
  }
}

/// Never send bearer credentials to a URL supplied by report metadata.
/// Retain the signed raw query only after validating the configured origin/path.
String validatedReportDownload({
  required String base,
  required String download,
  required int school,
  required int card,
  required bool staff,
}) {
  final expected = Uri.parse('${base}cards/$card/download');
  final actual = Uri.tryParse(download);
  bool invalid =
      actual == null ||
      !['http', 'https'].contains(actual.scheme) ||
      actual.origin != expected.origin ||
      actual.path != expected.path ||
      actual.userInfo.isNotEmpty ||
      actual.fragment.isNotEmpty;
  if (!invalid) {
    final query = actual.queryParametersAll;
    invalid =
        query.length != 5 ||
        ![
          'school',
          'reader',
          'staff',
          'expires',
          'signature',
        ].every((key) => query[key]?.length == 1) ||
        query['school']!.single != '$school' ||
        query['staff']!.single != (staff ? '1' : '0') ||
        int.tryParse(query['reader']!.single) == null ||
        int.parse(query['reader']!.single) <= 0 ||
        int.tryParse(query['expires']!.single) == null ||
        !RegExp(r'^[a-f0-9]{64}$').hasMatch(query['signature']!.single);
  }
  if (invalid) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  return '${expected.toString()}?${actual!.query}';
}
