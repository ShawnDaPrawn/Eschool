import 'package:dio/dio.dart';
import 'package:eschool/app/app.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/studentProfileScreen.dart';
import 'package:eschool/ui/widgets/borderedProfilePictureContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets(
    'an unpaid learner signs in, returns home and opens their profile',
    (tester) async {
      final unpaid = {...student(), 'is_fee_payment_due': 1};
      final api = await startSmokeApp(tester, {
        ...learnerRoutes(),
        'settings': (RequestOptions request) =>
            request.queryParameters['type'] == 'app_settings'
            ? ok({...appSettings, 'compulsory_fee_payment_mode': '1'})
            : ok(''),
        'POST student/login': {...ok(unpaid), 'token': 'smoke-student-token'},
        'student/get-profile-data': ok(unpaid),
      });

      final Finder asStudent = find.text('Login as Student');
      await tapWhenReady(tester, asStudent);
      await pumpUntilFound(
        tester,
        find.text('Student number, GR number or handle'),
      );

      await enterField(tester, 0, grNumber);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );

      await pumpUntilFound(tester, find.byType(HomeScreen));
      expect(api.requested('POST student/login'), isTrue);
      expect(api.requested('GET student/dashboard'), isTrue);

      await tapWhenReady(tester, find.byType(BorderedProfilePictureContainer));
      await pumpUntilFound(tester, find.byType(StudentProfileScreen));
      expect(find.textContaining('Thandi'), findsWidgets);
      // Recreate the app with the persisted unpaid session: the startup path
      // must grant the same learning access as a fresh sign-in.
      await tester.pumpWidget(const SizedBox.shrink());
      await tester.pump();
      await tester.pumpWidget(const MyApp());
      await pumpUntilFound(tester, find.byType(HomeScreen));
      expect(api.requested('GET student/get-profile-data'), isTrue);
    },
  );
}
