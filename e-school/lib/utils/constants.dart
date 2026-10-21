import 'package:eschool_core/eschool_core.dart';

//The server address comes from the build: --dart-define=API_BASE_URL=...
//(see AppConfig in eschool_core). Without it, builds use the production server.
const String baseUrl = AppConfig.apiBaseUrl;

//please do not change the below 2 values it's for convince of usage in code
const String databaseUrl = AppConfig.apiUrl;
const String storageUrl = AppConfig.storageUrl;

//which shell this build is (--dart-define=FLAVOR=...), 'learner' when not given
String get appFlavor => AppConfig.flavorOr('learner');
//error message display duration
const Duration errorMessageDisplayDuration = Duration(milliseconds: 3000);

//home menu bottom sheet animation duration
const Duration homeMenuBottomSheetAnimationDuration = Duration(
  milliseconds: 300,
);

//Change slider duration
const Duration changeSliderDuration = Duration(seconds: 5);

//Number of latest notices to show in home container
const int numberOfLatestNoticesInHomeScreen = 3;

//the limit used in pagination APIs where offset and limit logic is used, change to fetch items accordingly
const int offsetLimitPaginationAPIDefaultItemFetchLimit = 15;

//notification channel keys
const String notificationChannelKey = 'basic_channel';
const String chatNotificationChannelKey = 'chat_notifications_channel';

//to enable and disable default credentials in login page
const bool showDefaultCredentials = false;
//default credentials of student
const String defaultStudentGRNumber = 'student123';
const String defaultStudentPassword = 'student123';
//default credentials of parent
const String defaultParentEmail = 'parent@gmail.com';
const String defaultParentPassword = 'parent123';

//animations configuration: isApplicationItemAnimationOn and the durations
//are in eschool_core (lib/src/utils/animation_configuration.dart)
