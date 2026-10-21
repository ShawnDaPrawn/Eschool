import 'dart:io';

import 'package:eschool_teacher/data/models/teacher.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
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
