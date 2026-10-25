import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'case professional picker sends exact school calendar date and selection',
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
                  'message': 'Private choices',
                  'code': 200,
                  'data': {
                    'items': [
                      {'id': 7, 'name': 'Synthetic professional'},
                    ],
                    'page': 2,
                    'last_page': 3,
                    'selected_available': true,
                  },
                },
              ),
            );
          },
        ),
      );
      final result = await WellbeingApi(dio).listWellbeingCaseProfessionals(
        case_: 19,
        onDate: '2026-02-11',
        selected: 7,
        page: 2,
        search: 'Synthetic',
      );
      expect(sent!.path, '/wellbeing/cases/19/professionals');
      expect(sent!.queryParameters['on_date'], '2026-02-11');
      expect(sent!.queryParameters['selected'], 7);
      expect(sent!.queryParameters['page'], 2);
      expect(result.data!.data.selectedAvailable, true);
      expect(result.data!.data.items.single.id, 7);
    },
  );
  test(
    'older access responses omit optional timezone without invented default',
    () {
      final json = <String, dynamic>{
        'can_configure': false,
        'professional': true,
        'can_triage': false,
        'can_casework': true,
        'can_write': true,
        'today': '2026-02-10',
        'school_slug': 'synthetic',
        'emergency_guidance': 'Find trusted help',
      };
      final access = WellbeingAccess.fromJson(json);
      expect(access.timezone, isNull);
      expect(access.toJson(), json);
      expect(
        WellbeingAccess.fromJson({
          ...json,
          'timezone': 'Africa/Johannesburg',
        }).timezone,
        'Africa/Johannesburg',
      );
    },
  );
  test('anonymous follow-up code travels only in a POST body', () async {
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
                'message': 'Private result',
                'code': 200,
                'data': {
                  'anonymous': true,
                  'body': 'Synthetic concern',
                  'urgency': 'urgent',
                  'status': 'open',
                  'revision': 1,
                  'submitted_at': null,
                  'emergency_guidance':
                      'Seek immediate trusted help if unsafe.',
                  'threads': <Object>[],
                  'has_older': false,
                  'next_before': null,
                },
              },
            ),
          );
        },
      ),
    );
    final secret = List.filled(64, 'a').join();
    final result = await WellbeingApi(dio).getAnonymousIncidentFollowUp(
      schoolSlug: 'synthetic-school',
      incidentFollowUpInput: IncidentFollowUpInput(secret: secret),
    );
    expect(sent!.method, 'POST');
    expect(sent!.queryParameters, isEmpty);
    expect(sent!.uri.toString(), isNot(contains(secret)));
    expect(jsonDecode(sent!.data as String), {'secret': secret});
    expect(sent!.extra['secure'], isEmpty);
    expect(result.data!.data.anonymous, true);
    expect(result.data!.data.nextBefore, isNull);
  });
  test('anonymous write receipts carry no private code or report body', () {
    final source = {
      'received': true,
      'submitted_at': null,
      'emergency_guidance': 'Find trusted help.',
    };
    final receipt = IncidentSubmissionReceipt.fromJson(source);
    expect(receipt.toJson(), source);
    expect(receipt.toJson().keys, isNot(contains('secret')));
    expect(receipt.toJson().keys, isNot(contains('body')));
  });
  test(
    'clinical correction references and immutable operation UUID survive JSON',
    () {
      final source = {
        'operation_id': 'ca2bca07-b767-4b12-ac79-447508940778',
        'revision': 4,
        'kind': 'note',
        'body': 'Synthetic clinical correction',
        'happened_at': '2026-02-10T08:00:00.000Z',
        'supersedes_id': 19,
      };
      final input = WellbeingCaseEntryInput.fromJson(source);
      expect(input.supersedesId, 19);
      expect(input.toJson(), source);
    },
  );
  test('professional grant dates remain calendar dates across JSON', () {
    final source = {
      'operation_id': 'ca2bca07-b767-4b12-ac79-447508940778',
      'revision': 0,
      'user_id': 12,
      'starts_on': '2026-02-10',
      'ends_on': '2026-03-01',
      'active': true,
      'reason': 'Explicit clinical assignment',
    };
    final input = WellbeingAssignmentInput.fromJson(source);
    expect(input.startsOn, '2026-02-10');
    expect(input.toJson(), source);
  });
}
