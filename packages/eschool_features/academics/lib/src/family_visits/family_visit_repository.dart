import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import '../wellbeing/school_wall_time.dart';

enum FamilyVisitAction {
  book,
  unbook,
  requestPass,
  cancelPass,
  approvePass,
  cancelMeeting,
}

class FamilyVisitMutation {
  FamilyVisitMutation({
    required this.action,
    this.target,
    required Map<String, dynamic> body,
  }) : body = Map.unmodifiable(body);
  final FamilyVisitAction action;
  final String? target;
  final Map<String, dynamic> body;
  Map<String, dynamic> toJson() => {
    'action': action.name,
    'target': target,
    'body': body,
  };
  factory FamilyVisitMutation.fromJson(Map<String, dynamic> data) =>
      FamilyVisitMutation(
        action: FamilyVisitAction.values.byName(data['action'] as String),
        target: data['target'] as String?,
        body: Map<String, dynamic>.from(data['body'] as Map),
      );
}

class FamilyVisitReceipt {
  const FamilyVisitReceipt(this.id, this.status, this.revision);
  final String id, status;
  final int revision;
}

abstract interface class FamilyVisitRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<FamilyVisitConfiguration> configuration();
  Future<FamilyMeetingPage> meetings({int? learnerId, int page = 1});
  Future<FamilyGatePassPage> passes({int? learnerId, int page = 1});
  Future<FamilyVisitReceipt> execute(FamilyVisitMutation intent);
}

