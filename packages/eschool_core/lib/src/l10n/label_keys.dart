// Label keys used by the code shared between the apps. Each app's
// lib/utils/labelKeys.dart re-exports these, so app code keeps using the same
// names; every key must be in each app's assets/languages/*.json, which the
// apps' shared_label_keys_test checks.

const String addMoreCharactorsToSearchKey = 'addMoreCharactersToSearch';
const String availableInMobileAppKey = 'availableInMobileApp';
const String cancelKey = 'cancel';
const String dateKey = 'date';
const String deleteDialogMessageKey = 'deleteDialogMessage';
const String deleteDialogTitleKey = 'deleteDialogTitle';
const String errorLoadingMoreTapToRetryKey = 'errorLoadingMoreTapToRetry';
const String examNameKey = 'examName';
const String fileDownloadingKey = 'downloadingFile';
const String firstHalfKey = 'firstHalf';
const String fridayKey = 'friday';
const String fullKey = 'full';
const String galleryKey = 'gallery';
const String gradeKey = 'grade';
const String mondayKey = 'monday';
const String noKey = 'no';
const String otherDetailsKey = 'otherDetails';
const String percentageKey = 'percentage';
const String retryKey = 'retry';
const String saturdayKey = 'saturday';
const String searchHintKey = 'searchHint';
const String secondHalfKey = 'secondHalf';
const String semesterBreakKey = 'semesterBreak';
const String sundayKey = 'sunday';
const String thursdayKey = 'thursday';
const String toKey = 'to';
const String tuesdayKey = 'tuesday';
const String unknownKey = 'unknown';
const String viewAllKey = 'viewAll';
const String viewFileKey = 'viewFile';
const String wednesdayKey = 'wednesday';
const String yesKey = 'yes';

// The child switcher (eschool_session).
const String chooseChildKey = 'chooseChild';
const String switchChildKey = 'switchChild';
const String yourChildrenKey = 'yourChildren';

// The school switcher (eschool_session).
const String chooseSchoolKey = 'chooseSchool';
const String switchSchoolKey = 'switchSchool';

// The Notifications settings screen (eschool_notifications).
const String emailChannelKey = 'emailChannel';
const String inAppChannelKey = 'inAppChannel';
const String lockedByYourSchoolKey = 'lockedByYourSchool';
const String notificationSettingsHintKey = 'notificationSettingsHint';
const String notificationSettingsKey = 'notificationSettings';
const String pushChannelKey = 'pushChannel';

// Shared by the screens built once in the packages.
const String notAvailableYetKey = 'notAvailableYet';
const String nothingToSetUpYetKey = 'nothingToSetUpYet';

/// Every key above, for the apps' tests.
const List<String> sharedLabelKeys = [
  addMoreCharactorsToSearchKey,
  availableInMobileAppKey,
  cancelKey,
  dateKey,
  deleteDialogMessageKey,
  deleteDialogTitleKey,
  errorLoadingMoreTapToRetryKey,
  examNameKey,
  fileDownloadingKey,
  firstHalfKey,
  fridayKey,
  fullKey,
  galleryKey,
  gradeKey,
  mondayKey,
  noKey,
  otherDetailsKey,
  percentageKey,
  retryKey,
  saturdayKey,
  searchHintKey,
  secondHalfKey,
  semesterBreakKey,
  sundayKey,
  thursdayKey,
  toKey,
  tuesdayKey,
  unknownKey,
  viewAllKey,
  viewFileKey,
  wednesdayKey,
  yesKey,
  chooseChildKey,
  switchChildKey,
  yourChildrenKey,
  chooseSchoolKey,
  switchSchoolKey,
  emailChannelKey,
  inAppChannelKey,
  lockedByYourSchoolKey,
  notificationSettingsHintKey,
  notificationSettingsKey,
  pushChannelKey,
  notAvailableYetKey,
  nothingToSetUpYetKey,
];
