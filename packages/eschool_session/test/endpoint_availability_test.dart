import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  setUp(EndpointAvailability.forget);

  test('asks once per feature until forgotten', () async {
    var asked = 0;
    Future<bool> probe() async {
      asked++;
      return true;
    }

    expect(await EndpointAvailability.check('approvals', probe), isTrue);
    expect(await EndpointAvailability.check('approvals', probe), isTrue);
    expect(asked, 1);

    EndpointAvailability.forget();
    await EndpointAvailability.check('approvals', probe);
    expect(asked, 2);
  });

  test('a failed check counts as not there and is asked again', () async {
    var asked = 0;
    Future<bool> probe() async {
      asked++;
      if (asked == 1) {
        throw Exception('no network');
      }
      return true;
    }

    expect(await EndpointAvailability.check('my-school', probe), isFalse);
    expect(await EndpointAvailability.check('my-school', probe), isTrue);
  });

  testWidgets('an entry point shows only when its endpoint is deployed', (
    tester,
  ) async {
    Widget entry(String key, bool deployed, {bool enabled = true}) =>
        AvailableOnly(
          featureKey: key,
          enabled: enabled,
          probe: () async => deployed,
          child: Text('Open $key'),
        );

    await tester.pumpWidget(
      MaterialApp(
        home: Column(
          children: [
            entry('approvals', false),
            entry('my-school', true),
            entry('avatar', true, enabled: false),
          ],
        ),
      ),
    );
    await tester.pumpAndSettle();

    expect(find.text('Open approvals'), findsNothing);
    expect(find.text('Open my-school'), findsOneWidget);
    expect(find.text('Open avatar'), findsNothing);
  });
}
