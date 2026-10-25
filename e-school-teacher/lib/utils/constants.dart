import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';

//The server address comes from the build: --dart-define=API_BASE_URL=...
//(see AppConfig in eschool_core). Without it, builds use the production server.
const String baseUrl = AppConfig.apiBaseUrl;

//please do not change the below 2 values it's for convince of usage in code
const String databaseUrl = AppConfig.apiUrl;
const String storageUrl = AppConfig.storageUrl;

//which shell this build is (--dart-define=FLAVOR=...), 'teacher' when not given
String get appFlavor => StaffBuildIdentity.current.name;

//error message display duration
const Duration errorMessageDisplayDuration = Duration(milliseconds: 3000);

//the limit used in pagination APIs where offset and limit logic is used, change to fetch items accordingly
const int offsetLimitPaginationAPIDefaultItemFetchLimit = 15;

//notification channel keys (basic_channel, chat_notifications_channel) are in
//eschool_notifications

//to enable and disable default credentials in login page
const bool showDefaultCredentials = false;
//default credentials of teacher
const String defaultTeacherEmail = 'teacher@gmail.com';
const String defaultTeacherPassword = 'teacher123';

//animations configuration: isApplicationItemAnimationOn and the durations
//are in eschool_core (lib/src/utils/animation_configuration.dart)
