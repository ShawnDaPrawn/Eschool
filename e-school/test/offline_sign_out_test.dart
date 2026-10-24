import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool/data/repositories/authRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  test('offline sign out clears local access and handles the failed network request', () async {
    final directory = await Directory.systemTemp.createTemp(
      'offline-sign-out-',
    );
    Hive.init(directory.path);
    await Hive.openBox<dynamic>(SessionBoxKeys.authBox);
    addTearDown(() async {
      Api.debugCreateDio = null;
      await Hive.close();
      await directory.delete(recursive: true);
    });
    final api = FakeApi(
      routes: {
        'POST logout': (RequestOptions request) => throw DioException(
          requestOptions: request,
          type: DioExceptionType.connectionError,
        ),
      },
    );
    Api.debugCreateDio = () => Dio()..httpClientAdapter = api;
    final auth = AuthRepository();
    await auth.setIsLogIn(true);
    await auth.setJwtToken('synthetic-local-session');
    await auth.signOutUser();
    await Future<void>.delayed(Duration.zero);
    expect(auth.getIsLogIn(), false);
    expect(auth.getJwtToken(), '');
    expect(api.requested('POST logout'), true);
  });
}
