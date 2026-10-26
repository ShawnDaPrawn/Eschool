import 'dart:async';
import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

const supportDoc = 'c8705678-17aa-48aa-88bb-123456789abc';
const supportThread = 'd1605678-17aa-48aa-88bb-123456789abc';
const supportMessage = 'f1605678-17aa-48aa-88bb-123456789abc';
const supportTurn = 'e1605678-17aa-48aa-88bb-123456789abc';
Map<String, dynamic> documentData() => {
  'id': supportDoc,
  'document_key': supportDoc,
  'revision': 1,
  'title': 'School hours',
  'source_reference': 'Approved school handbook',
  'expires_on': null,
  'state': 'published',
};

class SupportFake implements AssistanceRepository {
  SupportFake({this.office = false, this.writable = true});
  final bool office;
  bool? writable;
  bool active = true, denied = false, unknown = false, ready = false;
  int configurations = 0;
  int? unknownStatus;
  Completer<SchoolAssistanceDocumentBody>? documentWait;
  final writes = <Map<String, dynamic>>[];
  final messages = <Map<String, dynamic>>[
    {
      'id': supportMessage,
      'author_kind': 'parent',
      'text': 'PRIVATE-PARENT original request',
      'language': 'en',
      'sent_at': '2026-10-26T10:00:00Z',
    },
  ];
  final expiry = DateTime.now().toUtc().add(const Duration(minutes: 14));
  @override
  int get readerId => 12;
  @override
  int get schoolId => 1;
  @override
  bool get isActive => active;
  Map<String, dynamic> scoped(Map<String, dynamic> data) => {
    ...data,
    'school_id': 1,
    'reader_id': 12,
  };
  @override
  Future<SchoolAssistanceConfiguration> configuration() async {
    configurations++;
    if (denied) throw ApiException('Withdrawn', statusCode: 403);
    return SchoolAssistanceConfiguration.fromJson(
      scoped({
        'can_parent': !office,
        'can_office': office,
        'languages': ['en', 'af'],
        'answer_available': ready,
        'translation_available': ready,
        if (writable != null) 'writable': writable,
      }),
    );
  }

  @override
  Future<SchoolAssistanceDocumentPage> documents(
    int learner, {
    int page = 1,
    bool office = false,
  }) async => SchoolAssistanceDocumentPage.fromJson(
    scoped({
      'page': page,
      'last_page': 1,
      'items': [documentData()],
    }),
  );
  @override
  Future<SchoolAssistanceDocumentBody> document(
    String id, {
    int? learner,
  }) async =>
      documentWait?.future ??
      SchoolAssistanceDocumentBody.fromJson(
        scoped({
          ...documentData(),
          'markdown': 'PRIVATE-SOURCE school hours are 08:00.',
        }),
      );
  @override
  Future<SchoolAssistanceThreadPage> threads(
    int learner, {
    int page = 1,
  }) async => SchoolAssistanceThreadPage.fromJson(
    scoped({
      'page': page,
      'last_page': 1,
      'items': [
        {
          'id': supportThread,
          'revision': 1,
          'state': 'open',
          'created_at': '2026-10-26T10:00:00Z',
        },
      ],
    }),
  );
  @override
  Future<SchoolAssistanceOfficeQueue> queue({int page = 1}) async =>
      SchoolAssistanceOfficeQueue.fromJson(
        scoped({
          'page': page,
          'last_page': 1,
          'items': [
            {
              'id': supportThread,
              'revision': 1,
              'state': 'open',
              'created_at': '2026-10-26T10:00:00Z',
              'assigned_to_reader': true,
            },
          ],
        }),
      );
  @override
  Future<SchoolAssistanceThread> thread(
    String id, {
    required bool office,
    int page = 1,
  }) async {
    if (denied) throw ApiException('Withdrawn', statusCode: 403);
    return SchoolAssistanceThread.fromJson(
      scoped({
        'id': id,
        'revision': 1,
        'state': 'open',
        'learner_id': 3,
        'expires_at': expiry.toIso8601String(),
        'page': page,
        'last_page': 1,
        'messages': messages,
      }),
    );
  }

