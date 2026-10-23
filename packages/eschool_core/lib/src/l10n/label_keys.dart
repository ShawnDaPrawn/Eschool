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

// The people screens (eschool_people).
const String approvalsKey = 'approvals';
const String approvalsWaitingKey = 'approvalsWaiting';
const String approvalsGivenKey = 'approvalsGiven';
const String reviewAndApproveKey = 'reviewAndApprove';
const String approveAccessKey = 'approveAccess';
const String withdrawApprovalKey = 'withdrawApproval';
const String withdrawApprovalTitleKey = 'withdrawApprovalTitle';
const String withdrawApprovalMessageKey = 'withdrawApprovalMessage';
const String approvalConsentTitleKey = 'approvalConsentTitle';
const String noApprovalsKey = 'noApprovals';
const String studentNumberLabelKey = 'studentNumberLabel';
const String approvalWithdrawnKey = 'approvalWithdrawn';
const String invitationKey = 'invitation';
const String invitedAsParentOfKey = 'invitedAsParentOf';
const String invitationForKey = 'invitationFor';
const String invitationExpiresKey = 'invitationExpires';
const String createPasswordKey = 'createPassword';
const String repeatPasswordKey = 'repeatPassword';
const String passwordTooShortKey = 'passwordTooShort';
const String passwordsDoNotMatchKey = 'passwordsDoNotMatch';
const String acceptInvitationKey = 'acceptInvitation';
const String invitationNotValidKey = 'invitationNotValid';
const String showPasswordKey = 'showPassword';
const String hidePasswordKey = 'hidePassword';
const String mySchoolKey = 'mySchool';
const String schoolContactsKey = 'schoolContacts';
const String callSchoolKey = 'callSchool';
const String emailSchoolKey = 'emailSchool';
const String schoolWebsiteKey = 'schoolWebsite';
const String schoolAddressKey = 'schoolAddress';
const String schoolDocumentsKey = 'schoolDocuments';
const String termDatesKey = 'termDates';
const String schoolDetailsNotAddedYetKey = 'schoolDetailsNotAddedYet';
const String couldNotOpenLinkKey = 'couldNotOpenLink';
const String profilePictureKey = 'profilePicture';
const String chooseAvatarKey = 'chooseAvatar';
const String uploadPhotoKey = 'uploadPhoto';
const String photoWaitingForSchoolKey = 'photoWaitingForSchool';
const String profilePictureSavedKey = 'profilePictureSaved';
const String avatarBoysKey = 'avatarBoys';
const String avatarGirlsKey = 'avatarGirls';
const String avatarMenKey = 'avatarMen';
const String avatarWomenKey = 'avatarWomen';

const String handleLabelKey = 'handleLabel';
const String handleSettingsKey = 'handleSettings';
const String emailOrHandleKey = 'emailOrHandle';
const String learnerLoginKey = 'learnerLogin';
const String handleRulesKey = 'handleRules';
const String handlePendingKey = 'handlePending';
const String handleSavedKey = 'handleSaved';
const String handleChangeAfterKey = 'handleChangeAfter';
const String handleChooseKey = 'handleChoose';
const String handleRequestsKey = 'handleRequests';
const String handleNoRequestsKey = 'handleNoRequests';
const String handleApproveKey = 'handleApprove';
const String handleRejectKey = 'handleReject';
const String handleVerifyEmailKey = 'handleVerifyEmail';

/// Every key above, for the apps' tests.
const List<String> sharedLabelKeys = [
  handleLabelKey,
  handleSettingsKey,
  emailOrHandleKey,
  learnerLoginKey,
  handleRulesKey,
  handlePendingKey,
  handleSavedKey,
  handleChangeAfterKey,
  handleChooseKey,
  handleRequestsKey,
  handleNoRequestsKey,
  handleApproveKey,
  handleRejectKey,
  handleVerifyEmailKey,

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
  approvalsKey,
  approvalsWaitingKey,
  approvalsGivenKey,
  reviewAndApproveKey,
  approveAccessKey,
  withdrawApprovalKey,
  withdrawApprovalTitleKey,
  withdrawApprovalMessageKey,
  approvalConsentTitleKey,
  noApprovalsKey,
  studentNumberLabelKey,
  approvalWithdrawnKey,
  invitationKey,
  invitedAsParentOfKey,
  invitationForKey,
  invitationExpiresKey,
  createPasswordKey,
  repeatPasswordKey,
  passwordTooShortKey,
  passwordsDoNotMatchKey,
  acceptInvitationKey,
  invitationNotValidKey,
  showPasswordKey,
  hidePasswordKey,
  mySchoolKey,
  schoolContactsKey,
  callSchoolKey,
  emailSchoolKey,
  schoolWebsiteKey,
  schoolAddressKey,
  schoolDocumentsKey,
  termDatesKey,
  schoolDetailsNotAddedYetKey,
  couldNotOpenLinkKey,
  profilePictureKey,
  chooseAvatarKey,
  uploadPhotoKey,
  photoWaitingForSchoolKey,
  profilePictureSavedKey,
  avatarBoysKey,
  avatarGirlsKey,
  avatarMenKey,
  avatarWomenKey,
];
