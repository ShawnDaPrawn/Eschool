import 'dart:async';

import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_academics/src/risk/risk_repository.dart';
import 'package:eschool_academics/src/risk/risk_screen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> riskMeta({
  int id = 9,
  int owner = 12,
  int revision = 1,
  String state = 'active',
}) => {
  'id': id,
  'learner_id': 11,
  'session_year_id': 7,
  'grade_level_id': 5,
  'owner_authority': owner == 12 ? 'academic' : 'professional',
  'owner_id': owner,
  'started_on': '2026-02-10',
  'review_on': '2026-02-20',
  'state': state,
  'revision': revision,
};

class RiskFake implements RiskRepository {
  RiskFake({this.principal = false, this.empty = false});
  int? ownCaseCount;
  final bool principal, empty;
  bool? writable = true;
  @override
  int schoolId = 1, readerId = 12;
  @override
  bool isActive = true;
  int bodies = 0,
      creates = 0,
      updates = 0,
      transfers = 0,
      revision = 1,
      configurationCalls = 0;
  bool unknownOnce = false,
      conflictOnce = false,
      denyQueue = false,
      paged = false;
  Completer<InterventionDetail>? bodyWait;
  final intents = <Map<String, dynamic>>[];
  @override
  Future<RiskConfiguration> configuration() async {
    configurationCalls++;
    return RiskConfiguration.fromJson({
      'session_year_id': 7,
      if (writable != null) 'writable': writable,
      'today': '2026-02-10',
      'can_manage': principal,
      'can_read_signals': true,
      'grade_ids': principal ? [5] : [],
      'settings': {
        'academic_threshold_bp': 6500,
        'minimum_coverage_bp': 2500,
        'absence_threshold_bp': 1000,
        'minimum_registers': 10,
        'revision': 0,
        'rule_version': 'observed-counts-v1',
      },
      'years': [
        {'id': 7, 'name': '2026'},
      ],
      'groups': [
        {
          'id': 3,
          'name': 'Blue group',
          'subject_id': 4,
          'subject_name': 'Mathematics',
          'grade_name': 'Grade 5',
          'session_year_id': 7,
        },
      ],
    });
  }

  @override
  Future<InterventionQueue> queue(int year, {int page = 1}) async {
    if (denyQueue) throw ApiException('Scope withdrawn', statusCode: 403);
    return InterventionQueue.fromJson({
      'session_year_id': year,
      'page': page,
      'last_page': 1,
      'interventions': empty
          ? []
          : [
              {
                'id': principal ? 10 : 9,
                'learner_id': 11,
                'learner_name': 'Synthetic learner',
                'owner_id': principal ? 13 : 12,
                'owner_name': principal ? 'Assigned professional' : 'Own staff',
                'grade_level_id': 5,
                'review_on': '2026-02-20',
                'state': 'active',
                'revision': revision,
                'can_read_body': !principal,
              },
            ],
    });
  }

  @override
  Future<RiskLearnerChoices> learners(
    int year, {
    int page = 1,
    String? search,
  }) async => RiskLearnerChoices.fromJson({
    'session_year_id': year,
    'page': page,
    'last_page': paged ? 2 : 1,
    'learners': [
      {
        'learner_id': page == 1 ? 11 : 21,
        'name': page == 1 ? 'Synthetic learner' : 'Another eligible learner',
      },
    ],
  });
  @override
  Future<RiskOwnerChoices> owners(
    int learner,
    int year, {
    int? intervention,
    int page = 1,
    String? search,
  }) async => RiskOwnerChoices.fromJson({
    'page': page,
    'last_page': paged ? 2 : 1,
    'owners': intervention != null
        ? [
            {
              'id': 13,
              'name': 'Assigned professional',
              'authority': 'professional',
            },
            {'id': 15, 'name': 'New professional', 'authority': 'professional'},
          ]
        : [
            {
              'id': page == 1 ? 12 : 22,
              'name': page == 1 ? 'Own staff' : 'Another eligible staff member',
              'authority': 'academic',
            },
          ],
  });
  @override
  Future<InterventionDetail> detail(int id) async {
    bodies++;
    if (bodyWait != null) return bodyWait!.future;
    return InterventionDetail.fromJson({
      ...riskMeta(id: id, revision: revision),
      'action': revision > 1
          ? 'A newer saved action'
          : 'Private owned intervention',
      'outcome': null,
    });
  }

  @override
  Future<InterventionHistory> history(int id) async =>
      InterventionHistory.fromJson({
        'intervention_id': id,
        'versions': [
          {
            'revision': 1,
            'created_at': '2026-02-10T12:00:00Z',
            'actor_id': 12,
            'record': {
              'kind': 'record',
              'action': 'Private old action',
              'outcome': null,
              'review_on': '2026-02-20',
              'state': 'active',
            },
          },
        ],
      });
  @override
  Future<InterventionMetadata> create(
    int learner,
    InterventionCreate input,
  ) async {
    creates++;
    intents.add(input.toJson());
    if (unknownOnce && creates == 1) {
      throw ApiException('Network outcome unknown', statusCode: 0);
    }
    return InterventionMetadata.fromJson(riskMeta());
  }

