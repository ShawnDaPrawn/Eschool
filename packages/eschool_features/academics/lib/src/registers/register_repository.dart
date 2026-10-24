import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class RegisterRepository {
  int get schoolId;
  Future<AcademicRegisterChoicesData> choices({String? date, int? year});
  Future<AcademicRegisterMatrixData> open(AcademicRegisterTargetWrite input);
  Future<AcademicRegisterMatrixData> matrix(int register);
  Future<AcademicRegisterSyncData> sync(
    int register,
    AcademicRegisterSyncWrite input,
  );
  Future<AcademicAttendanceTodayData> today(int learner);
  Future<AcademicRegisterQrData> qr(int learner);
  Future<AcademicRegisterQrData> rotate(int learner);
}

class ApiRegisterRepository implements RegisterRepository {
  ApiRegisterRepository(this.client, {required this.schoolId});
  final ApiClient client;
  @override
  final int schoolId;
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? query,
    Object? data,
  }) async {
    final envelope = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}academics/registers/$path',
      schoolId: schoolId,
      queryParameters: query,
      data: data,
    );
    final result = envelope?['data'];
    if (result is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result);
  }

  @override
  Future<AcademicRegisterChoicesData> choices({
    String? date,
    int? year,
  }) async => AcademicRegisterChoicesData.fromJson(
    await _send('choices', query: {'on_date': ?date, 'session_year_id': ?year}),
  );
  @override
  Future<AcademicRegisterMatrixData> open(
    AcademicRegisterTargetWrite input,
  ) async => AcademicRegisterMatrixData.fromJson(
    await _send('open', method: 'POST', data: input.toJson()),
  );
  @override
  Future<AcademicRegisterMatrixData> matrix(int register) async =>
      AcademicRegisterMatrixData.fromJson(await _send('$register'));
  @override
  Future<AcademicRegisterSyncData> sync(
    int register,
    AcademicRegisterSyncWrite input,
  ) async => AcademicRegisterSyncData.fromJson(
    await _send('$register/sync', method: 'POST', data: input.toJson()),
  );
  @override
  Future<AcademicAttendanceTodayData> today(int learner) async =>
      AcademicAttendanceTodayData.fromJson(
        await _send('learners/$learner/today'),
      );
  @override
  Future<AcademicRegisterQrData> qr(int learner) async =>
      AcademicRegisterQrData.fromJson(await _send('learners/$learner/qr'));
  @override
  Future<AcademicRegisterQrData> rotate(int learner) async =>
      AcademicRegisterQrData.fromJson(
        await _send('learners/$learner/qr/rotate', method: 'POST'),
      );
}
