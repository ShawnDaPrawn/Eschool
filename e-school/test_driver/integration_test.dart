import 'package:integration_test/integration_test_driver.dart';

/// Runs an integration_test/ flow in Chrome:
/// flutter drive --driver test_driver/integration_test.dart
///   --target integration_test/learner_smoke_test.dart -d web-server
Future<void> main() => integrationDriver();
