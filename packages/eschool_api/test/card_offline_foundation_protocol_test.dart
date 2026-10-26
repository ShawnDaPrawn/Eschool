import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const reference = 'f216bd97-812e-4e3f-8afc-c986765a18db';
Map<String, dynamic> offlinePacket() => {
  'request_id': reference,
  'envelope': {
    'suite': 'X25519-HKDF-SHA256-A256GCM',
    'context': {
      'school_id': 1,
      'device_id': reference,
      'snapshot_id': reference,
      'issued_at': 1000,
      'expires_at': 1060,
    },
    'ephemeral_public_key': 'synthetic',
    'salt': 'synthetic',
    'nonce': 'synthetic',
    'tag': 'synthetic',
    'ciphertext': 'synthetic',
  },
  'signing_key_id': reference,
  'signing_public_key': 'synthetic',
  'next_after': null,
};
Map<String, dynamic> restrictedContext() => {
  'school_id': 1,
  'learner_id': 7,
  'name': 'Synthetic learner',
  'teaching_group_id': 8,
  'expires_at': '2026-11-02T12:05:00Z',
  'restricted': true,
  'capabilities': ['daily_practice'],
};
void main() {
  test(
    'offline packet preserves integer seconds, fresh challenge and explicit null cursor',
    () {
      final value = SecureOfflineSnapshot.fromJson(offlinePacket());
      expect(value.envelope.context.expiresAt, 1060);
      expect(value.requestId, reference);
      expect(value.nextAfter, isNull);
      expect(value.toJson(), offlinePacket());
      expect(
        () => SecureOfflineSnapshot.fromJson(
          {...offlinePacket()}..remove('request_id'),
        ),
        throwsA(anything),
      );
    },
  );
  test('history receipt never invents collection or sign-in capability', () {
    final value = SecureOfflineReceipt.fromJson({
      'scan_id': reference,
      'card_id': reference,
      'outcome': 'offline_accepted',
      'collection_authorized': false,
      'sign_in_authorized': false,
    });
    expect(value.collectionAuthorized, isFalse);
    expect(value.signInAuthorized, isFalse);
    expect(value.outcome, 'offline_accepted');
  });
  test(
    'offline request keeps the original UUID and proof in the body, not the URL',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      final api = EschoolApi(dio: dio)
        ..setBearerAuth('CardDeviceCredential', 'synthetic-device');
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
                    'scan_id': reference,
                    'card_id': reference,
                    'outcome': 'offline_accepted',
                    'collection_authorized': false,
                    'sign_in_authorized': false,
                  },
                },
              ),
            );
          },
        ),
      );
      final input = {
        'operation_id': reference,
        'manifest': 'NEOS1.synthetic',
        'credential': 'NEQ1.${DateTime.now().microsecondsSinceEpoch}',
        'scanned_at': 1001,
      };
      await api.getSecureCardsApi().reconcileSecureOfflineScan(
        secureOfflineReconciliation: SecureOfflineReconciliation.fromJson(
          input,
        ),
        xSchoolId: 1,
      );
      expect(sent!.method, 'POST');
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.headers['Authorization'], 'Bearer synthetic-device');
      expect(sent!.headers['X-School-Id'], 1);
      expect(jsonDecode(sent!.data as String), input);
      dio.close();
    },
  );
  test(
    'offline GET requires its challenge and carries only public cursor query metadata',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      final api = EschoolApi(dio: dio);
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
                  'data': offlinePacket(),
                },
              ),
            );
          },
        ),
      );
      final result = await api.getSecureCardsApi().getSecureOfflineSnapshot(
        requestId: reference,
        xSchoolId: 1,
      );
      expect(sent!.queryParameters, {'request_id': reference});
      expect(result.data!.data.requestId, reference);
      dio.close();
    },
  );
  test(
    'restricted classroom context requires explicit capabilities and preserves UTC instants',
    () {
      final value = FoundationLearnerContext.fromJson(restrictedContext());
      expect(value.restricted, isTrue);
      expect(value.capabilities, ['daily_practice']);
      expect(
        value.expiresAt.toUtc().toIso8601String(),
        '2026-11-02T12:05:00.000Z',
      );
      expect(
        () => FoundationLearnerContext.fromJson(
          {...restrictedContext()}..remove('restricted'),
        ),
        throwsA(anything),
      );
    },
  );
  test(
    'ordinary account bearer is never borrowed for a restricted classroom session',
    () async {
      final requests = <RequestOptions>[];
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      final api = EschoolApi(dio: dio)
        ..setBearerAuth('bearerAuth', 'synthetic-ordinary-account');
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (request, handler) {
            requests.add(request);
            handler.resolve(
              Response(
                requestOptions: request,
                statusCode: 200,
                data: {
                  'error': false,
                  'message': 'OK',
                  'code': 200,
                  'data': restrictedContext(),
                },
              ),
            );
          },
        ),
      );
      await api.getFoundationClassroomApi().getFoundationLearnerContext();
      expect(requests[0].headers['Authorization'], isNull);
      api.setBearerAuth(
        'foundationLearnerAuth',
        'synthetic-restricted-session-and-device',
      );
      await api.getFoundationClassroomApi().getFoundationLearnerContext();
      expect(
        requests[1].headers['Authorization'],
        'Bearer synthetic-restricted-session-and-device',
      );
      expect(requests[1].queryParameters, isEmpty);
      dio.close();
    },
  );
}
