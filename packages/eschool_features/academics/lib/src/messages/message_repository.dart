import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class SchoolMessageRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<CommunicationConfiguration> configuration({int? year});
  Future<CommunicationMessageList> messages({
    bool drafts = false,
    int page = 1,
  });
  Future<CommunicationMessageReader> reader(int id);
  Future<CommunicationReadReceipt> read(int id);
  Future<CommunicationMessageEditor> editor(int id);
  Future<CommunicationMessageReceipt> save(
    int? id,
    CommunicationMessageWrite input,
  );
  Future<CommunicationMessagePreview> preview(int id);
  Future<CommunicationMessageReceipt> submit(
    int id,
    CommunicationSubmitWrite input,
  );
  Future<CommunicationMessageReceipt> decide(
    int id,
    CommunicationDecisionWrite input,
  );
  Future<CommunicationMessageReceipt> withdraw(
    int id,
    CommunicationWithdrawWrite input,
  );
  Future<CommunicationContactList> contacts(int year, {int page = 1});
  Future<CommunicationAudienceReceipt> audience(
    int? id,
    CommunicationAudienceWrite input,
  );
  Future<CommunicationRuleList> rules();
  Future<CommunicationRoleRule> rule(int id, CommunicationRuleWrite input);
}

class ApiSchoolMessageRepository implements SchoolMessageRepository {
  ApiSchoolMessageRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId;
  @override
  final int readerId;
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
        'Your account or school changed. Reopen School messages.',
        isApiMessage: true,
        statusCode: 403,
      );
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? data,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}communication/$path',
      data: data,
      queryParameters: {...?query, 'activity_groups': 1},
      schoolId: schoolId,
      accountId: readerId,
      requestTimeout: const Duration(seconds: 30),
    );
    _guard();
    final body = result?['data'];
    if (body is! Map) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(body);
  }

  @override
  Future<CommunicationConfiguration> configuration({int? year}) async {
    final config = CommunicationConfiguration.fromJson(
      await _send('configuration', query: {'session_year_id': ?year}),
    );
    if (config.schoolId != schoolId || config.readerId != readerId) {
      throw ApiException('message_scope_changed', statusCode: 403);
    }
    return config;
  }

  @override
  Future<CommunicationMessageList> messages({
    bool drafts = false,
    int page = 1,
  }) async {
    final rows = CommunicationMessageList.fromJson(
      await _send(drafts ? 'drafts' : 'messages', query: {'page': page}),
    );
    if (rows.page != page) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return rows;
  }

  @override
  Future<CommunicationMessageReader> reader(int id) async {
    final row = CommunicationMessageReader.fromJson(
      await _send('messages/$id'),
    );
    if (row.id != id || row.schoolId != schoolId || row.readerId != readerId) {
      throw ApiException('message_scope_changed', statusCode: 403);
    }
    return row;
  }

  @override
  Future<CommunicationReadReceipt> read(int id) async {
    final row = CommunicationReadReceipt.fromJson(
      await _send('messages/$id/read', method: 'POST'),
    );
    if (row.id != id) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return row;
  }

  @override
  Future<CommunicationMessageEditor> editor(int id) async {
    final row = CommunicationMessageEditor.fromJson(await _send('drafts/$id'));
    if (row.id != id) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return row;
  }

  @override
  Future<CommunicationMessageReceipt> save(
    int? id,
    CommunicationMessageWrite input,
  ) async {
    if (utf8.encode(input.markdown).length > 65536 ||
        input.markdown.runes.length > 20000) {
      throw ApiException(
        'Keep the message within 20,000 characters and 64 KiB.',
        isApiMessage: true,
        statusCode: 422,
      );
    }
    final row = CommunicationMessageReceipt.fromJson(
      await _send(
        id == null ? 'drafts' : 'drafts/$id',
        method: id == null ? 'POST' : 'PUT',
        data: input.toJson(),
      ),
    );
    if (id != null && row.id != id) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return row;
  }

  @override
  Future<CommunicationMessagePreview> preview(int id) async {
    final row = CommunicationMessagePreview.fromJson(
      await _send('drafts/$id/preview'),
    );
    if (row.id != id) throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return row;
  }

  @override
  Future<CommunicationMessageReceipt> submit(
    int id,
    CommunicationSubmitWrite input,
  ) async => CommunicationMessageReceipt.fromJson(
    await _send('drafts/$id/submit', method: 'POST', data: input.toJson()),
  );
  @override
  Future<CommunicationMessageReceipt> decide(
    int id,
    CommunicationDecisionWrite input,
  ) async => CommunicationMessageReceipt.fromJson(
    await _send('drafts/$id/decision', method: 'POST', data: input.toJson()),
  );
  @override
  Future<CommunicationMessageReceipt> withdraw(
    int id,
    CommunicationWithdrawWrite input,
  ) async => CommunicationMessageReceipt.fromJson(
    await _send('drafts/$id/withdraw', method: 'POST', data: input.toJson()),
  );
  @override
  Future<CommunicationContactList> contacts(int year, {int page = 1}) async =>
      CommunicationContactList.fromJson(
        await _send('contacts', query: {'session_year_id': year, 'page': page}),
      );
  @override
  Future<CommunicationAudienceReceipt> audience(
    int? id,
    CommunicationAudienceWrite input,
  ) async => CommunicationAudienceReceipt.fromJson(
    await _send(
      id == null ? 'audiences' : 'audiences/$id',
      method: id == null ? 'POST' : 'PUT',
      data: input.toJson(),
    ),
  );
  @override
  Future<CommunicationRuleList> rules() async =>
      CommunicationRuleList.fromJson(await _send('rules'));
  @override
  Future<CommunicationRoleRule> rule(
    int id,
    CommunicationRuleWrite input,
  ) async => CommunicationRoleRule.fromJson(
    await _send('rules/$id', method: 'PUT', data: input.toJson()),
  );
}

class MessageLinkTarget {
  const MessageLinkTarget(this.id);
  final int id;
}

MessageLinkTarget? messageLinkArguments(
  Map<String, String> values,
  Map<String, String> query,
) {
  if (!query.containsKey('message') && !query.containsKey('message_id')) {
    return null;
  }
  if (query.containsKey('message') &&
      query.containsKey('message_id') &&
      query['message'] != query['message_id']) {
    throw const FormatException('Conflicting school message identifiers.');
  }
  final value = query['message'] ?? query['message_id'] ?? '';
  if (!RegExp(r'^[1-9][0-9]{0,14}$').hasMatch(value)) {
    throw const FormatException('Invalid school message link.');
  }
  return MessageLinkTarget(int.parse(value));
}