  @override
  Future<SchoolAssistanceStaffChoices> staff() async =>
      SchoolAssistanceStaffChoices.fromJson(
        scoped({
          'staff': [
            {'id': 13, 'name': 'Current office reader'},
          ],
        }),
      );
  Map<String, dynamic> turnData() => scoped({
    'id': supportTurn,
    'purpose': 'answer',
    'state': 'prepared',
    'learner_id': 3,
    'expires_at': expiry.toIso8601String(),
    'language': 'en',
    'review_text': jsonEncode({
      'task': 'answer',
      'language': 'en',
      'question': 'PRIVATE-REVIEW school question',
      'sources': [
        {
          'source': 's1',
          'text': jsonEncode({
            'markdown': 'PRIVATE-REVIEW approved source',
            'reference': 'School handbook',
          }),
        },
      ],
    }),
    'citations': [
      {
        'token': 's1',
        'kind': 'document',
        'title': 'School hours',
        'locator': {'id': supportDoc},
      },
    ],
    'response': null,
  });
  @override
  Future<SchoolAssistanceTurn> turn(String id) async =>
      SchoolAssistanceTurn.fromJson(turnData());
  @override
  Future<Map<String, dynamic>> write(
    AssistanceWrite action,
    Map<String, dynamic> body, {
    int? learner,
    String? reference,
  }) async {
    writes.add({
      'action': action.name,
      'body': jsonDecode(jsonEncode(body)),
      'reference': reference,
      'learner': learner,
    });
    if (unknown)
      throw ApiException(
        'Unknown network outcome',
        statusCode: unknownStatus,
        isTransportFailure: unknownStatus == null,
      );
    if ([
      AssistanceWrite.answer,
      AssistanceWrite.translation,
      AssistanceWrite.officeTranslation,
      AssistanceWrite.run,
    ].contains(action))
      return turnData();
    return scoped({
      'id': supportThread,
      'revision': 2,
      'state': 'open',
      'message_id': supportMessage,
    });
  }
}

class WrongChildSupport extends SupportFake {
  @override
  Future<SchoolAssistanceThread> thread(String id, {required bool office, int page = 1}) async {
    final body = jsonDecode(jsonEncode((await super.thread(id, office:office, page:page)).toJson())) as Map<String,dynamic>;
    return SchoolAssistanceThread.fromJson({...body, 'learner_id':99});
  }
  @override
  Map<String,dynamic> turnData() => {...super.turnData(), 'learner_id':99};
}

