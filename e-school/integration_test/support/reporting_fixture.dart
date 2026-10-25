import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

class ReportingFixture {
  bool denied = false;
  String code = '', body = '';
  int revision = 1;
  final submissions = <Map<String, dynamic>>[],
      threads = <Map<String, dynamic>>[],
      photos = <Map<String, dynamic>>[];
  Map<String, dynamic> ok(Object? data) => {
    'error': false,
    'message': 'Private result',
    'code': 200,
    'data': data,
  };
  Map<String, dynamic> input(RequestOptions request) =>
      Map<String, dynamic>.from(
        request.data is String ? jsonDecode(request.data) : request.data as Map,
      );
  Map<String, dynamic> view() => {
    'anonymous': false,
    'body': body,
    'urgency': 'routine',
    'status': 'open',
    'revision': revision,
    'submitted_at': '2026-02-10T08:00:00Z',
    'emergency_guidance': 'Speak to a trusted adult now if unsafe.',
    'threads': threads,
    'has_older': false,
    'next_before': null,
    'photos': photos,
  };
  late final Map<String, Object> routes = {
    'GET v2/wellbeing/access': (RequestOptions _) => denied
        ? const FakeResponse({'message': 'Forbidden'}, status: 403)
        : ok({
            'can_configure': false,
            'professional': false,
            'can_triage': false,
            'can_casework': false,
            'can_write': true,
            'today': '2026-02-10',
            'school_slug': 'synthetic',
            'emergency_guidance': 'Speak to a trusted adult now if unsafe.',
          }),
    'GET v2/wellbeing/public/synthetic/configuration': ok({
      'emergency_guidance': 'Speak to a trusted adult now if unsafe.',
      'photo_constraints': {
        'maximum_bytes': 4194304,
        'maximum_photos': 5,
        'allowed_mimes': ['image/jpeg', 'image/png'],
      },
      'categories': [
        {
          'id': 1,
          'name': 'Safety concern',
          'emergency_guidance': 'Seek trusted help',
        },
      ],
    }),
    'POST v2/wellbeing/own/photos': (RequestOptions request) {
      if (denied)
        return const FakeResponse({'message': 'Forbidden'}, status: 403);
      final form = request.data as FormData;
      if (Map.fromEntries(form.fields)['secret'] != code)
        return const FakeResponse({'message': 'Forbidden'}, status: 403);
      revision++;
      photos.add({
        'id': '11111111-1111-4111-8111-111111111111',
        'mime': 'image/jpeg',
        'bytes': 80,
        'width': 1,
        'height': 1,
        'thumbnail_base64': 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNQcGj4DwADRAHgMqq/hAAAAABJRU5ErkJggg==',
        'created_at': '2026-02-10T08:00:00Z',
        'report_revision': revision,
      });
      return ok(photos.last);
    },
    'POST v2/wellbeing/own/reports': (RequestOptions request) {
      final data = input(request);
      submissions.add(data);
      code = data['secret'];
      body = data['body'];
      return ok({
        'received': true,
        'submitted_at': '2026-02-10T08:00:00Z',
        'emergency_guidance': 'Seek trusted help',
      });
    },
    'POST v2/wellbeing/own/follow-up': (RequestOptions request) =>
        !denied && input(request)['secret'] == code
        ? ok(view())
        : const FakeResponse({'message': 'Forbidden'}, status: 403),
    'POST v2/wellbeing/own/follow-up/replies': (RequestOptions request) {
      final data = input(request);
      threads.add({
        'author': 'reporter',
        'body': data['body'],
        'created_at': '2026-02-10T08:01:00Z',
      });
      revision++;
      return ok({
        'posted': true,
        'operation_id': data['operation_id'],
        'created_at': '2026-02-10T08:01:00Z',
      });
    },
  };
}
