import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

enum AssistanceWrite {
  open,
  reply,
  close,
  claim,
  reassign,
  publish,
  withdraw,
  answer,
  translation,
  officeTranslation,
  run,
}

abstract interface class AssistanceRepository {
  int get schoolId;
  int get readerId;
  bool get isActive;
  Future<SchoolAssistanceConfiguration> configuration();
  Future<SchoolAssistanceDocumentPage> documents(
    int learner, {
    int page = 1,
    bool office = false,
  });
  Future<SchoolAssistanceDocumentBody> document(String id, {int? learner});
  Future<SchoolAssistanceThreadPage> threads(int learner, {int page = 1});
  Future<SchoolAssistanceOfficeQueue> queue({int page = 1});
  Future<SchoolAssistanceThread> thread(
    String id, {
    required bool office,
    int page = 1,
  });
  Future<SchoolAssistanceStaffChoices> staff();
  Future<SchoolAssistanceTurn> turn(String id);
  Future<Map<String, dynamic>> write(
    AssistanceWrite action,
    Map<String, dynamic> body, {
    int? learner,
    String? reference,
  });
}

class ApiAssistanceRepository implements AssistanceRepository {
  ApiAssistanceRepository(
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
        'Reopen school assistance to check your account and school.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _uuid(String? id) {
    if (id == null ||
        !RegExp(
          r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[1-8][a-fA-F0-9]{3}-[89aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$',
        ).hasMatch(id)) {
      throw ArgumentError('Invalid school support reference.');
    }
  }

  int _learner(int? learner) {
    if (learner == null || learner < 1) {
      throw ArgumentError('Select a current child.');
    }
    return learner;
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final reply = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}school-assistance/$path',
      data: body == null ? null : jsonDecode(jsonEncode(body)),
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (reply?['data'] is! Map) {
      throw const FormatException('Invalid school assistance response.');
    }
    final data = Map<String, dynamic>.from(reply!['data'] as Map);
    if (data['school_id'] != schoolId || data['reader_id'] != readerId) {
      throw ApiException(
        'Your school or account changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    return data;
  }

  @override
  Future<SchoolAssistanceConfiguration> configuration() async =>
      SchoolAssistanceConfiguration.fromJson(await _send('configuration'));
  @override
  Future<SchoolAssistanceDocumentPage> documents(
    int learner, {
    int page = 1,
    bool office = false,
  }) async {
    final path = office
        ? 'office/documents'
        : 'family/learners/${_learner(learner)}/documents';
    final result = SchoolAssistanceDocumentPage.fromJson(
      await _send(path, query: {'page': page}),
    );
    if (!office &&
        result.items.any((document) => document.state.value != 'published')) {
      throw const FormatException('An unpublished school source was returned.');
    }
    return result;
  }

  @override
  Future<SchoolAssistanceDocumentBody> document(
    String id, {
    int? learner,
  }) async {
    _uuid(id);
    final data = SchoolAssistanceDocumentBody.fromJson(
      await _send(
        learner == null
            ? 'office/documents/$id'
            : 'family/learners/${_learner(learner)}/documents/$id',
      ),
    );
    if (data.id != id || (learner != null && data.state.value != 'published')) {
      throw const FormatException('The school document changed.');
    }
    return data;
  }

  @override
  Future<SchoolAssistanceThreadPage> threads(
    int learner, {
    int page = 1,
  }) async => SchoolAssistanceThreadPage.fromJson(
    await _send(
      'family/learners/${_learner(learner)}/threads',
      query: {'page': page},
    ),
  );
  @override
  Future<SchoolAssistanceOfficeQueue> queue({int page = 1}) async =>
      SchoolAssistanceOfficeQueue.fromJson(
        await _send('office/queue', query: {'page': page}),
      );
  @override
  Future<SchoolAssistanceThread> thread(
    String id, {
    required bool office,
    int page = 1,
  }) async {
    _uuid(id);
    final data = SchoolAssistanceThread.fromJson(
      await _send(
        'threads/$id',
        query: {'persona': office ? 'office' : 'parent', 'page': page},
      ),
    );
    if (data.id != id) {
      throw const FormatException('The school-support thread changed.');
    }
    return data;
  }

  @override
  Future<SchoolAssistanceStaffChoices> staff() async =>
      SchoolAssistanceStaffChoices.fromJson(await _send('office/staff'));
  @override
  Future<SchoolAssistanceTurn> turn(String id) async {
    _uuid(id);
    final data = SchoolAssistanceTurn.fromJson(await _send('turns/$id'));
    if (data.id != id) {
      throw const FormatException('The reviewed question changed.');
    }
    return data;
  }

  @override
  Future<Map<String, dynamic>> write(
    AssistanceWrite action,
    Map<String, dynamic> body, {
    int? learner,
    String? reference,
  }) async {
    if ([
      AssistanceWrite.reply,
      AssistanceWrite.close,
      AssistanceWrite.claim,
      AssistanceWrite.reassign,
      AssistanceWrite.withdraw,
      AssistanceWrite.run,
    ].contains(action)) {
      _uuid(reference);
    }
    final path = switch (action) {
      AssistanceWrite.open => 'family/learners/${_learner(learner)}/threads',
      AssistanceWrite.reply => 'threads/$reference/reply',
      AssistanceWrite.close => 'threads/$reference/close',
      AssistanceWrite.claim => 'office/threads/$reference/claim',
      AssistanceWrite.reassign => 'office/threads/$reference/reassign',
      AssistanceWrite.publish => 'office/documents',
      AssistanceWrite.withdraw => 'office/documents/$reference/withdraw',
      AssistanceWrite.answer => 'family/learners/${_learner(learner)}/answers',
      AssistanceWrite.translation =>
        'family/learners/${_learner(learner)}/translations',
      AssistanceWrite.officeTranslation =>
        'office/learners/${_learner(learner)}/translations',
      AssistanceWrite.run => 'turns/$reference/run',
    };
    return _send(path, body: body);
  }
}
