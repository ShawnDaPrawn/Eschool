import 'dart:convert';

import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';
import 'package:file_picker/file_picker.dart';
import 'package:eschool_academics/src/wellbeing/private_report_image.dart';

import 'support/photo_picker_fixture.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';
import 'support/reporting_fixture.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parentProfile in [false, true]) {
    testWidgets(
      '${parentProfile ? 'parent' : 'learner'} reports with explicit identity and clears withdrawn private data',
      (tester) async {
        final originalPicker = FilePickerPlatform.instance;
        FilePickerPlatform.instance = SyntheticPhotoPicker();
        addTearDown(() => FilePickerPlatform.instance = originalPicker);
        final fixture = ReportingFixture(), account = parentProfile ? 21 : 11;
        final api = await startSmokeApp(tester, {
          ...(parentProfile ? parentOfTwoRoutes() : learnerRoutes()),
          ...fixture.routes,
          'GET v2/me/context': ok({
            ...academicContextFixture(
              role: parentProfile ? 'Parent' : 'Student',
            ),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Mokoena',
              'name': 'Synthetic Mokoena',
              'email': parentProfile ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parentProfile ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'wellbeing',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parentProfile)
              'children': [
                for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
                  {
                    'id': id,
                    'user_id': id,
                    'first_name': name,
                    'last_name': 'Mokoena',
                    'name': '$name Mokoena',
                    'image': null,
                    'school': {
                      'id': 1,
                      'name': 'Smoke Test School',
                      'short_name': null,
                      'crest_url': null,
                    },
                  },
              ],
          }),
        });
        await tapWhenReady(
          tester,
          find.text(parentProfile ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parentProfile
                ? 'Email or handle'
                : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parentProfile ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parentProfile ? ParentHomeScreen : HomeScreen),
        );
        await tapWhenReady(tester, find.text('Report a concern'));
        Finder field(String label) => find.byWidgetPredicate(
          (w) => w is TextField && w.decoration?.labelText == label,
        );
        Future<void> seek(Finder target) async {
          FocusManager.instance.primaryFocus?.unfocus();
          await tester.pumpAndSettle();
          final scroll = find.byType(Scrollable).first;
          await tester.drag(scroll, const Offset(0, 100000));
          await tester.pumpAndSettle();
          for (int i = 0; target.evaluate().isEmpty && i < 30; i++) {
            await tester.drag(scroll, const Offset(0, -300));
            await tester.pumpAndSettle();
          }
          await tester.ensureVisible(target);
          await tester.pumpAndSettle();
        }

        await pumpUntilFound(tester, find.text('Report with my identity'));
        final category = find.byType(DropdownButtonFormField<int>);
        await seek(category);
        await tester.tap(category);
        await tester.pumpAndSettle();
        await tester.tap(find.text('Safety concern').last);
        await tester.pumpAndSettle();
        final body =
            'Private ${parentProfile ? 'parent' : 'learner'} browser concern';
        await seek(field('What happened?'));
        await tester.enterText(field('What happened?'), body);
        await tester.tap(find.byTooltip('Private updates'));
        await tester.pumpAndSettle();
        expect(find.text(body), findsNothing);
        expect(find.text('Unread private updates: 1'), findsOneWidget);
        await tester.tap(find.byKey(const ValueKey('wellbeing-notice-71')));
        await tester.pumpAndSettle();
        await seek(field('What happened?'));
        expect(
          tester.widget<TextField>(field('What happened?')).controller!.text,
          body,
        );
        final consent = find.widgetWithText(
          CheckboxListTile,
          'I agree to identify myself to the school safeguarding team for this report.',
        );
        await seek(consent);
        await tester.tap(consent);
        await tester.pumpAndSettle();
        final send = find.widgetWithText(
          FilledButton,
          'Send identified report',
        );
        await seek(send);
        await tester.tap(send);
        await tester.pumpAndSettle();
        await seek(find.text(body));
        expect(find.text(body), findsOneWidget);
        expect(fixture.submissions.single['anonymous'], false);
        expect(fixture.submissions.single['identify_consent'], true);
        expect(fixture.submissions.single.containsKey('learner_id'), false);
        expect(fixture.code, hasLength(64));
        final choosePhoto = find.widgetWithText(
          FilledButton,
          'Choose report photo',
        );
        await seek(choosePhoto);
        await tester.tap(choosePhoto);
        await tester.pumpAndSettle();
        final sendPhoto = find.widgetWithText(
          FilledButton,
          'Send selected photo',
        );
        await seek(sendPhoto);
        await tester.tap(sendPhoto);
        await tester.pumpAndSettle();
        await seek(find.byType(PrivateReportImage));
        expect(find.byType(PrivateReportImage), findsOneWidget);
        expect(fixture.photos.length, 1);
        final upload = api.requests.singleWhere(
          (r) => r.path.endsWith('/wellbeing/own/photos'),
        );
        expect(upload.queryParameters, isEmpty);
        expect(upload.uri.toString(), isNot(contains(fixture.code)));
        final stored = jsonEncode({
          ...Hive.box(authBoxKey).toMap(),
          ...Hive.box(settingsBoxKey).toMap(),
        });
        expect(stored, isNot(contains(fixture.code)));
        expect(stored, isNot(contains(body)));
        expect(stored, isNot(contains(SyntheticPhotoFile.pixels)));
        await seek(field('Add a follow-up'));
        await tester.enterText(
          field('Add a follow-up'),
          'Private own follow-up',
        );
        final reply = find.widgetWithText(FilledButton, 'Send follow-up');
        await seek(reply);
        await tester.tap(reply);
        await tester.pumpAndSettle();
        await seek(find.text('Private own follow-up'));
        expect(find.text('Private own follow-up'), findsOneWidget);
        expect(
          api.requests
              .where((r) => r.path.contains('/wellbeing/'))
              .every(
                (r) =>
                    r.headers['X-Account-Id'] == '$account' &&
                    r.headers['X-School-Id'] == '1',
              ),
          true,
        );
        fixture.denied = true;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text(body), findsNothing);
        expect(find.byType(PrivateReportImage), findsNothing);
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text(
            'Your reporting access changed. Reopen this area after refreshing your account.',
          ),
        );
        expect(find.text(body), findsNothing);
        expect(find.byType(PrivateReportImage), findsNothing);
        expect(find.text(fixture.code), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
