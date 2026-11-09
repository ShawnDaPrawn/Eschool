import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('guardian handle proposals require an identified learner', () {
    final json = <String, dynamic>{
      'id': 21,
      'handle': 'orbit',
      'expires_at': '2026-11-01T12:00:00.000Z',
      'learner': {'id': 5, 'name': 'Asha'},
    };
    final proposal = GuardianHandleProposal.fromJson(json);
    expect(proposal.learner.name, 'Asha');
    expect(proposal.toJson(), json);
    expect(
      () => GuardianHandleProposal.fromJson({...json, 'learner': null}),
      throwsA(anything),
    );
  });

  test(
    'practice contract 2 keeps pending marks nullable and validates its own fields',
    () {
      final json = <String, dynamic>{
        'id': 59,
        'entry_id': 19,
        'state': 'awaiting_teacher',
        'correct': null,
        'achieved_units': null,
        'maximum_units': 1,
        'parts': null,
        'marked_how': 'teacher',
        'schedule': null,
        'reviewed_on': '2026-02-10',
        'solution': {
          'explanation': '',
          'answers': <String>[],
          'steps': <Object>[],
          'mapping': <Object>[],
        },
        'streak_days': 1,
      };
      final response = ReviewPracticeEntryResponseData.fromJson(json);
      expect(response.legacy, isNull);
      expect(response.v2, isNotNull);
      expect(response.correct, isNull);
      expect(response.schedule, isNull);
      expect(response.toJson(), json);
      expect(
        () => ReviewPracticeEntryResponseData.fromJson(
          {...json}..remove('maximum_units'),
        ),
        throwsA(anything),
      );
    },
  );

  test('a practice contract 2 set preserves answer-free questions', () {
    final json = <String, dynamic>{
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
          'review': null,
          'question': {
            'kind': 'number',
            'prompt': '2 + 2?',
            'options': <Object>[],
          },
        },
      ],
    };
    final response = IssuePracticeDailySetResponseData.fromJson(json);
    expect(response.legacy, isNull);
    expect(response.v2!.items.single.question.prompt, '2 + 2?');
    expect(response.toJson(), json);
    expect(() => response.items, throwsStateError);
  });

  test('an omitted audience kind uses its fixed enum default', () {
    final audience = CommunicationAudienceWrite.fromJson({
      'operation_id': '11111111-1111-4111-8111-111111111111',
      'revision': 1,
      'session_year_id': 1,
      'name': 'Test audience',
      'active': true,
    });
    expect(audience.kind, CommunicationAudienceWriteKindEnum.fixed);
    expect(audience.toJson()['kind'], 'fixed');
  });

  test('Boolean acknowledgement enums serialize as JSON booleans', () {
    final input = SubmitBursaryApplicationRequest.fromJson({
      'operation_id': '11111111-1111-4111-8111-111111111111',
      'revision': 1,
      'confirmed': true,
    });
    expect(input.confirmed, SubmitBursaryApplicationRequestConfirmedEnum.true_);
    expect(input.toJson()['confirmed'], isTrue);
  });

  test('an unknown list value maps to the enum fallback', () {
    final availability = LearnerPassportExportAvailability.fromJson({
      'available': true,
      'formats': ['pdf', 'future_format'],
    });
    expect(availability.formats, [
      LearnerPassportExportAvailabilityFormatsEnum.pdf,
      LearnerPassportExportAvailabilityFormatsEnum.unknownDefaultOpenApi,
    ]);
  });

  for (final code in <Object?>[0, 'signed_out', null]) {
    test('sign-out retains null data and scalar code $code', () {
      final json = {
        'error': false,
        'message': 'Signed out',
        'data': null,
        'code': code,
      };
      final response = PoolSignOutResponse.fromJson(json);
      expect(response.data, isNull);
      expect(response.code?.value, code);
      expect(response.toJson(), json);
    });
  }

  test('a null-only sign-out payload rejects unexpected data', () {
    expect(
      () => PoolSignOutResponse.fromJson({
        'error': false,
        'message': 'Signed out',
        'data': {'private': true},
        'code': 0,
      }),
      throwsA(anything),
    );
  });

  test('mixed scalar codes reject arrays, objects, booleans and decimals', () {
    for (final value in <Object>[[], {}, true, 1.5]) {
      expect(
        () => PoolMemberPageResponseCode.fromJson(value),
        throwsFormatException,
      );
    }
  });
}
