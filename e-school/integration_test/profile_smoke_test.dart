import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

Map<String, Object?> profile({
  bool learner = false,
  String route = 'Old route',
}) => {
  'person_id': learner ? 11 : 21,
  'profile_type': learner ? 'learner' : 'parent',
  'available_profiles': [learner ? 'learner' : 'parent'],
  'categories': [
    {
      'id': 1,
      'key': 'transport',
      'label': 'Transport',
      'fields': [
        {
          'id': 1,
          'key': 'first_name',
          'label': 'First name',
          'type': 'text',
          'validation': {},
          'options': [],
          'required': true,
          'locked': true,
          'editable': false,
          'sensitive': false,
          'value': {'text': learner ? 'Thandi' : 'Lerato'},
        },
        {
          'id': 2,
          'key': 'route',
          'label': 'Transport route',
          'type': 'text',
          'validation': {},
          'options': [],
          'required': false,
          'locked': false,
          'editable': true,
          'sensitive': false,
          'value': {'text': route},
        },
      ],
    },
  ],
};
void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'guardian edits a child profile using its own school and a partial write',
    (tester) async {
      var route = 'Old route';
      final childSchool = {
        'id': 2,
        'name': 'Second School',
        'short_name': null,
        'crest_url': null,
      };
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          'schools': [
            {
              'school': {
                'id': 1,
                'name': 'First School',
                'short_name': null,
                'crest_url': null,
              },
              'roles': ['Parent'],
            },
          ],
          'children': [
            {
              'id': 11,
              'user_id': 11,
              'first_name': 'Thandi',
              'last_name': 'Mokoena',
              'name': 'Thandi Mokoena',
              'image': null,
              'school': childSchool,
            },
          ],
        }),
        'GET v2/me/profile': ok(profile()),
        'GET v2/people/11/profile': (RequestOptions _) =>
            ok(profile(learner: true, route: route)),
        'PUT v2/people/11/profile': (RequestOptions request) {
          route =
              ((request.data as Map)['values'] as Map)['route']['text']
                  as String;
          return ok(null);
        },
      });
      await tapWhenReady(tester, find.text('Login as Parent'));
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, parentEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.byIcon(Icons.settings));
      await tapWhenReady(tester, find.text('Profile details'));
      await pumpUntilFound(tester, find.byType(ProfileScreen));
      await tapWhenReady(tester, find.textContaining('My details'));
      await tapWhenReady(
        tester,
        find.text('Thandi Mokoena — Second School').last,
      );
      await pumpUntilFound(tester, find.text('Thandi'));
      final routeField = find.byKey(const ValueKey('profile-field-route'));
      // Opening the native keyboard can echo its initial editing value. Finish
      // that platform transition before injecting the user's new text.
      await tester.showKeyboard(routeField);
      await tester.pump(const Duration(milliseconds: 600));
      await tester.enterText(routeField, 'Blue bus');
      // Let the edit reach the rebuilt form and native text-input connection
      // before hiding the keyboard; an immediate unfocus can replay old IME text.
      await tester.pump();
      final editor = tester
          .element(find.byKey(const ValueKey('profile-field-route')))
          .read<ProfileCubit>();
      expect(editor.state.draft['route']?.text, 'Blue bus');
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pump(const Duration(milliseconds: 300));
      expect(editor.state.draft['route']?.text, 'Blue bus');
      await tapWhenReady(tester, find.text('Save profile'));
      await pumpUntilFound(tester, find.text('Profile saved'));
      final write = api.requests
          .where(
            (r) =>
                r.method == 'PUT' && r.uri.path.endsWith('people/11/profile'),
          )
          .single;
      expect(write.headers['X-School-Id'], '2');
      expect(write.data, {
        'profile_type': 'learner',
        'values': {
          'route': {'text': 'Blue bus'},
        },
      });
      expect(route, 'Blue bus');
    },
  );
}
