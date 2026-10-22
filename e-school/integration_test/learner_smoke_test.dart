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
    await pumpUntilFound(tester, asStudent);
    await tester.tap(asStudent);
    await pumpUntilFound(tester, find.text('GR Number'));

    await enterField(tester, 0, grNumber);
    await enterField(tester, 1, password);
    await tester.tap(find.widgetWithText(CustomRoundedButton, 'Sign in'));

    await pumpUntilFound(tester, find.byType(HomeScreen));
    expect(api.requested('POST student/login'), isTrue);
    expect(api.requested('GET student/dashboard'), isTrue);

    await tester.tap(
      find.byType(BorderedProfilePictureContainer).hitTestable().first,
    );
    await pumpUntilFound(tester, find.byType(StudentProfileScreen));
    expect(find.textContaining('Thandi'), findsWidgets);
  });
}
