import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_core/eschool_core.dart';

class ErrorMessageKeysAndCode {
  static const String defaultErrorMessageKey =
      ApiErrorCodes.defaultErrorMessageKey;
  static const String noInternetKey = 'noInternet';
  static const String internetServerErrorKey = 'internetServerError';
  static const String invalidLogInCredentialsKey = 'invalidLogInCredentials';
  static const String unauthorizedAccessKey = 'unauthorizedAccess';

  static const String assignmentAlreadySubmittedKey =
      'assignmentAlreadySubmitted';

  static String invalidUserDetailsKey = 'invalidUserDetails';

  static String invalidPasswordKey = 'invalidPassword';

  static String canNotSendResetPasswordRequestKey =
      'canNotSendResetPasswordRequest';

  static String nameAlreadyTakenKey = 'nameAlreadyTaken';

  //These are ui side error codes
  static const String internetServerErrorCode =
      ApiErrorCodes.internetServerError;
  static const String unauthorizedAccessCode = ApiErrorCodes.unauthorizedAccess;
  static const String fileNotFoundErrorCode = ApiErrorCodes.fileNotFound;
  static const String permissionNotGivenCode = '300';
  static const String noInternetCode = ApiErrorCodes.noInternet;
  static const String defaultErrorMessageCode = ApiErrorCodes.defaultError;

  static String getErrorMessageKeyFromCode(String errorCode) {
    //
    if (errorCode == '101') {
      return invalidLogInCredentialsKey;
    }
    if (errorCode == '104') {
      return assignmentAlreadySubmittedKey;
    }

    if (errorCode == '107') {
      return invalidUserDetailsKey;
    }

    if (errorCode == '108') {
      return canNotSendResetPasswordRequestKey;
    }

    if (errorCode == '109') {
      return invalidPasswordKey;
    }
    if (errorCode == '112') {
      return featureDisableInDemoVersionKey;
    }
    if (errorCode == permissionNotGivenCode) {
      return permissionsNotGivenKey;
    }
    if (errorCode == '113') {
      return nameAlreadyTakenKey;
    } else if (errorCode == unauthorizedAccessCode) {
      return unauthorizedAccessKey;
    }
    if (errorCode == noInternetCode) {
      return noInternetKey;
    }
    if (errorCode == internetServerErrorCode) {
      return internetServerErrorKey;
    }
    if (errorCode == fileNotFoundErrorCode) {
      return fileDownloadingFailedKey;
    }
    if (errorCode == defaultErrorMessageCode) {
      return defaultErrorMessageKey;
    } else {
      return defaultErrorMessageKey;
    }
  }
}
