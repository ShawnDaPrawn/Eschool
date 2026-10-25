import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

class FakeRecognition implements RecognitionRepository {
  @override
  bool isActive = true;
  bool deny = false, writable = true, offlineOnce = false;
  int accessCalls = 0, reads = 0, revision = 1, total = 5;
  @override
  int schoolId = 1, accountId = 12;
  final events = StreamController<RecognitionInvalidation>.broadcast();
  @override
  Stream<RecognitionInvalidation> get changes => events.stream;
  final writes = <Map<String, dynamic>>[];
  final operations = <String>{};
  final entries = <RecognitionPointEntry>[
    RecognitionPointEntry(
      id: 1,
      houseId: 3,
      houseName: 'Mountain',
      categoryName: 'Helping',
      points: 5,
      reason: 'Private existing point evidence',
      onDate: '2026-02-10',
      public: false,
      reversesId: null,
    ),
  ];
  Completer<RecognitionPointHistory>? delayedHistory;
  void guard() {
    if (deny) throw ApiException('Access withdrawn', statusCode: 403);
  }

  @override
  Future<RecognitionAccess> access(String module) async {
    guard();
    accessCalls++;
    return RecognitionAccess(
      module: module,
      schoolId: schoolId,
      accountId: accountId,
      sessionYearId: 7,
      today: '2026-02-10',
      family: false,
      canWrite: writable,
      manager: false,
      gradeIds: [6],
    );
  }

  @override
  Future<RecognitionConfiguration> configuration(String module) async {
    guard();
    return RecognitionConfiguration(
      access: await access(module),
      settings: RecognitionSettings(
        revision: 1,
        dailyStaffCap: 50,
        publicPointsAllowed: false,
        principalApprovalRequired: false,
      ),
      years: [
        RecognitionYear(
          id: 7,
          name: '2026',
          startsOn: '2026-01-01',
          endsOn: '2026-12-31',
        ),
      ],
      grades: [RecognitionNamedReference(id: 6, name: 'Grade five')],
      pointCategories: [
        RecognitionPointCategory(
          id: 4,
          name: 'Helping',
          defaultPoints: 5,
          minimumPoints: -10,
          maximumPoints: 20,
          active: true,
          revision: 1,
        ),
      ],
    );
  }

  @override
  Future<RecognitionLearnerPage> learners(
    String module, {
    String search = '',
    int after = 0,
  }) async {
    guard();
    return RecognitionLearnerPage(
      items: [
        RecognitionLearner(
          id: 2,
          name: 'Synthetic learner',
          gradeLevelId: 6,
          sessionYearId: 7,
        ),
      ],
      nextAfter: null,
    );
  }

  @override
  Future<RecognitionHouseCounters> counters(int year) async {
    guard();
    reads++;
    return RecognitionHouseCounters(
      sessionYearId: year,
      revision: revision,
      items: [
        RecognitionHouseCounter(
          id: 3,
          name: 'Mountain',
          emblem: 'mountain',
          primaryColour: '#315b8c',
          secondaryColour: '#ffffff',
          active: true,
          revision: revision,
          points: total,
        ),
      ],
    );
  }

  @override
  Future<RecognitionPointHistory> points(
    int learner,
    int year, {
    required bool family,
    int? before,
  }) async {
    guard();
    reads++;
    final waiting = delayedHistory;
    delayedHistory = null;
    if (waiting != null) return waiting.future;
    return RecognitionPointHistory(items: List.of(entries), nextBefore: null);
  }

  @override
  Future<RecognitionAwardProfile> awards(
    int learner, {
    required bool family,
    int? before,
  }) async {
    guard();
    reads++;
    return RecognitionAwardProfile(
      items: [
        RecognitionPermanentAward(
          id: 9,
          sessionYearId: 7,
          title: 'Synthetic service award',
          criteria: 'Retained service achievement',
          presentedOn: '2026-02-10',
          event: 'Assembly',
          outsideOrganisation: null,
          sourceKind: 'catalogue',
        ),
      ],
      nextBefore: null,
    );
  }

  @override
  Future<RecognitionReference> issue(RecognitionIssue request) async {
    guard();
    writes.add(Map.of(request.toJson()));
    if (operations.add(request.operationId)) {
      revision++;
      total += request.points;
      entries.insert(
        0,
        RecognitionPointEntry(
          id: revision,
          houseId: 3,
          houseName: 'Mountain',
          categoryName: 'Helping',
          points: request.points,
          reason: request.reason,
          onDate: '2026-02-10',
          public: false,
          reversesId: null,
        ),
      );
    }
    if (offlineOnce) {
      offlineOnce = false;
      throw ApiException('Timeout', isTransportFailure: true);
    }
    return RecognitionReference(id: revision, revision: revision);
  }

  @override
  Future<RecognitionReference> reverse(
    int point,
    String operationId,
    String reason,
  ) async {
    guard();
    return RecognitionReference(id: revision, revision: revision);
  }

  void addMoment() {
    revision++;
    total += 5;
    entries.insert(
      0,
      RecognitionPointEntry(
        id: revision,
        houseId: 3,
        houseName: 'Mountain',
        categoryName: 'Helping',
        points: 5,
        reason: 'New private moment evidence',
        onDate: '2026-02-10',
        public: false,
        reversesId: null,
      ),
    );
    events.add(RecognitionInvalidation(schoolId: schoolId, learnerId: 2));
  }
}
