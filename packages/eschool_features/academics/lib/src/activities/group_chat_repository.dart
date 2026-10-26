import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class GroupChatProvider {
  GroupChatRepository chat(int group);
}

abstract interface class GroupChatRepository {
  int get schoolId;
  int get accountId;
  int get groupId;
  String get persona;
  bool get isActive;
  Future<GroupChatThreadPage> threads({String? before});
  Future<GroupChatConversation> read(String thread, {int? before});
  Future<GroupChatMutation> create(ChatWriteIntent intent);
  Future<GroupChatMutation> send(String thread, ChatWriteIntent intent);
  Future<GroupChatMutation> state(String thread, ChatWriteIntent intent);
  Future<GroupChatMutation> remove(
    String thread,
    int message,
    ChatWriteIntent intent,
  );
  Future<GroupChatReceipt> receipt(String operation);
  Future<void> markRead(String thread, int message);
}

/// A foreground-only frozen write. Erasure removes all private draft fields;
/// only the opaque UUID may survive for a separately authorized outcome lookup.
class ChatWriteIntent {
  ChatWriteIntent(this.operationId, Map<String, dynamic> fields)
    : _fields = Map.unmodifiable(fields);
  final String operationId;
  Map<String, dynamic>? _fields;
  bool get erased => _fields == null;
  Map<String, dynamic> get fields {
    final value = _fields;
    if (value == null) throw StateError('The private draft was retired.');
    return {...value, 'operation_id': operationId};
  }

  void erase() => _fields = null;
}

/// No persisted conversation/body cache. The owning app session supplies its
/// one-way context lease, and every request binds the expected school/account.
class ApiGroupChatRepository implements GroupChatRepository {
  ApiGroupChatRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.groupId,
    required this.persona,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId, groupId;
  @override
  final String persona;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      groupId > 0 &&
      const ['staff', 'parent', 'student'].contains(persona) &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _deny() => throw ApiException(
    'Your conversation access changed. Reopen Activity groups.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _deny();
  }

  String get _base => '${AppConfig.apiV2Url}activities/groups/$groupId/chat';
  bool _uuid(String id) => RegExp(
    r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
  ).hasMatch(id);
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? data,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '$_base$path',
      data: method == 'GET' ? null : {...?data, 'persona': persona},
      queryParameters: method == 'GET' ? {...?query, 'persona': persona} : null,
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (result?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  bool _audience(String value) => switch (persona) {
    'parent' => value == 'guardians',
    'student' => value == 'learners',
    _ => const ['learners', 'guardians', 'staff'].contains(value),
  };
  void _thread(GroupChatThread thread) {
    if (thread.activityGroupId != groupId ||
        !_uuid(thread.id) ||
        thread.revision < 1 ||
        !_audience(thread.audience.value))
      _deny();
  }

  @override
  Future<GroupChatThreadPage> threads({String? before}) async {
    if (before != null && !_uuid(before)) _deny();
    final page = GroupChatThreadPage.fromJson(
      await _send('/threads', query: {if (before != null) 'before': before}),
    );
    if (page.persona.value != persona ||
        page.items.length > 25 ||
        page.maxCharacters < 1 ||
        page.maxCharacters > 10000 ||
        (page.nextBefore != null && !_uuid(page.nextBefore!)))
      _deny();
    for (final row in page.items) {
      if (row.activityGroupId != groupId ||
          !_uuid(row.id) ||
          row.revision < 1 ||
          row.unread < 0 ||
          !_audience(row.audience.value))
        _deny();
    }
    return page;
  }

  @override
  Future<GroupChatConversation> read(String thread, {int? before}) async {
    if (!_uuid(thread) || (before != null && before < 1)) _deny();
    final result = GroupChatConversation.fromJson(
      await _send(
        '/threads/$thread',
        query: {if (before != null) 'before_message_id': before},
      ),
    );
    _thread(result.thread);
    if (result.thread.id != thread ||
        result.items.length > 50 ||
        result.maxCharacters < 1 ||
        result.maxCharacters > 10000 ||
        result.items.map((row) => row.id).toSet().length != result.items.length)
      _deny();
    for (final row in result.items) {
      if (!const [
        'staff',
        'parent',
        'student',
      ].contains(row.senderPersona.value))
        _deny();
      if (persona != 'staff' && row.senderPersona.value != 'staff') {
        final shared = persona == 'parent'
            ? result.policy.guardianSharedReplies
            : result.policy.learnerSharedReplies;
        if (row.senderPersona.value != persona ||
            (row.senderId != accountId && (row.private || !shared)))
          _deny();
      }

      if (row.id < 1 ||
          row.senderId < 1 ||
          row.revision < 1 ||
          (row.removed && row.body != null))
        _deny();
    }
    return result;
  }

  Future<GroupChatMutation> _write(String path, ChatWriteIntent intent) async {
    if (!_uuid(intent.operationId) || intent.erased) _deny();
    final result = GroupChatMutation.fromJson(
      await _send(path, method: 'POST', data: intent.fields),
    );
    if (intent.erased) _deny();
    if (result.activityGroupId != groupId ||
        result.persona.value != persona ||
        !_uuid(result.threadId) ||
        result.revision < 1)
      _deny();
    return result;
  }

  @override
  Future<GroupChatMutation> create(ChatWriteIntent intent) =>
      _write('/threads', intent);
  @override
  Future<GroupChatMutation> send(String thread, ChatWriteIntent intent) {
    if (!_uuid(thread)) _deny();
    return _write('/threads/$thread/messages', intent).then((result) {
      if (result.threadId != thread) _deny();
      return result;
    });
  }

  @override
  Future<GroupChatMutation> state(String thread, ChatWriteIntent intent) {
    if (!_uuid(thread)) _deny();
    return _write('/threads/$thread/state', intent).then((result) {
      if (result.threadId != thread) _deny();
      return result;
    });
  }

  @override
  Future<GroupChatMutation> remove(
    String thread,
    int message,
    ChatWriteIntent intent,
  ) {
    if (!_uuid(thread) || message < 1) _deny();
    return _write('/threads/$thread/messages/$message/remove', intent).then((
      result,
    ) {
      if (result.threadId != thread || result.messageId != message) _deny();
      return result;
    });
  }

  @override
  Future<GroupChatReceipt> receipt(String operation) async {
    if (!_uuid(operation)) _deny();
    final result = GroupChatReceipt.fromJson(
      await _send('/operations/$operation'),
    );
    if (!result.recorded ||
        result.operationId != operation ||
        result.receipt.activityGroupId != groupId ||
        result.receipt.persona.value != persona ||
        !_uuid(result.receipt.threadId))
      _deny();
    return result;
  }

  @override
  Future<void> markRead(String thread, int message) async {
    if (!_uuid(thread) || message < 1) _deny();
    final result = GroupChatReadAck.fromJson(
      await _send(
        '/threads/$thread/read',
        method: 'POST',
        data: {'last_message_id': message},
      ),
    );
    if (result.threadId != thread || result.lastMessageId < message) _deny();
  }
}