/// Normal account transport has no delegated gate-reader/collection capability.
class ApiFamilyVisitRepository implements FamilyVisitRepository {
  ApiFamilyVisitRepository(
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
  FamilyVisitConfiguration? _configuration;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _denied() => throw ApiException(
    'Your meeting access changed. Reopen this screen.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _denied();
  }

  void _uuid(String? value) {
    if (value == null ||
        !RegExp(
          r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
        ).hasMatch(value))
      throw const FormatException('Invalid visit reference.');
  }

  void _page(int page) {
    if (page < 1 || page > 100000)
      throw const FormatException('Invalid visit page.');
  }

  bool _choice(int learner) =>
      _configuration?.children.any((c) => c.id == learner) == true;
  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final answer = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}family-visits$path',
      schoolId: schoolId,
      accountId: accountId,
      data: body,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (answer?['data'] is! Map)
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    return Map<String, dynamic>.from(answer!['data'] as Map);
  }

  @override
  Future<FamilyVisitConfiguration> configuration() async {
    _configuration = null;
    final value = FamilyVisitConfiguration.fromJson(
      await _send('/configuration'),
    );
    if (value.schoolId != schoolId ||
        value.readerId != accountId ||
        value.timezone.isEmpty ||
        value.children.length > 200 ||
        value.hosts.length > 200 ||
        value.children.any((c) => c.id < 1) ||
        value.children.map((c) => c.id).toSet().length !=
            value.children.length ||
        value.hosts.any((h) => h.id < 1) ||
        value.gateRules.staffApprovalRequired != true ||
        value.gateRules.collectorCheckRequired != true)
      _denied();
    final rules = value.gateRules;
    if (rules.maxWindowMinutes < 15 ||
        rules.maxWindowMinutes > 240 ||
        rules.maxFutureDays < 1 ||
        rules.maxFutureDays > 30 ||
        rules.scanMaxAgeSeconds < 15 ||
        rules.scanMaxAgeSeconds > 120)
      _denied();
    _configuration = value;
    return value;
  }

  void _scope(int? learner, {bool pass = false}) {
    _guard();
    if (_configuration == null ||
        (learner != null && !_choice(learner)) ||
        (learner == null &&
            (pass ? !_configuration!.canManage : !_configuration!.canHost)))
      _denied();
  }

  void _slot(FamilyMeetingSlot row, int? learner) {
    _uuid(row.id);
    if (row.hostId < 1 ||
        row.revision < 1 ||
        !['open', 'booked', 'cancelled'].contains(row.status) ||
        row.title.length > 191 ||
        row.venue.length > 500 ||
        !row.startsAt.isUtc ||
        !row.endsAt.isUtc ||
        !row.bookingClosesAt.isUtc ||
        !row.endsAt.isAfter(row.startsAt) ||
        row.bookingClosesAt.isAfter(row.startsAt))
      _denied();
    if (learner != null) {
      if (row.learnerId != null &&
          (row.learnerId != learner || row.guardianUserId != accountId))
        _denied();
      if (row.learnerId == null &&
          (row.guardianUserId != null || row.learnerName != null))
        _denied();
    } else if (_configuration?.canManage != true && row.hostId != accountId) {
      _denied();
    }
  }

  @override
  Future<FamilyMeetingPage> meetings({int? learnerId, int page = 1}) async {
    _scope(learnerId);
    _page(page);
    final value = FamilyMeetingPage.fromJson(
      await _send(
        '/meetings',
        query: {if (learnerId != null) 'learner_id': learnerId, 'page': page},
      ),
    );
    if (value.items.length > 25 ||
        (value.nextPage != null && value.nextPage != page + 1))
      _denied();
    for (final row in value.items) {
      _slot(row, learnerId);
    }
    return value;
  }

  void _pass(FamilyGatePass row, int? learner) {
    _uuid(row.id);
    if (row.learnerId < 1 ||
        row.requestedBy < 1 ||
        row.revision < 1 ||
        ![
          'pending',
          'approved',
          'cancelled',
          'collected',
        ].contains(row.status) ||
        row.collector.isEmpty ||
        row.collector.length > 191 ||
        row.reason.length > 2000 ||
        !row.startsAt.isUtc ||
        !row.endsAt.isUtc ||
        !row.endsAt.isAfter(row.startsAt))
      _denied();
    if (learner != null &&
        (row.learnerId != learner || row.requestedBy != accountId))
      _denied();
    if (['approved', 'collected'].contains(row.status) &&
        row.approvedBy == null)
      _denied();
    if (row.status == 'collected' && row.collectedAt == null) _denied();
  }

  @override
  Future<FamilyGatePassPage> passes({int? learnerId, int page = 1}) async {
    _scope(learnerId, pass: true);
    _page(page);
    final value = FamilyGatePassPage.fromJson(
      await _send(
        '/passes',
        query: {if (learnerId != null) 'learner_id': learnerId, 'page': page},
      ),
    );
    if (value.items.length > 25 ||
        (value.nextPage != null && value.nextPage != page + 1))
      _denied();
    for (final row in value.items) {
      _pass(row, learnerId);
    }
    return value;
  }

  @override
  Future<FamilyVisitReceipt> execute(FamilyVisitMutation intent) async {
    _guard();
    _uuid(intent.body['operation_id'] as String?);
    final config = _configuration;
    if (config == null) _denied();
    final action = intent.action;
    final learner = intent.body['learner_id'];
    if ([
      FamilyVisitAction.book,
      FamilyVisitAction.unbook,
      FamilyVisitAction.requestPass,
    ].contains(action)) {
      if (learner is! int || !_choice(learner)) _denied();
    }
    if ([
          FamilyVisitAction.approvePass,
          FamilyVisitAction.cancelMeeting,
        ].contains(action) &&
        !config.canManage)
      _denied();
    if (action == FamilyVisitAction.cancelPass &&
        !config.canManage &&
        config.children.isEmpty)
      _denied();
    if ([
          FamilyVisitAction.book,
          FamilyVisitAction.requestPass,
          FamilyVisitAction.approvePass,
        ].contains(action) &&
        !config.writable)
      _denied();
    if (action != FamilyVisitAction.requestPass) _uuid(intent.target);
    final path = switch (action) {
      FamilyVisitAction.book => '/meetings/${intent.target}/book',
      FamilyVisitAction.unbook => '/meetings/${intent.target}/unbook',
      FamilyVisitAction.requestPass => '/passes',
      FamilyVisitAction.cancelPass => '/passes/${intent.target}/cancel',
      FamilyVisitAction.approvePass => '/passes/${intent.target}/approve',
      FamilyVisitAction.cancelMeeting => '/meetings/${intent.target}/cancel',
    };
    final body = switch (action) {
      FamilyVisitAction.book || FamilyVisitAction.unbook =>
        FamilyMeetingBooking.fromJson(intent.body).toJson(),
      FamilyVisitAction.requestPass => FamilyGateRequest.fromJson(
        intent.body,
      ).toJson(),
      _ => FamilyVisitRevision.fromJson(intent.body).toJson(),
    };
    final data = await _send(path, method: 'POST', body: body);
    FamilyVisitReceipt receipt;
    if (action == FamilyVisitAction.book) {
      final value = FamilyMeetingSlot.fromJson(data);
      _slot(value, learner as int);
      if (value.status != 'booked' ||
          value.learnerId != learner ||
          value.guardianUserId != accountId)
        _denied();
      receipt = FamilyVisitReceipt(value.id, value.status, value.revision);
    } else if ([
      FamilyVisitAction.requestPass,
      FamilyVisitAction.approvePass,
    ].contains(action)) {
      final value = FamilyGatePass.fromJson(data);
      _pass(
        value,
        action == FamilyVisitAction.requestPass ? learner as int : null,
      );
      if (action == FamilyVisitAction.requestPass &&
              value.status != 'pending' ||
          action == FamilyVisitAction.approvePass &&
              (value.status != 'approved' || value.approvedBy != accountId))
        _denied();
      if (action == FamilyVisitAction.requestPass &&
          (value.collector != (body['collector'] as String).trim() ||
              value.reason != (body['reason'] as String).trim() ||
              !value.startsAt.isAtSameMomentAs(
                SchoolWallTime.utc(
                  body['starts_at'] as String,
                  config.timezone,
                ),
              ) ||
              !value.endsAt.isAtSameMomentAs(
                SchoolWallTime.utc(body['ends_at'] as String, config.timezone),
              ))) {
        _denied();
      }
      receipt = FamilyVisitReceipt(value.id, value.status, value.revision);
    } else {
      final value = FamilyVisitCancelled.fromJson(data);
      if (value.status !=
          (action == FamilyVisitAction.unbook ? 'open' : 'cancelled'))
        _denied();
      receipt = FamilyVisitReceipt(value.id, value.status, value.revision);
    }
    _uuid(receipt.id);
    if (action != FamilyVisitAction.requestPass &&
            receipt.id != intent.target ||
        receipt.revision != (intent.body['revision'] as int? ?? 0) + 1)
      _denied();
    return receipt;
  }
}

Future<bool> familyVisitsDeployed(
  FamilyVisitRepository repository, {
  required bool staffOnly,
}) async {
  final value = await repository.configuration();
  return staffOnly ? value.canHost : value.children.isNotEmpty;
}
