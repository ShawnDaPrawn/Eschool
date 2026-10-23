import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/studentProfileScreen.dart';
import 'package:eschool/ui/widgets/borderedProfilePictureContainer.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('a learner signs in, lands on home and opens their profile', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, learnerRoutes());

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
  });
}
