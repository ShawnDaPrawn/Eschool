import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'guardian decision keeps exact tag, account, school and revision',
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
                  'data': {'id': 7, 'revision': 2},
                },
              ),
            );
          },
        ),
      );
      final input = GalleryDecision.fromJson({
        'operation_id': '00000000-0000-4000-8000-000000000001',
        'revision': 1,
        'decision': 'approve',
      });
      final response = await GalleryApi(dio).decideGalleryTag(
        tag: 7,
        galleryDecision: input,
        xAccountId: 12,
        xSchoolId: 1,
      );
      expect(sent!.path, '/gallery/tags/7/decision');
      expect(sent!.queryParameters, isEmpty);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.headers['X-School-Id'], 1);
      expect(
        sent!.data is String ? jsonDecode(sent!.data as String) : sent!.data,
        input.toJson(),
      );
      expect(response.data!.data.revision, 2);
    },
  );

  test(
    'private thumbnail remains binary with explicit scope headers',
    () async {
      final dio = Dio(BaseOptions(baseUrl: 'https://school.example/api/v2'));
      RequestOptions? sent;
      final pixels = Uint8List.fromList([0xff, 0xd8, 0xff, 0xd9]);
      dio.interceptors.add(
        InterceptorsWrapper(
          onRequest: (options, handler) {
            sent = options;
            handler.resolve(
              Response(requestOptions: options, statusCode: 200, data: pixels),
            );
          },
        ),
      );
      final response = await GalleryApi(dio).getGalleryPhoto(
        photo: 9,
        thumbnail: true,
        xAccountId: 12,
        xSchoolId: 1,
      );
      expect(sent!.path, '/gallery/photos/9/content');
      expect(sent!.queryParameters['thumbnail'], isTrue);
      expect(sent!.headers['X-School-Id'], 1);
      expect(sent!.headers['X-Account-Id'], 12);
      expect(sent!.responseType, ResponseType.bytes);
      expect(response.data, pixels);
    },
  );

  test(
    'old consent omits photo choice while explicit keep carries its fingerprint',
    () {
      final base = <String, dynamic>{
        'operation_id': '00000000-0000-4000-8000-000000000001',
        'revision': 3,
        'fingerprint': 'a' * 64,
        'version': 'school-transfer-v1',
        'confirmation': true,
      };
      final old = TransferConsentInput.fromJson(base);
      expect(old.keepPhotoTags, isNull);
      expect(old.toJson().containsKey('keep_photo_tags'), isFalse);
      expect(old.toJson().containsKey('gallery_fingerprint'), isFalse);
      final retained = TransferConsentInput.fromJson({
        ...base,
        'keep_photo_tags': true,
        'gallery_fingerprint': 'b' * 64,
      });
      expect(retained.toJson()['gallery_fingerprint'], 'b' * 64);
      expect(retained.keepPhotoTags, isTrue);
      expect(
        TransferGalleryChoice.fromJson({
          'fingerprint': 'b' * 64,
          'photos': 2,
          'can_keep': true,
        }).photos,
        2,
      );
    },
  );
}