Future<void> supportFrame(
  WidgetTester tester,
  SupportFake repository, {
  double width = 390,
}) async {
  tester.view.physicalSize = Size(width, 1100);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(
      theme: ThemeData(useMaterial3: true),
      home: SchoolAssistanceScreen(
        repository: repository,
        office: repository.office,
        learnerId: repository.office ? null : 3,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> supportTap(WidgetTester tester, String label) async {
  final target = find.text(label);
  if (target.evaluate().isEmpty) {
    await tester.drag(find.byType(Scrollable).first, const Offset(0, 9000));
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(
      target,
      200,
      scrollable: find.byType(Scrollable).first,
    );
  }
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> supportType(
  WidgetTester tester,
  String label,
  String value,
) async {
  final target = find.byWidgetPredicate(
    (widget) => widget is TextField && widget.decoration?.labelText == label,
  );
  await tester.scrollUntilVisible(
    target,
    200,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.enterText(target, value);
  await tester.pump();
}

void main() {
  testWidgets('same-parent sibling thread is rejected before rendering a body for the selected child', (tester) async {
    final repo=WrongChildSupport();
    await supportFrame(tester,repo);
    await supportTap(tester,'Open thread');
    expect(find.text('PRIVATE-PARENT original request',skipOffstage:false),findsNothing);
  });
  testWidgets('an inconsistent successful review keeps its exact intent instead of allowing a replacement UUID', (tester) async {
    final repo=WrongChildSupport();
    await supportFrame(tester,repo);
    await supportType(tester,'Your school question','Private selected-child question');
    await supportTap(tester,'Review selected sources and question');
    expect(find.textContaining('PRIVATE-REVIEW school question',skipOffstage:false),findsNothing);
    final first=jsonEncode(repo.writes.single);
    await supportTap(tester,'Retry exact submitted request');
    expect(repo.writes.length,2);
    expect(jsonEncode(repo.writes.last),first);
  });
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('Parent handoff sends only explicit question at $width', (
      tester,
    ) async {
      final repo = SupportFake();
      await supportFrame(tester, repo, width: width);
      await supportType(
        tester,
        'Your school question',
        'PRIVATE-EXPLICIT question',
      );
      await supportTap(tester, 'Ask the school office');
      expect(repo.writes.single['action'], 'open');
      final body = repo.writes.single['body'] as Map;
      expect(body['text'], 'PRIVATE-EXPLICIT question');
      expect(body.containsKey('sources'), false);
      expect(body.containsKey('records'), false);
      await tester.scrollUntilVisible(
        find.text('PRIVATE-PARENT original request'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(find.text('PRIVATE-PARENT original request'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  for (final writable in [false, null]) {
    testWidgets(
      'readonly or absent flag keeps existing private conversations ($writable)',
      (tester) async {
        final repo = SupportFake(writable: writable);
        await supportFrame(tester, repo);
        await supportTap(tester, 'Open thread');
        await tester.scrollUntilVisible(
          find.text('PRIVATE-PARENT original request'),
          200,
          scrollable: find.byType(Scrollable).first,
        );
        expect(find.text('PRIVATE-PARENT original request'), findsOneWidget);
        final send = find.widgetWithText(FilledButton, 'Send reply');
        await tester.scrollUntilVisible(
          send,
          200,
          scrollable: find.byType(Scrollable).first,
        );
        expect(tester.widget<FilledButton>(send).onPressed, isNull);
        expect(repo.writes, isEmpty);
      },
    );
  }
  testWidgets(
    'unknown handoff keeps exact encrypted UUID through background and 404',
    (tester) async {
      final repo = SupportFake()..unknown = true;
      await supportFrame(tester, repo);
      await supportType(
        tester,
        'Your school question',
        'PRIVATE-UNCERTAIN question',
      );
      await supportTap(tester, 'Ask the school office');
      final first = jsonEncode(repo.writes.single);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.textContaining('PRIVATE-UNCERTAIN'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      repo.unknownStatus = 404;
      await supportTap(tester, 'Retry exact submitted request');
      expect(jsonEncode(repo.writes.last), first);
      await supportTap(tester, 'Check current access');
      repo.unknown = false;
      await supportTap(tester, 'Retry exact submitted request');
      expect(jsonEncode(repo.writes.last), first);
      expect(repo.writes.length, 3);
    },
  );
  testWidgets(
    'same-account source denial clears and never reactivates old reader',
    (tester) async {
      final repo = SupportFake();
      await supportFrame(tester, repo);
      await supportTap(tester, 'Open thread');
      await tester.scrollUntilVisible(
        find.text('PRIVATE-PARENT original request'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(find.text('PRIVATE-PARENT original request'), findsOneWidget);
      repo.denied = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('PRIVATE-PARENT original request'), findsNothing);
      final count = repo.configurations;
      repo.denied = false;
      expect(
        tester
            .widget<TextButton>(
              find.widgetWithText(TextButton, 'Check current access'),
            )
            .onPressed,
        isNull,
      );
      expect(repo.configurations, count);
    },
  );
  testWidgets(
    'late document bytes cannot return after background source retirement',
    (tester) async {
      final repo = SupportFake()
        ..documentWait = Completer<SchoolAssistanceDocumentBody>();
      await supportFrame(tester, repo);
      final button = find.text('Read source');
      await tester.ensureVisible(button);
      await tester.tap(button);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.documentWait!.complete(
        SchoolAssistanceDocumentBody.fromJson(
          repo.scoped({...documentData(), 'markdown': 'PRIVATE-LATE body'}),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.textContaining('PRIVATE-LATE'), findsNothing);
    },
  );
  testWidgets(
    'approved review expires while foreground and never reveals wire JSON',
    (tester) async {
      final repo = SupportFake();
      await supportFrame(tester, repo);
      await supportType(
        tester,
        'Your school question',
        'PRIVATE-QUESTION review',
      );
      await supportTap(tester, 'Review selected sources and question');
      await tester.scrollUntilVisible(
        find.textContaining('PRIVATE-REVIEW school question'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(
        find.textContaining('PRIVATE-REVIEW school question'),
        findsOneWidget,
      );
      expect(find.textContaining('"sources"'), findsNothing);
      await tester.pump(const Duration(minutes: 16));
      await tester.pump();
      expect(
        find.textContaining('PRIVATE-REVIEW school question'),
        findsNothing,
      );
    },
  );
  testWidgets(
    'office reply uses explicit persona and preserves original messages',
    (tester) async {
      final repo = SupportFake(office: true);
      await supportFrame(tester, repo);
      await supportTap(tester, 'Open thread');
      await supportType(tester, 'Your reply', 'PRIVATE-OFFICE reply');
      await supportTap(tester, 'Send reply');
      expect(repo.writes.single['action'], 'reply');
      expect((repo.writes.single['body'] as Map)['persona'], 'office');
      await tester.scrollUntilVisible(
        find.text('PRIVATE-PARENT original request'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(find.text('PRIVATE-PARENT original request'), findsOneWidget);
    },
  );
  testWidgets(
    'refresh cannot turn an approved existing document into a separate publication',
    (tester) async {
      final repo = SupportFake(office: true);
      await supportFrame(tester, repo);
      await supportTap(tester, 'Read source');
      final approval = find.widgetWithText(
        CheckboxListTile,
        'I approve this exact school document and reviewed identifying details',
      );
      await tester.scrollUntilVisible(
        approval,
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.ensureVisible(approval);
      await tester.pumpAndSettle();
      await tester.tap(approval);
      await tester.pump();
      expect(tester.widget<CheckboxListTile>(approval).value, isTrue);
      await tester.drag(find.byType(Scrollable).first, const Offset(0, 9000));
      await tester.pumpAndSettle();
      await supportTap(tester, 'Refresh current information');
      final title = find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == 'Document title',
      );
      await tester.scrollUntilVisible(
        title,
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(tester.widget<TextField>(title).controller!.text, isEmpty);
      final publish = find.widgetWithText(
        FilledButton,
        'Publish approved document',
      );
      await tester.scrollUntilVisible(
        publish,
        200,
        scrollable: find.byType(Scrollable).first,
      );
      expect(tester.widget<FilledButton>(publish).onPressed, isNull);
      expect(repo.writes, isEmpty);
    },
  );
}
