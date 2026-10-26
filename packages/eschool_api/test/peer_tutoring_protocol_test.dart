import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const operation = 'f216bd97-812e-4e3f-8afc-c986765a18db';
Map<String, dynamic> createIntent() => {
  'persona': 'staff',
  'operation_id': operation,
  'teaching_group_id': 4,
  'tutor_learner_id': 7,
  'tutee_learner_id': 8,
  'topic_id': 11,
  'venue': 'Library',
  'starts_at': '2026-11-02T14:00',
  'ends_at': '2026-11-02T14:30',
};
Map<String, dynamic> session() => {
  'id': operation,
  'revision': 1,
  'teaching_group_id': 4,
  'topic_id': 11,
  'topic_name': 'Fractions',
  'tutor_learner_id': 7,
  'tutor_name': 'Tutor',
  'tutee_learner_id': 8,
  'tutee_name': 'Learner',
  'supervisor_user_id': 12,
  'supervisor_name': 'Teacher',
  'starts_at': '2026-11-02T12:00:00Z',
  'ends_at': '2026-11-02T12:30:00Z',
  'venue': 'Library',
  'status': 'completed',
  'verified_minutes': 25,
  'service_minutes': null,
  'service_for_reader': false,
  'house_point_id': null,
  'can_complete': false,
  'can_cancel': false,
  'can_reverse': false,
};
Map<String, dynamic> config() => {
  'school_id': 1,
  'reader_id': 12,
  'persona': 'staff',
  'timezone': 'Africa/Johannesburg',
  'writable': false,
  'groups': [],
  'children': [],
  'can_award_points': false,
  'point_categories': [],
};
void main() {
  test('school-local schedule stays wall time on the wire', () {
    expect(PeerSessionCreate.fromJson(createIntent()).toJson(), createIntent());
  });
  test('tutee response preserves private service and point values as null', () {
    final result = PeerSession.fromJson(session());
    expect(result.serviceMinutes, isNull);
    expect(result.serviceForReader, isFalse);
    expect(result.housePointId, isNull);
    expect(result.toJson()['service_minutes'], isNull);
  });
  test(
    'verified service uses integer minutes without manufacturing point awards',
    () {
      final result = PeerSessionComplete.fromJson({
        'persona': 'staff',
        'operation_id': operation,
        'revision': 1,
        'minutes': 25,
      });
      expect(result.toJson()['minutes'], 25);
      expect(result.toJson()['points'], isNull);
      expect(result.toJson()['point_category_id'], isNull);
    },
  );
  test('readonly capabilities cannot be manufactured from omitted fields', () {
    final result = PeerConfiguration.fromJson(config());
    expect(result.writable, isFalse);
    expect(result.canAwardPoints, isFalse);
    expect(
      () => PeerConfiguration.fromJson({...config()}..remove('writable')),
      throwsA(anything),
    );
  });
  test(
    'typed create binds explicit account and school and keeps private body off query',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            sent = request;
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'OK',
                  'code': 200,
                  'data': {
                    'school_id': 1,
                    'reader_id': 12,
                    'session': session(),
                  },
                },
              ),
            );
          },
        ),
      );
      final result = await EschoolApi(dio: dio)
          .getPeerTutoringApi()
          .createPeerSession(
            peerSessionCreate: PeerSessionCreate.fromJson(createIntent()),
            xSchoolId: 1,
            xAccountId: 12,
          );
      expect(sent!.method, 'POST');
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(jsonDecode(sent!.data as String), createIntent());
      expect(result.data!.data.session.serviceMinutes, isNull);
      dio.close();
    },
  );
  test(
    'configuration carries explicit persona and current readonly capability',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            sent = request;
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'OK',
                  'code': 200,
                  'data': config(),
                },
              ),
            );
          },
        ),
      );
      final result = await EschoolApi(dio: dio)
          .getPeerTutoringApi()
          .getPeerConfiguration(persona: 'staff', xSchoolId: 1, xAccountId: 12);
      expect(sent!.queryParameters, {'persona': 'staff'});
      expect(result.data!.data.writable, isFalse);
      dio.close();
    },
  );
}
