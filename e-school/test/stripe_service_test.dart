import 'package:eschool/utils/stripeService.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  const channel = MethodChannel('flutter.stripe/payments', JSONMethodCodec());
  final calls = <MethodCall>[];
  String? failAt;

  setUp(() {
    calls.clear();
    failAt = null;
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, (call) async {
          calls.add(call);
          if (call.method == failAt) {
            throw PlatformException(code: 'cancelled');
          }
          return <String, Object?>{};
        });
    StripeService.init('pk_test_local_fixture', 'test');
  });

  tearDown(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(channel, null);
  });

  test('checkout configures the native sheet with the server intent before presenting', () async {
    await StripeService.payWithPaymentSheet(
      clientSecret: 'pi_fixture_secret_fixture',
      merchantDisplayName: 'Test School',
    );

    final checkout = calls
        .where((call) => call.method != 'initialise')
        .toList();
    expect(checkout.map((call) => call.method), [
      'initPaymentSheet',
      'presentPaymentSheet',
    ]);
    final params = (checkout.first.arguments as Map)['params'] as Map;
    expect(params['paymentIntentClientSecret'], 'pi_fixture_secret_fixture');
    expect(params['merchantDisplayName'], 'Test School');
  });

  test('a failed setup never presents a sheet', () async {
    failAt = 'initPaymentSheet';
    await expectLater(StripeService.payWithPaymentSheet(), throwsException);
    expect(calls.any((call) => call.method == 'presentPaymentSheet'), isFalse);
  });

  test(
    'a cancelled sheet fails checkout instead of reporting success',
    () async {
      failAt = 'presentPaymentSheet';
      await expectLater(StripeService.payWithPaymentSheet(), throwsException);
      expect(calls.last.method, 'presentPaymentSheet');
    },
  );
}
