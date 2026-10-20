import 'dart:io';

import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/models/student.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:hive/hive.dart';
import 'package:intl/intl.dart';

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
    return Hive.box(authBoxKey).get(jwtTokenKey) ?? '';
  }

  Future<void> setJwtToken(String value) async {
    return Hive.box(authBoxKey).put(jwtTokenKey, value);
  }

  Future<void> signOutUser() async {
    try {
      Api.post(body: {}, url: Api.logout, useAuthToken: true);
    } catch (e) {
      //
    }
    setIsLogIn(false);
    setJwtToken('');
    setStudentDetails(Student.fromJson({}));
    setParentDetails(Parent.fromJson({}));
  }

  //RemoteDataSource
  Future<Map<String, dynamic>> signInStudent({
    required String grNumber,
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
      'gr_number': grNumber,
      'fcm_id': fcmToken,
      'device_type': Platform.isAndroid ? 'android' : 'ios',
    };

    final result = await Api.post(
      body: body,
      url: Api.studentLogin,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'],
      'student': Student.fromJson(Map.from(result['data'])),
    };
  }

  Future<Map<String, dynamic>> signInParent({
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
      url: Api.parentLogin,
      useAuthToken: false,
    );

    return {
      'jwtToken': result['token'],
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
