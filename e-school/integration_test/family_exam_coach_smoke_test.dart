import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool/app/routes.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

final class _Photo extends PlatformFile {
  String get name => 'synthetic-paper.png';
  Uri get uri => Uri.parse('blob:synthetic-paper');
  Never get xFile => throw StateError('Bounded streaming only');
  int? lengthSync() => base64Decode(ExamCoachSmokeFixture.pixels).length;
  Future<int?> length() async => lengthSync();
  Future<Uint8List> readAsBytes() => throw StateError('Bounded streaming only');
  Stream<Uint8List> readAsByteStream() async* {
    yield base64Decode(ExamCoachSmokeFixture.pixels);
  }
}

class _Picker extends FilePickerPlatform {
  Future<PlatformFile?> pickFile({
    String? dialogTitle,
    String? initialDirectory,
    FileType type = FileType.any,
    List<String>? allowedExtensions,
    Function(FilePickerStatus)? onFileLoading,
    int compressionQuality = 0,
    AndroidOptions androidOptions = const AndroidOptions(),
    DarwinOptions darwinOptions = const DarwinOptions(),
    WindowsOptions windowsOptions = const WindowsOptions(),
    LinuxOptions linuxOptions = const LinuxOptions(),
    WebOptions webOptions = const WebOptions(),
  }) async => _Photo();
  Future<void> clearTemporaryFiles() async {}
}

Future<void> seek(WidgetTester tester, Finder finder) async {
  await tester.pump(const Duration(milliseconds: 300));
  await pumpUntilFound(tester, find.byType(Scrollable));
  // Preserve the mounted crop selection while reaching controls inside it.
  // Jumping to the top first can dispose its lazy ListView element.
  if (finder.evaluate().isEmpty) {
    tester
        .state<ScrollableState>(find.byType(Scrollable).first)
        .position
        .jumpTo(0);
    await tester.pump();
  }
  try {
    if (finder.evaluate().isEmpty) {
      await tester.scrollUntilVisible(
        finder,
        230,
        scrollable: find.byType(Scrollable).first,
      );
    }
  } catch (error) {
    throw TestFailure('Exam control could not be reached: $finder ($error)');
  }
  await tester.ensureVisible(finder);
  await pumpUntilFound(tester, finder);
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await seek(tester, finder);
  await tapWhenReady(tester, finder);
  await tester.pumpAndSettle();
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'Parent' : 'Student'} Exam Coach library, plan and scoped working journey',
      (tester) async {
        final original = FilePickerPlatform.instance;
        FilePickerPlatform.instance = _Picker();
        addTearDown(() => FilePickerPlatform.instance = original);
        final account = parent ? 21 : 11;
        final fixture = ExamCoachSmokeFixture(reader: account, staff: false);
        late final FakeApi api;
        api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...fixture.routes(() => api),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': account,
              'name': 'Synthetic reader',
              'first_name': 'Synthetic',
              'last_name': 'Reader',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'roles': [parent ? 'Parent' : 'Student'],
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'exam_coach',
                'web_learner',
                'web_parent',
              ])
                {'key': key, 'read_only': false},
            ],
          }),
        });
        await tapWhenReady(
          tester,
          find.text(parent ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent ? 'Email or handle' : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parent ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parent ? ParentHomeScreen : HomeScreen),
        );
        if (parent) {
          expect(find.text('Exam Coach'), findsNothing);
          Navigator.of(tester.element(find.byType(ParentHomeScreen)))
              .pushNamed(Routes.examCoach);
          await pumpUntilFound(
            tester,
            find.text('Not available for this account'),
          );
          expect(
            api.requestedPaths.where((p) => p.contains('/exam-coach/')),
            isEmpty,
          );
          return;
        }
        await tapWhenReady(tester, find.text('Exam Coach'));
        await pumpUntilFound(tester, find.text('Synthetic licensed paper'));
        await tap(tester, find.text('Synthetic licensed paper'));
        await seek(tester, find.text('1. Explain one half.'));
        expect(find.textContaining('PRIVATE-STAFF-MEMO-CANARY'), findsNothing);
        await tap(tester, find.byType(Checkbox).first);
        expect(
          tester.widget<Checkbox>(find.byType(Checkbox).first).value,
          true,
        );
        await tester.pump();
        await tap(tester, find.byKey(const ValueKey('exam-date-3')));
        await tapWhenReady(
          tester,
          find.text('Published exam · 2026-12-02').last,
        );
        await tap(tester, find.text('Create study plan'));
        await seek(tester, find.text('2026-12-01 · 20 minutes'));
        await tap(tester, find.text('Choose working photo'));
        await seek(tester, find.byKey(const Key('tutor-crop-area')));
        await tap(
          tester,
          find.text(
            'Share this cropped working and provisional feedback with my allocated teacher.',
          ),
        );
        await tap(
          tester,
          find.text('Select the whole photo if it contains only working'),
        );
        await tap(
          tester,
          find.text('I checked this selection contains only my working.'),
        );
        expect(
          tester
              .widgetList<Checkbox>(find.byType(Checkbox))
              .where((c) => c.value == true)
              .length,
          greaterThanOrEqualTo(2),
        );
        await seek(tester, find.text('Use this working crop'));
        expect(
          tester
              .widget<FilledButton>(
                find.ancestor(
                  of: find.text('Use this working crop'),
                  matching: find.byType(FilledButton),
                ),
              )
              .onPressed,
          isNotNull,
        );
        await tester.runAsync(
          () => tester.tap(
            find.text('Use this working crop').hitTestable().first,
          ),
        );
        final uploadDeadline = DateTime.now().add(const Duration(seconds: 15));
        while (!fixture.uploaded && DateTime.now().isBefore(uploadDeadline)) {
          await tester.pump(const Duration(milliseconds: 100));
        }
        expect(
          fixture.uploaded,
          true,
          reason:
              '${api.requestedPaths.join('\n')}\n${tester.widgetList<Text>(find.byType(Text)).map((t) => t.data).join(' | ')}',
        );
        await seek(tester, find.text('Request provisional feedback'));
        await tap(tester, find.text('Request provisional feedback'));
        await seek(tester, find.text('Explain the two equal parts.'));
        expect(find.textContaining('73.0%'), findsOneWidget);
        final requests = api.requests.where(
          (r) => r.uri.path.contains('/exam-coach/'),
        );
        expect(requests, isNotEmpty);
        expect(
          requests.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '11',
          ),
          true,
        );
        expect(
          requests.any(
            (r) =>
                r.uri.path.contains('/staff/') || r.uri.path.endsWith('/memo'),
          ),
          false,
        );
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text('Explain the two equal parts.'), findsNothing);
        fixture.permitted = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await tester.pumpAndSettle();
        expect(find.text('Explain the two equal parts.'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
