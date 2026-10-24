import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'annual selection and empty signatures retain explicit nulls without invented approval',
    () {
      final selection = AcademicReportPrepareWrite.fromJson({
        'template_id': 2,
        'session_year_id': 1,
        'term_id': null,
        'class_section_id': 3,
        'learner_ids': [11],
        'mode': 'official',
        'fingerprint': 'a' * 64,
      });
      expect(selection.toJson().containsKey('term_id'), isTrue);
      expect(selection.termId, isNull);
      final signatures = AcademicReportSignatures.fromJson({});
      expect(signatures.toJson(), isEmpty);
      final missing = AcademicReportSubjectResult.fromJson({
        'subject_id': 2,
        'subject_name': 'Mathematics',
        'snapshot_id': null,
        'fingerprint': null,
        'percentage_bp': null,
        'rating_level': null,
        'coverage_bp': 0,
        'complete': false,
        'ready': false,
        'result_status': 'ungraded',
      });
      expect(missing.percentageBp, isNull);
      expect(missing.toJson().containsKey('percentage_bp'), isTrue);
      expect(missing.complete, isFalse);
      final comment = AcademicReportCommentWrite.fromJson({
        'subject_id': null,
        'text': null,
        'revision': 0,
        'batch_revision': 4,
      });
      expect(comment.toJson()['revision'], 0);
      expect(comment.toJson().containsKey('subject_id'), isTrue);
      expect(comment.toJson().containsKey('text'), isTrue);
      expect(comment.toJson().containsKey('bank_id'), isFalse);
    },
  );

  test(
    'private PDF requests preserve requester school expiry signature and binary response',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.test/api/v2'));
      RequestOptions? sent;
      final pdf = Uint8List.fromList('%PDF-synthetic'.codeUnits);
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            sent = request;
            handler.resolve(
              Response<Uint8List>(
                requestOptions: request,
                data: pdf,
                statusCode: 200,
              ),
            );
          },
        ),
      );
      final response = await AcademicsApi(dio).downloadAcademicReport(
        card: 7,
        school: '1',
        reader: '12',
        staff: '0',
        expires: '1790000000',
        signature: 'synthetic-expiring-download',
        xSchoolId: 1,
      );
      expect(response.data, pdf);
      expect(sent!.path, '/academics/reports/cards/7/download');
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.responseType, ResponseType.bytes);
      expect(sent!.queryParameters, {
        'school': '1',
        'reader': '12',
        'staff': '0',
        'expires': '1790000000',
        'signature': 'synthetic-expiring-download',
      });
    },
  );
}
