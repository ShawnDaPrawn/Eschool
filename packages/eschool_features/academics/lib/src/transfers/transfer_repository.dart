import 'dart:typed_data';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class TransferRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<TransferAccess> access();
  Future<TransferPage> consents({int page = 1});
  Future<TransferDetail> detail(int id);
  Future<TransferWriteReceipt> write(
    int id,
    String action,
    Map<String, dynamic> payload,
  );
  Future<Uint8List> archive(int id);
}

/// Account-level guardian access does not depend on a selected/approved child.
class ApiTransferRepository implements TransferRepository {
  ApiTransferRepository(
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
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your access changed. Reopen School transfers.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? query,
    Map<String, dynamic>? payload,
  }) async {
    _guard();
    final response = await client.send(
      method: payload == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}transfers/$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      data: payload,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (response?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  @override
  Future<TransferAccess> access() async =>
      TransferAccess.fromJson(await _send('access'));
  @override
  Future<TransferPage> consents({int page = 1}) async =>
      TransferPage.fromJson(await _send('consents', query: {'page': page}));
  @override
  Future<TransferDetail> detail(int id) async {
    if (id <= 0) throw ArgumentError.value(id);
    final row = TransferDetail.fromJson(await _send('consents/$id'));
    if (row.id != id) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return row;
  }

  @override
  Future<TransferWriteReceipt> write(
    int id,
    String action,
    Map<String, dynamic> payload,
  ) async {
    if (id <= 0 || !['consent', 'withdraw'].contains(action)) {
      throw ArgumentError('Choose a guardian transfer action.');
    }
    final receipt = TransferWriteReceipt.fromJson(
      await _send('packages/$id/$action', payload: payload),
    );
    if (receipt.id != id || receipt.kind.value != 'package') {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return receipt;
  }

  @override
  Future<Uint8List> archive(int id) async {
    if (id <= 0) throw ArgumentError.value(id);
    _guard();
    final bytes = await client.getBytes(
      url: '${AppConfig.apiV2Url}transfers/consents/$id/archive',
      schoolId: schoolId,
      accountId: accountId,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    if (bytes.length > 50 * 1024 * 1024 ||
        bytes.length < 4 ||
        bytes[0] != 0x50 ||
        bytes[1] != 0x4b) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return bytes;
  }
}
