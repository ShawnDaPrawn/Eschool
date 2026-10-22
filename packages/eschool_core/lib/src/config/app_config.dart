/// Build settings, read from `--dart-define` when the app is compiled.
///
/// Staging and production builds of the same app differ only by these
/// values, so nothing about an environment is written in the code:
///
/// ```sh
/// flutter build apk --dart-define-from-file=config/staging.json
/// flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000 --dart-define=ENV=local
/// ```
///
/// | Define | Meaning | Default |
/// |---|---|---|
/// | `API_BASE_URL` | The admin panel's address, without a trailing `/` | [defaultApiBaseUrl] |
/// | `ENV` | `production`, `staging` or `local` | `production` |
/// | `FLAVOR` | Which shell this build is: `learner` (the student and parent app) or `teacher` | the app's own |
///
/// A build without any define talks to the production server, as every build
/// did before these settings existed.
// ignore: avoid_classes_with_only_static_members
abstract final class AppConfig {
  /// The server every build used before the address became configurable.
  static const String defaultApiBaseUrl =
      'https://navigateed-admin-972068206844.africa-south1.run.app';

  static const String productionEnvironment = 'production';
  static const String stagingEnvironment = 'staging';
  static const String localEnvironment = 'local';

  static const List<String> environments = [
    productionEnvironment,
    stagingEnvironment,
    localEnvironment,
  ];

  /// The admin panel's address, for example `https://admin.navigateed.co.za`.
  static const String apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: defaultApiBaseUrl,
  );

  /// Where the API lives: `<apiBaseUrl>/api/`.
  static const String apiUrl = '$apiBaseUrl/api/';

  /// Where API v2 lives: `<apiBaseUrl>/api/v2/`.
  static const String apiV2Url = '$apiBaseUrl/api/v2/';

  /// Where uploaded files are served: `<apiBaseUrl>/storage/`.
  static const String storageUrl = '$apiBaseUrl/storage/';

  /// `production`, `staging` or `local`.
  static const String environment = String.fromEnvironment(
    'ENV',
    defaultValue: productionEnvironment,
  );

  /// The flavour named at build time, or an empty string when none was.
  /// Read [flavorOr] to get the app's own default in that case.
  static const String flavor = String.fromEnvironment('FLAVOR');

  static bool get isProduction => environment == productionEnvironment;

  /// The flavour of this build, falling back to [appDefault] (each app
  /// passes its own) when the build named none.
  static String flavorOr(String appDefault) =>
      flavor.isEmpty ? appDefault : flavor;

  /// What is wrong with the defines this app was built with, in plain words;
  /// empty when nothing is.
  static List<String> problems() =>
      problemsWith(apiBaseUrl: apiBaseUrl, environment: environment);

  /// [problems] for any pair of values, so the rules can be tested.
  static List<String> problemsWith({
    required String apiBaseUrl,
    required String environment,
  }) {
    final List<String> found = [];
    final Uri? uri = Uri.tryParse(apiBaseUrl);
    if (uri == null || !uri.hasScheme || uri.host.isEmpty) {
      found.add('API_BASE_URL must be an absolute URL, got "$apiBaseUrl".');
    } else {
      if (apiBaseUrl.endsWith('/')) {
        found.add('API_BASE_URL must not end with "/".');
      }
      if (environment != localEnvironment && uri.scheme != 'https') {
        found.add('API_BASE_URL must use https outside ENV=local.');
      }
    }
    if (!environments.contains(environment)) {
      found.add(
        'ENV must be one of ${environments.join(', ')}, got "$environment".',
      );
    }
    return found;
  }

  /// Stops a debug build that was given unusable defines, with the reason.
  /// Release builds are not stopped: a mistyped define should never brick an
  /// installed app, and the build scripts pass checked values.
  static void assertValid() {
    assert(() {
      final List<String> found = problems();
      if (found.isNotEmpty) {
        throw StateError('Invalid build settings: ${found.join(' ')}');
      }
      return true;
    }());
  }
}
