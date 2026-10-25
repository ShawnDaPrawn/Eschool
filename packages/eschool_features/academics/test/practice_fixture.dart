import 'package:eschool_academics/eschool_academics.dart';

final practiceTime = DateTime.utc(2026, 10, 25, 9);
PracticeDailySet practiceSet({
  int id = 10,
  int reader = 12,
  int school = 1,
  DateTime? time,
  PracticeReview? review,
}) {
  final now = time ?? practiceTime;
  return PracticeDailySet(
    id: id,
    schoolId: school,
    readerId: reader,
    learnerId: 11,
    groupId: 7,
    practiceOn: '2026-10-25',
    serverTime: now,
    offlineUntil: now.add(const Duration(days: 1)),
    estimatedSeconds: 120,
    completed: review != null,
    streakDays: review == null ? 0 : 1,
    items: [
      PracticeSetEntry(
        id: 20,
        itemId: 21,
        topicId: 22,
        sourceVersionId: 23,
        estimatedSeconds: 120,
        definition: PracticeQuestion(
          kind: PracticeQuestionKindEnum.number,
          prompt: 'One half as a decimal?',
          explanation: 'Divide one by two to get 0.5.',
          options: [],
          answers: ['0.5'],
        ),
        review: review,
      ),
    ],
  );
}

PracticeIntent practiceAnswer({
  String operation = '11111111-1111-4111-8111-111111111111',
  String answer = '.500',
}) => PracticeIntent.review(
  11,
  10,
  20,
  PracticeReviewInput(operationId: operation, answer: answer, rating: 3),
);

class FakePracticeRepository implements PracticeRepository {
  @override
  int schoolId = 1, readerId = 12;
  @override
  bool isActive = true;
  bool writable = true, staff = false;
  Object? error, reviewError;
  DateTime time = practiceTime;
  PracticeReview? confirmed;
  final List<PracticeIntent> writes = [];
  int accessReads = 0, dailyReads = 0;
  PracticeBankItem? approved;
  Future<PracticeApproval> Function(PracticeIntent)? approving;
  @override
  Future<PracticeAccess> access() async {
    accessReads++;
    if (error != null) throw error!;
    return PracticeAccess(
      readerId: readerId,
      learnerId: staff ? null : 11,
      practiceOn: '2026-10-25',
      writable: writable,
      staffGroups: staff
          ? [
              PracticeGroupChoice(
                id: 7,
                name: 'Grade 7',
                subjectName: 'Mathematics',
              ),
            ]
          : [],
      learnerGroups: staff
          ? []
          : [
              PracticeGroupChoice(
                id: 7,
                name: 'Grade 7',
                subjectName: 'Mathematics',
                setId: 10,
              ),
            ],
    );
  }

  @override
  Future<PracticeBankChoices> choices(int group) async => PracticeBankChoices(
    groupId: group,
    topics: [PracticeChoice(id: 22, name: 'Fractions')],
    sources: [PracticeChoice(id: 23, name: 'Approved fractions')],
    writable: writable,
  );
  @override
  Future<PracticeBankPage> bank(int group, {int page = 1}) async =>
      PracticeBankPage(
        items: approved == null ? [] : [approved!],
        page: page,
        lastPage: 1,
      );
  @override
  Future<PracticeApproval> approve(PracticeIntent intent) async {
    writes.add(intent);
    return approving != null
        ? await approving!(intent)
        : PracticeApproval(id: 21, versionId: 30, revision: 1, active: true);
  }

  @override
  Future<PracticeDailySet> issue(PracticeIntent intent) async {
    writes.add(intent);
    if (error != null) throw error!;
    return practiceSet(time: time, review: confirmed);
  }

  @override
  Future<PracticeDailySet> daily(int learner, int set) async {
    dailyReads++;
    if (error != null) throw error!;
    return practiceSet(id: set, time: time, review: confirmed);
  }

  @override
  Future<PracticeReview> review(PracticeIntent intent) async {
    writes.add(intent);
    if (reviewError != null) throw reviewError!;
    return confirmed ??= PracticeReview(
      id: 1,
      entryId: intent.entry!,
      correct: true,
      reviewedOn: '2026-10-25',
      schedule: PracticeSchedule(
        version: 'synthetic-scheduler',
        rating: 3,
        difficulty: 4,
        stabilityDays: 2,
        retrievabilityBefore: null,
        intervalDays: 2,
      ),
    );
  }
}
