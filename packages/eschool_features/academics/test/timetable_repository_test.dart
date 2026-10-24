import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'week and next requests pin the selected school and learner without a guessed device date',
    () async {
      Map<String, Object?> ok(Object data) => {
        'error': false,
        'message': 'Fetched',
        'code': 200,
        'data': data,
      };
      final adapter = FakeApi(
        routes: {
          'GET v2/academics/timetable/week': ok(timetableWeekFixture()),
          'GET v2/academics/timetable/next': ok({
            'lesson': timetableLessonFixture(),
          }),
        },
      );
      final client = ApiClient(
        authToken: () => 'synthetic-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = adapter,
      );
      final repository = ApiTimetableRepository(client, schoolId: 7);
      await repository.week(learner: 11);
      await repository.next(learner: 11);
      expect(adapter.requests.length, 2);
      for (final request in adapter.requests) {
        expect(request.headers['X-School-Id'], '7');
        expect(request.headers['Authorization'], 'Bearer synthetic-token');
        expect(request.queryParameters['learner_id'], 11);
        expect(request.queryParameters.containsKey('starts_on'), isFalse);
        expect(request.queryParameters.containsKey('session_year_id'), isFalse);
      }
    },
  );
}
