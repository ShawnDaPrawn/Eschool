import 'dart:async';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:hive_flutter/hive_flutter.dart';

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

  Teacher getTeacherDetails() {
    return Teacher.fromJson(
      Map.from(Hive.box(authBoxKey).get(teacherDetailsKey) ?? {}),
    );
  }

  Future<void> setTeacherDetails(Teacher teacher) async {
    return Hive.box(authBoxKey).put(teacherDetailsKey, teacher.toJson());
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
      await SecureRegisterStore.purge();
    } catch (_) {
      /* A closed register key cannot serve another context. */
    }
    try {
      await SecureContentStore.purge();
    } catch (_) {
      /* Closed keys cannot serve another context. */
    }
    setTeacherDetails(Teacher.fromJson({}));
  }

  //RemoteDataSource
  Future<Map<String, dynamic>> signInTeacher({
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
      url: Api.login,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'] ?? '',
      'teacher': Teacher.fromJson(Map.from(result['data'])),
    };
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

  Future<Teacher?> fetchTeacherProfile() async {
    try {
      return Teacher.fromJson(
        await Api.get(
          url: Api.profile,
          useAuthToken: true,
        ).then((value) => value['data']),
      );
    } catch (e) {
      return null;
      // throw ApiException(e.toString());
    }
  }
}