  @override
  Future<InterventionMetadata> update(int id, InterventionUpdate input) async {
    updates++;
    intents.add(input.toJson());
    if (conflictOnce && updates == 1) {
      revision = 2;
      throw ApiException('Current revision changed', statusCode: 409);
    }
    revision++;
    return InterventionMetadata.fromJson(riskMeta(revision: revision));
  }

  @override
  Future<InterventionMetadata> transfer(
    int id,
    InterventionOwnerWrite input,
  ) async {
    transfers++;
    intents.add(input.toJson());
    if (conflictOnce && transfers == 1) {
      revision = 2;
      throw ApiException('Metadata changed', statusCode: 409);
    }
    revision++;
    return InterventionMetadata.fromJson(
      riskMeta(id: id, owner: input.ownerId, revision: revision),
    );
  }

  @override
  Future<RiskSignals> signals(int year, {int page = 1}) async =>
      RiskSignals.fromJson({
        'session_year_id': year,
        'page': page,
        'last_page': 1,
        'learners': [
          {
            'learner_id': 11,
            'name': 'Synthetic learner',
            'signals': [
              {
                'source': 'wellbeing',
                'score': null,
                'signal_count': ownCaseCount,
                'reason_code': ownCaseCount == null
                    ? 'source_unavailable'
                    : 'own_authorised_active_cases',
                'grade_level_id': 5,
                'as_of': '2026-02-10',
              },
            ],
          },
        ],
      });
  @override
  Future<RiskClassroom> classroom(int group) async =>
      RiskClassroom.fromJson({'group_id': group, 'learners': []});
  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<void> frameRisk(
  WidgetTester tester,
  RiskFake repo, {
  double width = 820,
  bool Function()? canRead,
}) async {
  tester.view.physicalSize = Size(width, 1100);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(
      theme: ThemeData(useMaterial3: true),
      home: RiskInterventionsScreen(
        repository: repo,
        canRead: canRead ?? () => true,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tapRisk(WidgetTester tester, String text) async {
  final labels = find.text(text);
  if (labels.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      labels,
      200,
      scrollable: find.byType(Scrollable).first,
    );
  }
  final label = labels.last;
  final buttons = find.ancestor(
    of: label,
    matching: find.byWidgetPredicate((widget) => widget is ButtonStyleButton),
  );
  final target = buttons.evaluate().isEmpty ? label : buttons.last;
  await Scrollable.ensureVisible(tester.element(target), alignment: .5);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Finder field(String label) => find.widgetWithText(TextField, label);
Future<void> chooseRiskLearner(WidgetTester tester) async {
  final select = find.byType(DropdownButtonFormField<int>).last;
  await tester.ensureVisible(select);
  await tester.tap(select);
  await tester.pumpAndSettle();
  await tester.tap(find.text('Synthetic learner').last);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('owned private intervention review fits $width', (
      tester,
    ) async {
      final repo = RiskFake();
      await frameRisk(tester, repo, width: width);
      await tapRisk(tester, 'Open your intervention');
      expect(find.text('Private owned intervention'), findsWidgets);
      expect(repo.bodies, 1);
      expect(tester.takeException(), isNull);
      await tester.enterText(field('Action to take'), 'Reviewed owner action');
      await tapRisk(tester, 'Save review');
      expect(repo.updates, 1);
      expect(find.text('Private owned intervention'), findsNothing);
    });
  }
  for (final writable in [false, null]) {
    testWidgets(
      'readonly or absent write capability retains owned reads ($writable)',
      (tester) async {
        final repo = RiskFake()..writable = writable;
        await frameRisk(tester, repo);
        await tapRisk(tester, 'Open your intervention');
        expect(find.text('Private owned intervention'), findsWidgets);
        expect(
          find.text('Follow-up is read-only. Saved records remain available.'),
          findsOneWidget,
        );
        expect(
          tester.widget<TextField>(field('Action to take')).enabled,
          isFalse,
        );
        final save = find.widgetWithText(FilledButton, 'Save review');
        await tester.ensureVisible(save);
        expect(tester.widget<FilledButton>(save).onPressed, isNull);
        expect(repo.updates, 0);
      },
    );
  }
  testWidgets(
    'unknown create retries exact immutable UUID and school-calendar dates',
    (tester) async {
      final repo = RiskFake(empty: true)..unknownOnce = true;
      await frameRisk(tester, repo);
      await chooseRiskLearner(tester);
      await tester.enterText(
        field('Action to take'),
        'Own private planned action',
      );
      await tapRisk(tester, 'Create intervention');
      expect(repo.creates, 1);
      expect(
        tester.widget<TextField>(field('Action to take')).enabled,
        isFalse,
      );
      await tapRisk(tester, 'Retry the same change');
      expect(repo.creates, 2);
      expect(repo.intents[0], repo.intents[1]);
      expect(repo.intents[0]['started_on'], '2026-02-10');
    },
  );
  testWidgets(
    'stale revision preserves draft and needs explicit current comparison',
    (tester) async {
      final repo = RiskFake()..conflictOnce = true;
      await frameRisk(tester, repo);
      await tapRisk(tester, 'Open your intervention');
      await tester.enterText(
        field('Action to take'),
        'My independent stale draft',
      );
      await tapRisk(tester, 'Save review');
      expect(repo.updates, 1);
      expect(
        tester.widget<TextField>(field('Action to take')).controller!.text,
        'My independent stale draft',
      );
      expect(find.text('A newer saved action'), findsWidgets);
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pumpAndSettle();
      await tapRisk(tester, 'Save review');
      expect(repo.updates, 2);
      expect(repo.intents[1]['expected_revision'], 2);
      expect(
        repo.intents[1]['operation_id'],
        isNot(repo.intents[0]['operation_id']),
      );
      expect(repo.intents[1]['action'], 'My independent stale draft');
    },
  );
  testWidgets(
    'principal handoff fetches metadata only and refreshes conflict revision',
    (tester) async {
      final repo = RiskFake(principal: true)..conflictOnce = true;
      await frameRisk(tester, repo);
      expect(repo.bodies, 0);
      await tapRisk(tester, 'Change the assigned owner');
      expect(repo.bodies, 0);
      await tester.ensureVisible(
        find.byType(DropdownButtonFormField<int>).last,
      );
      await tester.tap(find.byType(DropdownButtonFormField<int>).last);
      await tester.pumpAndSettle();
      await tester.tap(find.text('New professional').last);
      await tester.pumpAndSettle();
      await tester.enterText(
        field('Reason for the handoff'),
        'A current professional handoff',
      );
      await tapRisk(tester, 'Confirm owner handoff');
      expect(repo.transfers, 1);
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pumpAndSettle();
      await tapRisk(tester, 'Confirm owner handoff');
      expect(repo.transfers, 2);
      expect(repo.intents.last['expected_revision'], 2);
      expect(repo.bodies, 0);
    },
  );
  testWidgets(
    'background and fresh scope refusal cannot restore late private text',
    (tester) async {
      final repo = RiskFake()..bodyWait = Completer<InterventionDetail>();
      await frameRisk(tester, repo);
      await tester.tap(find.text('Open your intervention'));
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.bodyWait!.complete(
        InterventionDetail.fromJson({
          ...riskMeta(),
          'action': 'Late private body',
          'outcome': null,
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('Late private body'), findsNothing);
      repo.denyQueue = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Late private body'), findsNothing);
      expect(
        find.text('Reopen staff follow-up to check your current access.'),
        findsOneWidget,
      );
    },
  );
  testWidgets(
    'same-account permission withdrawal erases and never revives old body',
    (tester) async {
      var allowed = true;
      final repo = RiskFake();
      await frameRisk(tester, repo, canRead: () => allowed);
      await tapRisk(tester, 'Open your intervention');
      expect(find.text('Private owned intervention'), findsWidgets);
      allowed = false;
      await tester.pumpWidget(
        MaterialApp(
          home: RiskInterventionsScreen(
            repository: repo,
            canRead: () => allowed,
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Private owned intervention'), findsNothing);
      allowed = true;
      await tester.pumpWidget(
        MaterialApp(
          home: RiskInterventionsScreen(
            repository: repo,
            canRead: () => allowed,
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Private owned intervention'), findsNothing);
    },
  );
  testWidgets('unknown wellbeing is not shown as a measured zero', (
    tester,
  ) async {
    final repo = RiskFake();
    await frameRisk(tester, repo);
    await tapRisk(tester, 'Early warning list');
    expect(find.text('Unknown'), findsOneWidget);
    expect(find.textContaining('Observed score: 0'), findsNothing);
  });
  testWidgets('own clinical context is labelled separately from a risk score', (
    tester,
  ) async {
    final repo = RiskFake()..ownCaseCount = 2;
    await frameRisk(tester, repo, width: 390);
    await tapRisk(tester, 'Early warning list');
    expect(find.text('Own authorised active cases'), findsOneWidget);
    expect(find.text('2'), findsOneWidget);
    expect(find.textContaining('Observed score'), findsNothing);
    expect(
      find.text(
        'Current cases you can open in counselling. This count does not affect the risk score.',
      ),
      findsOneWidget,
    );
  });
}
