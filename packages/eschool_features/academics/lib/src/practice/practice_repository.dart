import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

enum PracticeAction { approve, issue, review }

/// A retry uses the exact same operation and payload; every decode is a copy.
class PracticeIntent {
  PracticeIntent.approve(
    int this.group,
    PracticeApproveInput input, {
    this.item,
  }) : action = PracticeAction.approve,
       learner = null,
       set = null,
       entry = null,
       _body = jsonEncode(input.toJson());
  PracticeIntent.issue(
    int this.learner,
    int this.group,
    PracticeIssueInput input,
  ) : action = PracticeAction.issue,
      item = null,
      set = null,
      entry = null,
      _body = jsonEncode(input.toJson());
  PracticeIntent.review(
    int this.learner,
    int this.set,
    int this.entry,
    PracticeReviewInput input,
  ) : action = PracticeAction.review,
      group = null,
      item = null,
      _body = jsonEncode(input.toJson());
  final PracticeAction action;
  final int? group, learner, item, set, entry;
  final String _body;
  Map<String, dynamic> get body =>
      Map<String, dynamic>.from(jsonDecode(_body) as Map);
  Map<String, dynamic> savedApproval() {
    if (action != PracticeAction.approve) {
      throw StateError('Only approvals are stored here.');
    }
    return {'group': group, 'item': item, 'body': body};
  }

  static PracticeIntent restoreApproval(Map<String, dynamic> value) {
    final group = value['group'], item = value['item'];
    if (group is! int ||
        group <= 0 ||
        (item != null && (item is! int || item <= 0)) ||
        value['body'] is! Map) {
      throw const FormatException('Invalid saved approval.');
    }
    final input = PracticeApproveInput.fromJson(
      Map<String, dynamic>.from(value['body'] as Map),
    );
    if (!RegExp(
      r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[1-8][a-fA-F0-9]{3}-[89aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$',
    ).hasMatch(input.operationId)) {
      throw const FormatException('Invalid saved approval identity.');
    }
    return PracticeIntent.approve(group, input, item: item as int?);
  }

  Map<String, dynamic> savedReview() {
    if (action != PracticeAction.review) {
      throw StateError('Only learner reviews can be queued offline.');
    }
    return {'learner': learner, 'set': set, 'entry': entry, 'body': body};
  }

  static PracticeIntent restoreReview(Map<String, dynamic> raw) {
    final learner = raw['learner'], set = raw['set'], entry = raw['entry'];
    if (learner is! int ||
        learner <= 0 ||
        set is! int ||
        set <= 0 ||
        entry is! int ||
        entry <= 0 ||
        raw['body'] is! Map) {
      throw const FormatException('Invalid saved practice review.');
    }
    final body = Map<String, dynamic>.from(raw['body'] as Map);
    final answer = body['answer'],
        rating = body['rating'],
        operation = body['operation_id'];
    if (body.length != 3 ||
        answer is! String ||
        answer.isEmpty ||
        answer.length > 100 ||
        rating is! int ||
        rating < 2 ||
        rating > 4 ||
        operation is! String ||
        !RegExp(
          r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[1-8][a-fA-F0-9]{3}-[89aAbB][a-fA-F0-9]{3}-[a-fA-F0-9]{12}$',
        ).hasMatch(operation)) {
      throw const FormatException('Invalid saved practice review.');
    }
    return PracticeIntent.review(
      learner,
      set,
      entry,
      PracticeReviewInput.fromJson(body),
    );
  }
}

abstract interface class PracticeRepository {
  bool get isActive;
  int get schoolId;
  int get readerId;
  Future<PracticeAccess> access();
  Future<PracticeBankChoices> choices(int group);
  Future<PracticeBankPage> bank(int group, {int page = 1});
  Future<PracticeApproval> approve(PracticeIntent intent);
  Future<PracticeDailySet> issue(PracticeIntent intent);
  Future<PracticeDailySet> daily(int learner, int set);
  Future<PracticeReview> review(PracticeIntent intent);
}

class ApiPracticeRepository implements PracticeRepository {
  ApiPracticeRepository(
    this.client, {
    required this.schoolId,
    required this.readerId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, readerId;
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
        'Refresh school access before opening practice.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _scope(bool valid) {
    if (!valid) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey, statusCode: 403);
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: body == null ? 'GET' : 'POST',
      url: '${AppConfig.apiV2Url}learning/practice/$path',
      data: body,
      queryParameters: query,
      schoolId: schoolId,
      accountId: readerId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (result?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  @override
  Future<PracticeAccess> access() async {
    final result = PracticeAccess.fromJson(await _send('access'));
    _scope(result.readerId == readerId);
    return result;
  }

  @override
  Future<PracticeBankChoices> choices(int group) async {
    final result = PracticeBankChoices.fromJson(
      await _send('groups/$group/choices'),
    );
    _scope(result.groupId == group);
    return result;
  }

  @override
  Future<PracticeBankPage> bank(int group, {int page = 1}) async {
    final result = PracticeBankPage.fromJson(
      await _send('groups/$group/bank', query: {'page': page}),
    );
    _scope(result.page == page);
    return result;
  }

  @override
  Future<PracticeApproval> approve(PracticeIntent intent) async {
    _scope(intent.action == PracticeAction.approve && intent.group != null);
    final result = PracticeApproval.fromJson(
      await _send(
        'groups/${intent.group}/bank${intent.item == null ? '' : '/${intent.item}'}',
        body: intent.body,
      ),
    );
    _scope(intent.item == null || result.id == intent.item);
    return result;
  }

  void _dailyScope(
    PracticeDailySet value,
    int learner, {
    int? set,
    int? group,
  }) {
    _scope(
      value.schoolId == schoolId &&
          value.readerId == readerId &&
          value.learnerId == learner &&
          (set == null || value.id == set) &&
          (group == null || value.groupId == group),
    );
  }

  @override
  Future<PracticeDailySet> issue(PracticeIntent intent) async {
    _scope(
      intent.action == PracticeAction.issue &&
          intent.learner != null &&
          intent.group != null,
    );
    final result = PracticeDailySet.fromJson(
      await _send(
        'learners/${intent.learner}/groups/${intent.group}/daily-sets',
        body: intent.body,
      ),
    );
    _dailyScope(result, intent.learner!, group: intent.group);
    return result;
  }

  @override
  Future<PracticeDailySet> daily(int learner, int set) async {
    final result = PracticeDailySet.fromJson(
      await _send('learners/$learner/daily-sets/$set'),
    );
    _dailyScope(result, learner, set: set);
    return result;
  }

  @override
  Future<PracticeReview> review(PracticeIntent intent) async {
    _scope(
      intent.action == PracticeAction.review &&
          intent.learner != null &&
          intent.set != null &&
          intent.entry != null,
    );
    final result = PracticeReview.fromJson(
      await _send(
        'learners/${intent.learner}/daily-sets/${intent.set}/entries/${intent.entry}/review',
        body: intent.body,
      ),
    );
    _scope(result.entryId == intent.entry);
    return result;
  }
}
