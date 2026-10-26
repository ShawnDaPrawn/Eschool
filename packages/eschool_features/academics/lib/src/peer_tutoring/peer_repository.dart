import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

enum PeerAction { create, complete, cancel, reverse }

class PeerIntent {
  PeerIntent({
    required this.action,
    this.target,
    required Map<String, dynamic> body,
  }) : body = Map.unmodifiable(body);
  final PeerAction action;
  final String? target;
  final Map<String, dynamic> body;
  Map<String, dynamic> toJson() => {
    'action': action.name,
    'target': target,
    'body': body,
  };
  factory PeerIntent.fromJson(Map<String, dynamic> input) => PeerIntent(
    action: PeerAction.values.byName(input['action'] as String),
    target: input['target'] as String?,
    body: Map<String, dynamic>.from(input['body'] as Map),
  );
}

abstract interface class PeerRepository {
  int get schoolId;
  int get accountId;
  String get persona;
  bool get isActive;
  Future<PeerConfiguration> configuration();
  Future<PeerRoster> roster(int group);
  Future<PeerMatchPage> matches(int group, int learner);
  Future<PeerSessionPage> sessions({int page = 1});
  Future<PeerSession> execute(PeerIntent intent);
}

class ApiPeerRepository implements PeerRepository {
  ApiPeerRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.persona,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  @override
  final String persona;
  final bool Function() contextActive;
  final String _token;
  PeerConfiguration? _configuration;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      ['staff', 'student', 'parent'].contains(persona) &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _deny() => throw ApiException(
    'Your peer tutoring access changed. Reopen this screen.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _deny();
  }

  void _scope(int school, int reader) {
    _guard();
    if (school != schoolId || reader != accountId) _deny();
  }

  void _uuid(String? id) {
    if (id == null ||
        !RegExp(
          r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
        ).hasMatch(id)) {
      throw const FormatException('Invalid peer session reference.');
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final response = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}peer-tutoring$path',
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      data: body,
      queryParameters: method == 'GET' ? {'persona': persona, ...?query} : null,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (response?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  @override
  Future<PeerConfiguration> configuration() async {
    _configuration = null;
    final result = PeerConfiguration.fromJson(await _send('/configuration'));
    _scope(result.schoolId, result.readerId);
    if (result.persona.value != persona ||
        result.timezone.isEmpty ||
        result.groups.length > 200 ||
        result.children.length > 200 ||
        result.pointCategories.length > 200 ||
        [
          ...result.groups,
          ...result.children,
        ].any((v) => v.id < 1 || v.name.length > 500) ||
        result.groups.map((v) => v.id).toSet().length != result.groups.length ||
        result.children.map((v) => v.id).toSet().length !=
            result.children.length ||
        (persona != 'staff' &&
            (result.writable ||
                result.canAwardPoints ||
                result.groups.isNotEmpty ||
                result.pointCategories.isNotEmpty)))
      _deny();
    _configuration = result;
    return result;
  }

  void _group(int id) {
    _guard();
    if (persona != 'staff' ||
        _configuration?.groups.any((v) => v.id == id) != true)
      _deny();
  }

  @override
  Future<PeerRoster> roster(int group) async {
    _group(group);
    final result = PeerRoster.fromJson(await _send('/groups/$group/learners'));
    _scope(result.schoolId, result.readerId);
    if (result.teachingGroupId != group ||
        result.items.length > 200 ||
        result.items.any((v) => v.id < 1) ||
        result.items.map((v) => v.id).toSet().length != result.items.length)
      _deny();
    return result;
  }

  @override
  Future<PeerMatchPage> matches(int group, int learner) async {
    _group(group);
    if (learner < 1) _deny();
    final result = PeerMatchPage.fromJson(
      await _send('/groups/$group/learners/$learner/matches'),
    );
    _scope(result.schoolId, result.readerId);
    if (result.teachingGroupId != group ||
        result.learnerId != learner ||
        result.items.length > 50 ||
        result.items.any(
          (v) =>
              v.tuteeLearnerId != learner ||
              v.tutorLearnerId == learner ||
              v.tutorLearnerId < 1 ||
              v.topicId < 1 ||
              v.reason == PeerMatchReasonEnum.unknownDefaultOpenApi,
        ))
      _deny();
    return result;
  }

  void _session(PeerSession row) {
    _uuid(row.id);
    if (row.revision < 1 ||
        row.teachingGroupId < 1 ||
        row.topicId < 1 ||
        row.supervisorUserId < 1 ||
        row.tutorLearnerId < 1 ||
        row.tuteeLearnerId < 1 ||
        row.tutorLearnerId == row.tuteeLearnerId ||
        row.status == PeerSessionStatusEnum.unknownDefaultOpenApi ||
        row.venue.length > 191 ||
        !row.startsAt.isUtc ||
        !row.endsAt.isUtc ||
        !row.endsAt.isAfter(row.startsAt) ||
        row.endsAt.difference(row.startsAt).inMinutes > 180 ||
        (row.verifiedMinutes != null &&
            (row.verifiedMinutes! < 1 || row.verifiedMinutes! > 180)) ||
        (row.serviceMinutes != null &&
            (row.serviceMinutes! < 0 || row.serviceMinutes! > 180)))
      _deny();
    if (persona != 'staff') {
      final own = _configuration?.children.map((c) => c.id).toSet() ?? <int>{};
      final tutor = own.contains(row.tutorLearnerId),
          tutee = own.contains(row.tuteeLearnerId);
      if ((!tutor && !tutee) ||
          row.canComplete ||
          row.canCancel ||
          row.canReverse ||
          row.serviceForReader != tutor ||
          (!tutor && (row.serviceMinutes != null || row.housePointId != null)))
        _deny();
    }
  }

  @override
  Future<PeerSessionPage> sessions({int page = 1}) async {
    _guard();
    if (_configuration == null || page < 1 || page > 100000) _deny();
    final result = PeerSessionPage.fromJson(
      await _send('/sessions', query: {'page': page}),
    );
    _scope(result.schoolId, result.readerId);
    if (result.items.length > 25 ||
        (result.nextPage != null && result.nextPage != page + 1))
      _deny();
    for (final row in result.items) {
      _session(row);
    }
    return result;
  }

  @override
  Future<PeerSession> execute(PeerIntent intent) async {
    _guard();
    if (persona != 'staff' || _configuration == null) _deny();
    _uuid(intent.body['operation_id'] as String?);
    if (intent.body['persona'] != persona) _deny();
    if (intent.action == PeerAction.create) {
      if (intent.target != null) _deny();
      PeerSessionCreate.fromJson(intent.body);
    } else {
      _uuid(intent.target);
      if (intent.action == PeerAction.complete) {
        PeerSessionComplete.fromJson(intent.body);
      } else {
        PeerSessionCorrection.fromJson(intent.body);
      }
    }
    // Fresh authorization is always server-side. A known operation can recover
    // its result after the original mastery/revision/time preconditions changed.
    final path = intent.action == PeerAction.create
        ? '/sessions'
        : '/sessions/${intent.target}/${intent.action.name}';
    final result = PeerSessionResult.fromJson(
      await _send(path, method: 'POST', body: intent.body),
    );
    _scope(result.schoolId, result.readerId);
    _session(result.session);
    if (intent.target != null && result.session.id != intent.target) _deny();
    return result.session;
  }
}
