import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

Map<String, Object?> mySchoolJson(int id, String name) => {
  'school': school(id, name),
  'website': 'https://school$id.test',
  'phone': '011 555 010$id',
  'email': 'office@school$id.test',
  'address': '1 School Road, Johannesburg',
  'documents': [
    {
      'title': 'Fee schedule 2026',
      'kind': 'fee_schedule',
      'url': 'https://school$id.test/fees.pdf',
    },
    {
      'title': 'Code of conduct',
      'kind': 'code_of_conduct',
      'url': 'https://school$id.test/conduct.pdf',
    },
  ],
  'term_dates': [
    {
      'name': 'Term 4',
      'starts_on': '2026-10-06',
      'ends_on': '2026-12-09',
      'grade_from': 0,
      'grade_to': 3,
    },
  ],
};

class FakeMySchool implements MySchoolRepository {
  final Map<int, MySchoolInfo?> answers = {
    1: MySchoolInfo.fromJson(mySchoolJson(1, 'Northcliff High')),
    2: MySchoolInfo.fromJson(mySchoolJson(2, 'Riverside Primary')),
  };
  final List<int> asked = [];

  @override
  Future<MySchoolInfo?> fetch(int schoolId) async {
    asked.add(schoolId);
    return answers[schoolId];
  }
}

final SchoolSummary northcliff = SchoolSummary(
  id: 1,
  name: 'Northcliff High',
  shortName: 'NHS',
  crestUrl: null,
);
final SchoolSummary riverside = SchoolSummary(
  id: 2,
  name: 'Riverside Primary',
  shortName: 'RP',
  crestUrl: null,
);

void main() {
  group('ApiMySchoolRepository', () {
    test('reads the school\'s page', () async {
      final adapter = RecordingAdapter(
        200,
        ok(mySchoolJson(1, 'Northcliff High')),
      );

      final info = await ApiMySchoolRepository(clientFor(adapter)).fetch(1);

      expect(adapter.requests.single.uri.path, '/api/v2/schools/1/my-school');
      expect(adapter.requests.single.headers['X-School-Id'], '1');
      expect(adapter.requests.single.headers['Authorization'], 'Bearer token');
      expect(info!.school!.name, 'Northcliff High');
      expect(info.phone, '011 555 0101');
      expect(info.documents.first.kind, 'fee_schedule');
      expect(info.termDates.single.endsOn, DateTime(2026, 12, 9));
      expect(info.termDates.single.gradeFrom, 0);
      expect(info.termDates.single.gradeTo, 3);
    });

    test('answers null before the endpoint is deployed', () async {
      final repository = ApiMySchoolRepository(
        clientFor(RecordingAdapter(404, {'message': 'Not Found'})),
      );

      expect(await mySchoolDeployed(repository, 1), isFalse);
    });
  });

  group('MySchoolScreen', () {
    late FakeMySchool repository;
    late List<Uri> opened;

    Widget screen(List<SchoolSummary> schools) => MySchoolScreen.withRepository(
      repository,
      schools: schools,
      openLink: (uri) async {
        opened.add(uri);
        return true;
      },
    );

    setUp(() {
      repository = FakeMySchool();
      opened = [];
    });

    for (final Size size in [phone, desktop]) {
      testWidgets('shows contacts, documents and term dates '
          '(${size.width.toInt()} px)', (tester) async {
        await pumpScreen(tester, screen([northcliff]), size: size);

        expect(find.text('Northcliff High'), findsOneWidget);
        expect(find.text('011 555 0101'), findsOneWidget);
        expect(find.text('Fee schedule 2026'), findsOneWidget);
        await tester.scrollUntilVisible(find.text('Term 4 · Grade R–3'), 100);
        expect(find.text('Term 4 · Grade R–3'), findsOneWidget);
        expect(find.text('6 Oct 2026 – 9 Dec 2026'), findsOneWidget);
        expect(find.byType(ChoiceChip), findsNothing);
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('a tap calls, writes or opens', (tester) async {
      await pumpScreen(tester, screen([northcliff]));

      await tester.tap(find.text('011 555 0101'));
      await tester.tap(find.text('office@school1.test'));
      await tester.scrollUntilVisible(find.text('Fee schedule 2026'), 100);
      await tester.tap(find.text('Fee schedule 2026'));

      expect(opened, [
        Uri.parse('tel:0115550101'),
        Uri.parse('mailto:office@school1.test'),
        Uri.parse('https://school1.test/fees.pdf'),
      ]);
    });

    testWidgets('a parent with children at two schools picks the school', (
      tester,
    ) async {
      await pumpScreen(tester, screen([northcliff, riverside]));
      expect(find.byType(ChoiceChip), findsNWidgets(2));
      expect(find.text('011 555 0101'), findsOneWidget);

      await tester.tap(find.widgetWithText(ChoiceChip, 'RP'));
      await tester.pumpAndSettle();

      expect(find.text('Riverside Primary'), findsOneWidget);
      expect(find.text('011 555 0102'), findsOneWidget);
      expect(repository.asked, [1, 2]);
    });

    testWidgets('a school with nothing added yet says so', (tester) async {
      repository.answers[1] = MySchoolInfo.fromJson({
        'school': school(1, 'Northcliff High'),
      });
      await pumpScreen(tester, screen([northcliff]));

      expect(
        find.text('The school has not added its details yet.'),
        findsOneWidget,
      );
    });

    testWidgets(
      'a partial older school uses context branding and keeps its contacts',
      (tester) async {
        repository.answers[1] = MySchoolInfo.fromJson({
          ...mySchoolJson(1, 'Legacy name'),
          'school': {'id': 1, 'name': 'Legacy name'},
        });
        await pumpScreen(tester, screen([northcliff]));
        expect(find.text('Northcliff High'), findsOneWidget);
        expect(find.text('011 555 0101'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );

    testWidgets(
      'unsafe and malformed web links fail without launching or crashing',
      (tester) async {
        repository.answers[1] = MySchoolInfo.fromJson({
          ...mySchoolJson(1, 'Northcliff High'),
          'website': 'javascript:alert(1)',
          'documents': [
            {'title': 'Malformed document', 'kind': null, 'url': 'https://['},
          ],
        });
        await pumpScreen(tester, screen([northcliff]));
        await tester.scrollUntilVisible(find.text('javascript:alert(1)'), 100);
        await tester.tap(find.text('javascript:alert(1)'));
        await tester.pumpAndSettle();
        expect(opened, isEmpty);
        await tester.scrollUntilVisible(find.text('Malformed document'), 100);
        await tester.tap(find.text('Malformed document'));
        await tester.pumpAndSettle();
        expect(opened, isEmpty);
        expect(tester.takeException(), isNull);
      },
    );

    testWidgets('contacts say what a tap does', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pumpScreen(tester, screen([northcliff, riverside]));

      expect(
        tester.getSemantics(find.bySemanticsLabel('Call, 011 555 0101')),
        isSemantics(isButton: true, hasTapAction: true),
      );
      expect(
        find.bySemanticsLabel('Email, office@school1.test'),
        findsOneWidget,
      );
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
      semantics.dispose();
    });
  });
}
