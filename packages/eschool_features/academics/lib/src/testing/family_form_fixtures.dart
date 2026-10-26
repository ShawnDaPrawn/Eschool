import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

// Synthetic transport only; no production records, scanner, or network.
const consentId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
const fileToken = '8aef50e7-2429-4d2c-a523-668ae436c4d4.png';
const privateWording = 'Synthetic complete private consent wording.';
Map<String, dynamic> _configJson({
  int school = 1,
  int reader = 7,
  bool writable = true,
}) => {
  'school_id': school,
  'reader_id': reader,
  'children': [
    {'id': 11, 'person_id': 12, 'name': 'Synthetic child'},
  ],
  'groups': [
    {'id': 3, 'name': 'Synthetic activity', 'session_year_id': 1},
  ],
  'writable': writable,
  'signature_name': 'Synthetic Guardian',
  'timezone': 'Africa/Johannesburg',
  'session_year_id': 1,
};
Map<String, dynamic> _summaryJson() => {
  'id': consentId,
  'title': 'Synthetic trip',
  'activity_group_id': 3,
  'session_year_id': 1,
  'status': 'open',
  'event_starts_at': '2099-02-20T08:00:00Z',
  'event_ends_at': '2099-02-20T10:00:00Z',
  'respond_by': '2099-02-19T10:00:00Z',
};
Map<String, dynamic> _detailJson({String? decision, int revision = 0}) => {
  ...(_summaryJson()
    ..remove('activity_group_id')
    ..remove('session_year_id')),
  'learner_id': 11,
  'wording': privateWording,
  'fingerprint': 'a' * 64,
  'revision': revision,
  'decision': decision,
  'signed_at': decision == null ? null : '2026-10-26T10:00:00Z',
};
Map<String, dynamic> _healthJson({int person = 12, bool file = false}) => {
  'person_id': person,
  'profile_type': 'learner',
  'available_profiles': ['learner'],
  'categories': [
    {
      'id': 1,
      'key': 'health',
      'label': 'Health',
      'fields': [
        {
          'id': 1,
          'key': 'allergies',
          'label': 'Synthetic allergy',
          'type': 'textarea',
          'validation': <String, dynamic>{},
          'options': <String>[],
          'required': false,
          'locked': false,
          'editable': true,
          'sensitive': true,
          'value': {'text': 'Synthetic private allergy'},
        },
        if (file)
          {
            'id': 2,
            'key': 'health_document',
            'label': 'Synthetic document',
            'type': 'file',
            'validation': <String, dynamic>{},
            'options': <String>[],
            'required': false,
            'locked': false,
            'editable': true,
            'sensitive': true,
            'value': {
              'file': {
                'id': fileToken,
                'name': 'Synthetic medical image',
                'mime': 'image/png',
                'size': 67,
                'url': 'https://untrusted.example/private',
              },
            },
          },
      ],
    },
  ],
};

class FamilyFormSmokeFixture {
  FamilyFormSmokeFixture({required this.accountId, this.staff = false});
  final int accountId;
  final bool staff;
  bool allowed = true, unknown = true;
  int revision = 0, effects = 0, saves = 0;
  String? decision;
  String allergy = 'Synthetic private allergy';
  final receipts = <String, Map<String, dynamic>>{};
  Object result(Object data) => allowed
      ? {'error': false, 'data': data}
      : const FakeResponse({
          'error': true,
          'message': 'Access withdrawn',
        }, status: 403);
  Map<String, Object> get routes => {
    'GET v2/family-forms/configuration': (_) => result({
      ..._configJson(reader: accountId),
      'children': staff
          ? []
          : [
              {'id': 11, 'person_id': 12, 'name': 'Synthetic child'},
            ],
      'groups': staff
          ? [
              {'id': 3, 'name': 'Synthetic activity', 'session_year_id': 1},
            ]
          : [],
    }),
    'GET v2/family-forms/consents': (_) => result({
      'items': [_summaryJson()],
      'next_page': null,
    }),
    'GET v2/family-forms/consents/$consentId': (_) =>
        result(_detailJson(decision: decision, revision: revision)),
    'GET v2/family-forms/consents/$consentId/managed': (_) => result({
      ..._summaryJson(),
      'wording': privateWording,
      'fingerprint': 'a' * 64,
      'revision': 1,
      'next_page': null,
      'recipients': [
        {
          'learner_id': 11,
          'name': 'Synthetic child',
          'current': true,
          'revision': 1,
          'decision': 'yes',
          'signed_at': '2026-10-26T10:00:00Z',
          'effective': false,
        },
      ],
    }),
    'POST v2/family-forms/consents/$consentId/learners/11/response':
        (RequestOptions request) {
          if (!allowed) return result({});
          final body = Map<String, dynamic>.from(request.data as Map);
          final receipt = receipts.putIfAbsent(
            body['operation_id'] as String,
            () {
              effects++;
              revision++;
              decision = body['decision'] as String;
              return {
                'id': consentId,
                'request_id': consentId,
                'learner_id': 11,
                'decision': decision,
                'revision': revision,
                'signed_at': '2026-10-26T10:00:00Z',
              };
            },
          );
          if (unknown) {
            unknown = false;
            throw DioException(
              requestOptions: request,
              type: DioExceptionType.connectionError,
            );
          }
          return result(receipt);
        },
    'GET v2/family-forms/learners/11/health': (_) => result(_health()),
    'PUT v2/family-forms/learners/11/health': (RequestOptions request) {
      if (!allowed) return result({});
      saves++;
      allergy =
          ((request.data as Map)['values']['allergies']['text']) as String;
      return result(_health());
    },
  };
  Map<String, dynamic> _health() {
    final form = _healthJson();
    ((form['categories'] as List).single['fields'] as List).first['value'] = {
      'text': allergy,
    };
    return form;
  }
}
