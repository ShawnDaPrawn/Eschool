import 'dart:async';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:hive/hive.dart';
import 'package:intl/intl.dart';

class AuthRepository {
  //LocalDataSource
  bool getIsLogIn() {
    return Api.session.isSignedIn;
  }

  Future<void> setIsLogIn(bool value) async {
    return Api.session.setSignedIn(value);
  }

  static bool getHaSubscribedToNotificationTopics() {
    return Api.session.hasSubscribedToNotificationTopics;
  }

  static Future<void> setHaSubscribedToNotificationTopics(bool value) async {
    return Api.session.setHasSubscribedToNotificationTopics(value);
  }

  bool getIsStudentLogIn() {
    return Hive.box(authBoxKey).get(isStudentLogInKey) ?? false;
  }

  Future<void> setIsStudentLogIn(bool value) async {
    return Hive.box(authBoxKey).put(isStudentLogInKey, value);
  }

  Student getStudentDetails() {
    return Student.fromJson(
      Map.from(Hive.box(authBoxKey).get(studentDetailsKey) ?? {}),
    );
  }

  Future<void> setStudentDetails(Student student) async {
    return Hive.box(authBoxKey).put(studentDetailsKey, student.toJson());
  }

  Parent getParentDetails() {
    return Parent.fromJson(
      Map.from(Hive.box(authBoxKey).get(parentDetailsKey) ?? {}),
    );
  }

  Future<void> setParentDetails(Parent parent) async {
    return Hive.box(authBoxKey).put(parentDetailsKey, parent.toJson());
  }

  String getJwtToken() {
    return Api.session.token;
  }

  Future<void> setJwtToken(String value) async {
    return Api.session.setToken(value);
  }

  Future<void> signOutUser() async {
    try {
      unawaited(
        Api.post(
          body: {},
          url: Api.logout,
          useAuthToken: true,
        ).then<void>((_) {}, onError: (Object error, StackTrace stack) {}),
      );
    } catch (_) {}
    // Invalidate both in-memory session values before asynchronous cache erasure.
    final clearLogin = setIsLogIn(false), clearToken = setJwtToken('');
    await Future.wait([clearLogin, clearToken]);
    try {
      await SecureContentStore.purge();
    } catch (_) {
      /* Closed keys cannot serve another context. */
    }
    setStudentDetails(Student.fromJson({}));
    setParentDetails(Parent.fromJson({}));
  }

  //RemoteDataSource
  Future<Map<String, dynamic>> signInStudent({
    required String grNumber,
    required String password,
  }) async {
    final body = {
      'password': password,
      'gr_number': grNumber,
      //the FCM token and device type; none in a browser
      ...await NotificationUtility.signInDeviceFields(),
    };

    final result = await Api.post(
      body: body,
      url: Api.studentLogin,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'] ?? '',
      'student': Student.fromJson(Map.from(result['data'])),
    };
  }

  Future<Map<String, dynamic>> signInParent({
    required String email,
    required String password,
  }) async {
    final body = {
      'password': password,
      'email': email,
      //the FCM token and device type; none in a browser
      ...await NotificationUtility.signInDeviceFields(),
    };

    final result = await Api.post(
      body: body,
      url: Api.parentLogin,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'] ?? '',
      'parent': Parent.fromJson(Map.from(result['data'])),
    };
  }

  Future<void> resetPasswordRequest({
    required String grNumber,
    required DateTime dob,
  }) async {
    final body = {
      'gr_no': grNumber,
      'dob': DateFormat('yyyy-MM-dd').format(dob),
    };
    await Api.post(
      body: body,
      url: Api.requestResetPassword,
      useAuthToken: false,
    );
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String newConfirmedPassword,
  }) async {
    final body = {
      'current_password': currentPassword,
      'new_password': newPassword,
      'new_confirm_password': newConfirmedPassword,
    };
    await Api.post(body: body, url: Api.changePassword, useAuthToken: true);
  }

  Future<void> forgotPassword({required String email}) async {
    final body = {'email': email};
    await Api.post(body: body, url: Api.forgotPassword, useAuthToken: false);
  }

  Future<Student?> fetchStudentProfile() async {
    try {
      return Student.fromJson(
        await Api.get(
          url: Api.studentProfile,
          useAuthToken: true,
        ).then((value) => value['data']),
      );
    } catch (e) {
      return null;
    }
  }

  Future<Parent?> fetchParentProfile() async {
    try {
      return Parent.fromJson(
        await Api.get(
          url: Api.parentProfile,
          useAuthToken: true,
        ).then((value) => value['data']),
      );
    } catch (e) {
      return null;
    }
  }
}
