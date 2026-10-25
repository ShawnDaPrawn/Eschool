import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class CardRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<SecureCardConfiguration> configuration();
  Future<SecureCardPage> mine({int page = 1});
  Future<SecureCardCredential> credential(String id);
  Future<SecureCard> freeze(String id, String operation, String reason);
  Future<Uint8List> portrait(String id);
  Future<SecureCardPortraitReceipt> upload(
    int person,
    String type,
    String operation,
    Uint8List bytes,
  );
}

class ApiCardRepository implements CardRepository {
  ApiCardRepository(
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
        'Your card access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _id(String id) {
    if (!RegExp(
      r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
    ).hasMatch(id)) {
      throw ArgumentError.value(id);
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final response = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}cards$path',
      schoolId: schoolId,
      accountId: accountId,
      data: body,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (response?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  SecureCard _card(SecureCard card, {String? id}) {
    if (card.schoolId != schoolId ||
        card.personId <= 0 ||
        id != null && card.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
    return card;
  }

  @override
  Future<SecureCardConfiguration> configuration() async =>
      SecureCardConfiguration.fromJson(await _send('/configuration'));
  @override
  Future<SecureCardPage> mine({int page = 1}) async {
    if (page <= 0) throw ArgumentError.value(page);
    final result = SecureCardPage.fromJson(
      await _send('/mine', query: {'page': page}),
    );
    for (final card in result.items) {
      _card(card);
    }
    return result;
  }

  @override
  Future<SecureCardCredential> credential(String id) async {
    _id(id);
    final result = SecureCardCredential.fromJson(
      await _send('/$id/credential', body: <String, dynamic>{}),
    );
    _card(result.card, id: id);
    if (result.card.status != 'active' ||
        !result.credential.startsWith('NEQ1.') ||
        result.credential.length > 1024) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }

  @override
  Future<SecureCard> freeze(String id, String operation, String reason) async {
    _id(id);
    return _card(
      SecureCard.fromJson(
        await _send(
          '/$id/freeze',
          body: {'operation_id': operation, 'reason': reason},
        ),
      ),
      id: id,
    );
  }

  @override
  Future<Uint8List> portrait(String id) async {
    _id(id);
    _guard();
    final bytes = await client.getBytes(
      url: '${AppConfig.apiV2Url}cards/$id/portrait',
      schoolId: schoolId,
      accountId: accountId,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (bytes.length < 4 ||
        bytes.length > 4194304 ||
        bytes[0] != 0xff ||
        bytes[1] != 0xd8) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return bytes;
  }

  @override
  Future<SecureCardPortraitReceipt> upload(
    int person,
    String type,
    String operation,
    Uint8List bytes,
  ) async {
    if (person <= 0 || bytes.isEmpty || bytes.length > 4194304) {
      throw ArgumentError('Choose a portrait up to 4 MB.');
    }
    final result = SecureCardPortraitReceipt.fromJson(
      await _send(
        '/portraits',
        body: FormData.fromMap({
          'person_id': person,
          'type': type,
          'operation_id': operation,
          'photo': MultipartFile.fromBytes(bytes, filename: 'portrait.jpg'),
        }),
      ),
    );
    if (result.personId != person || result.revision <= 0) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return result;
  }
}

Future<bool> cardsDeployed(CardRepository repository) async {
  try {
    await repository.configuration();
    return true;
  } on ApiException catch (error) {
    if (const [401, 403, 404].contains(error.statusCode)) return false;
    rethrow;
  }
}
