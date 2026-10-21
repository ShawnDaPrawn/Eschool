//database urls
//Please add your admin panel url here and make sure you do not add '/' at the end of the url

const String baseUrl = 'https://navigateed-admin-972068206844.africa-south1.run.app';

//please do not change the below 2 values it's for convince of usage in code
const String databaseUrl = '$baseUrl/api/';
const String storageUrl = '$baseUrl/storage/';
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

//animations configuration
//if this is false all item appearance animations will be turned off
const bool isApplicationItemAnimationOn = true;
//note: do not add Milliseconds values less then 10 as it'll result in errors
const int listItemAnimationDelayInMilliseconds = 100;
const int itemFadeAnimationDurationInMilliseconds = 250;
const int itemZoomAnimationDurationInMilliseconds = 200;
const int itemBounceScaleAnimationDurationInMilliseconds = 200;
