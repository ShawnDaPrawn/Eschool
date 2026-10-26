import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

const reference = 'f216bd97-812e-4e3f-8afc-c986765a18db';
Map<String, dynamic> packet() => {
  'suite': 'NEX1-X25519-HKDF-SHA256-A256GCM',
  'context': {
    'school_id': 1,
    'device_id': reference,
    'plan_id': reference,
    'card_id': reference,
    'revision': 3,
    'issued_at': 1000,
    'expires_at': 1600,
  },
  'ephemeral_public_key': base64.encode(List.filled(32, 1)),
  'salt': base64.encode(List.filled(32, 2)),
  'nonce': base64.encode(List.filled(12, 3)),
  'tag': base64.encode(List.filled(16, 4)),
  'ciphertext': base64.encode(utf8.encode('synthetic')),
};
Map<String, dynamic> receipt() => {
  'encoded': true,
  'plan_id': reference,
  'card_id': reference,
  'revision': 3,
  'verified_counter': 0,
  'encoded_at': '2026-11-02T12:00:00Z',
  'collection_authorized': false,
  'sign_in_authorized': false,
};
void main() {
  test(
    'device-bound encrypted envelope preserves integer authorization epochs and purpose',
    () {
      final value = SecureEncodingEnvelope.fromJson(packet());
      expect(value.toJson(), packet());
      expect(value.context.expiresAt - value.context.issuedAt, 600);
      expect(value.context.revision, 3);
      expect(value.toJson().containsKey('keys'), isFalse);
    },
  );
  test(
    'SUN receipt preserves valid zero counter and never supplies collection or sign-in',
    () {
      final value = SecureEncodingReceipt.fromJson(receipt());
      expect(value.verifiedCounter, 0);
      expect(value.collectionAuthorized, isFalse);
      expect(value.signInAuthorized, isFalse);
      expect(
        value.encodedAt.toUtc().toIso8601String(),
        '2026-11-02T12:00:00.000Z',
      );
      expect(
        () => SecureEncodingReceipt.fromJson(
          {...receipt()}..remove('collection_authorized'),
        ),
        throwsA(anything),
      );
    },
  );
  test(
    'ordinary user and gate bearer realms never supply encoder key delivery',
    () async {
      final requests = <RequestOptions>[];
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      final api = EschoolApi(dio: dio)
        ..setBearerAuth('bearerAuth', 'synthetic-user')
        ..setBearerAuth('CardDeviceCredential', 'synthetic-gate');
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
                  'data': packet(),
                },
              ),
            );
          },
        ),
      );
      await api.getSecureCardsApi().getSecureEncodingPlan(
        plan: reference,
        xSchoolId: 1,
      );
      expect(requests.single.headers['Authorization'], isNull);
      expect(requests.single.queryParameters, isEmpty);
      api.setBearerAuth('CardEncoderCredential', 'synthetic-encoder');
      await api.getSecureCardsApi().getSecureEncodingPlan(
        plan: reference,
        xSchoolId: 1,
      );
      expect(
        requests.last.headers['Authorization'],
        'Bearer synthetic-encoder',
      );
      expect(requests.last.headers['X-School-Id'], 1);
      dio.close();
    },
  );
  test(
    'confirmation keeps exact operation and SUN proof in the body, never the URL',
    () async {
      RequestOptions? sent;
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      final api = EschoolApi(dio: dio)
        ..setBearerAuth('CardEncoderCredential', 'synthetic-encoder');
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
                  'data': receipt(),
                },
              ),
            );
          },
        ),
      );
      final input = {
        'operation_id': reference,
        'picc': List.filled(32, 'A').join(),
        'mac': List.filled(16, 'B').join(),
      };
      final result = await api.getSecureCardsApi().confirmSecureEncodingPlan(
        plan: reference,
        xSchoolId: 1,
        secureEncodingConfirmation: SecureEncodingConfirmation.fromJson(input),
      );
      expect(sent!.queryParameters, isEmpty);
      expect(jsonDecode(sent!.data as String), input);
      expect(sent!.path, '/card-encoder/plans/$reference/confirm');
      expect(sent!.headers['Authorization'], 'Bearer synthetic-encoder');
      expect(result.data!.data.verifiedCounter, 0);
      dio.close();
    },
  );
}
