import 'dart:io';

import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:hive_flutter/hive_flutter.dart';

class AuthRepository {
  //LocalDataSource
  bool getIsLogIn() {
    return Hive.box(authBoxKey).get(isLogInKey) ?? false;
  }

  Future<void> setIsLogIn(bool value) async {
    return Hive.box(authBoxKey).put(isLogInKey, value);
  }

  static bool getHaSubscribedToNotificationTopics() {
    return Hive.box(authBoxKey).get(hasSubscribedToNotificationTopicsKey) ??
        false;
  }

  static Future<void> setHaSubscribedToNotificationTopics(bool value) async {
    return Hive.box(
      authBoxKey,
    ).put(hasSubscribedToNotificationTopicsKey, value);
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
    return Hive.box(authBoxKey).get(jwtTokenKey) ?? '';
  }

  Future<void> setJwtToken(String value) async {
    return Hive.box(authBoxKey).put(jwtTokenKey, value);
  }

  Future<void> signOutUser() async {
    try {
      Api.post(body: {}, url: Api.logout, useAuthToken: true);
    } catch (e) {
      null;
    }
    setIsLogIn(false);
    setJwtToken('');
    setTeacherDetails(Teacher.fromJson({}));
  }

  //RemoteDataSource
  Future<Map<String, dynamic>> signInTeacher({
    required String email,
    required String password,
  }) async {
    //to avoid errors when token is not retrieved in simulators
    final fcmToken = await () async {
      try {
        return await FirebaseMessaging.instance.getToken();
      } catch (_) {
        return '';
      }
    }();

    debugPrint('FCM Token: $fcmToken');

    final body = {
      'password': password,
      'email': email,
      'fcm_id': fcmToken,
      'device_type': Platform.isAndroid ? 'android' : 'ios',
    };

    final result = await Api.post(
      body: body,
      url: Api.login,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'],
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
