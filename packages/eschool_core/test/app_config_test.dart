import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('a build without defines talks to the production server', () {
    // flutter test is run without --dart-define, like a plain flutter run.
    expect(AppConfig.apiBaseUrl, AppConfig.defaultApiBaseUrl);
    expect(AppConfig.apiUrl, '${AppConfig.defaultApiBaseUrl}/api/');
    expect(AppConfig.storageUrl, '${AppConfig.defaultApiBaseUrl}/storage/');
    expect(AppConfig.environment, 'production');
    expect(AppConfig.isProduction, isTrue);
    expect(AppConfig.problems(), isEmpty);
  });

  test('the app names its own flavour when the build named none', () {
    expect(AppConfig.flavor, isEmpty);
    expect(AppConfig.flavorOr('teacher'), 'teacher');
  });

  group('problemsWith', () {
    test('accepts https addresses in every environment', () {
      for (final environment in AppConfig.environments) {
        expect(
          AppConfig.problemsWith(
            apiBaseUrl: 'https://staging-admin.navigateed.co.za',
            environment: environment,
          ),
          isEmpty,
        );
      }
    });

    test('accepts plain http only for local builds', () {
      expect(
        AppConfig.problemsWith(
          apiBaseUrl: 'http://10.0.2.2:8000',
          environment: 'local',
        ),
        isEmpty,
      );
      expect(
        AppConfig.problemsWith(
          apiBaseUrl: 'http://admin.navigateed.co.za',
          environment: 'staging',
        ),
        [contains('https')],
      );
    });

    test('refuses a trailing slash, a relative address and unknown ENV', () {
      expect(
        AppConfig.problemsWith(
          apiBaseUrl: 'https://admin.navigateed.co.za/',
          environment: 'production',
        ),
        [contains('must not end with "/"')],
      );
      expect(
        AppConfig.problemsWith(apiBaseUrl: 'admin', environment: 'production'),
        [contains('absolute URL')],
      );
      expect(
        AppConfig.problemsWith(
          apiBaseUrl: 'https://admin.navigateed.co.za',
          environment: 'prod',
        ),
        [contains('ENV must be one of')],
      );
    });
  });
}
