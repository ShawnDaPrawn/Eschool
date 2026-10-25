import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  final schedule = <String, dynamic>{
    'version': 'fsrs4anki-4.5.0-8180871-daily-v1',
    'rating': 3,
    'difficulty': 4.93,
    'stability_days': 2.4,
    'retrievability_before': null,
    'interval_days': 2,
  };
  final definition = <String, dynamic>{
    'kind': 'number',
    'prompt': 'One half as a decimal?',
    'explanation': 'Divide one by two.',
    'options': <dynamic>[],
    'answers': ['0.5'],
  };
  test(
    'calendar strings and nullable memory survive approved daily-set serialization',
    () {
      final value = <String, dynamic>{
        'id': 9,
        'school_id': 1,
        'reader_id': 12,
        'learner_id': 3,
        'group_id': 4,
        'practice_on': '2026-02-10',
        'server_time': '2026-02-10T10:00:00.000Z',
        'offline_until': '2026-02-10T21:59:59.999Z',
        'estimated_seconds': 120,
        'completed': false,
        'streak_days': 0,
        'items': [
          {
            'id': 19,
            'item_id': 29,
            'topic_id': 39,
            'source_version_id': 49,
            'estimated_seconds': 120,
            'definition': definition,
            'review': null,
          },
        ],
      };
      final set = PracticeDailySet.fromJson(value);
      expect(set.practiceOn, '2026-02-10');
      expect(set.serverTime.isUtc, isTrue);
      expect(set.items.single.definition.answers, ['0.5']);
      expect(set.items.single.review, isNull);
      expect(
        PracticeDailySet.fromJson(
          jsonDecode(jsonEncode(set.toJson())) as Map<String, dynamic>,
        ).toJson(),
        set.toJson(),
      );
      final memory = PracticeSchedule.fromJson(schedule);
      expect(memory.retrievabilityBefore, isNull);
      expect(memory.toJson()['retrievability_before'], isNull);
    },
  );
  test(
    'the optional Teacher authoring flag preserves old profile omission',
    () {
      final old = FacilitatorFeatures.fromJson({
        'groups': true,
        'messages': true,
        'counselling': false,
      });
      expect(old.dailyPractice, isNull);
      expect(old.toJson().containsKey('daily_practice'), isFalse);
      expect(
        FacilitatorFeatures.fromJson({
          ...old.toJson(),
          'daily_practice': true,
        }).dailyPractice,
        isTrue,
      );
    },
  );
  test(
    'typed review keeps exact operation, answer, scope and path identities',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      RequestOptions? sent;
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            sent = options;
            handler.resolve(
              Response(
                requestOptions: options,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'Saved',
                  'code': 200,
                  'data': {
                    'id': 59,
                    'entry_id': 19,
                    'correct': true,
                    'schedule': schedule,
                    'reviewed_on': '2026-02-10',
                    'streak_days': 1,
                  },
                },
              ),
            );
          },
        ),
      );
      final input = PracticeReviewInput(
        operationId: '00000000-0000-4000-8000-000000000001',
        answer: '.50',
        rating: 3,
      );
      final response = await PracticeApi(dio).reviewPracticeEntry(
        learner: 3,
        set_: 9,
        entry: 19,
        practiceReviewInput: input,
        xAccountId: 12,
        xSchoolId: 1,
      );
      expect(
        sent!.path,
        '/learning/practice/learners/3/daily-sets/9/entries/19/review',
      );
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.headers['X-School-Id'], 1);
      expect(
        sent!.data is String ? jsonDecode(sent!.data as String) : sent!.data,
        input.toJson(),
      );
      expect(response.data!.data.reviewedOn, '2026-02-10');
      expect(response.data!.data.entryId, 19);
    },
  );
}
