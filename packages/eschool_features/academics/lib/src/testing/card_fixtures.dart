import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic JPEG/account data only; no production proof, scanner or network.
class CardSmokeFixture {
  CardSmokeFixture({required this.personId, required this.type});
  final int personId;
  final String type;
  bool allowed = true, frozen = false;
  int freezes = 0, proofs = 0;
  static const id = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
  Map<String, dynamic> card() => {
    'id': id,
    'school_id': 1,
    'person_id': personId,
    'type': type,
    'status': frozen ? 'frozen' : 'active',
    'revision': frozen ? 2 : 1,
    'issued_at': '2026-10-25T10:00:00Z',
    'frozen_at': frozen ? '2026-10-25T10:00:01Z' : null,
    'replaced_by': null,
    'has_chip': false,
  };
  Object result(Object data) => allowed
      ? {'error': false, 'data': data}
      : const FakeResponse({
          'error': true,
          'message': 'Card access withdrawn',
          'code': 403,
          'data': {},
        }, status: 403);
  Map<String, Object> routes() => {
    'GET v2/cards/configuration': (_) => result({
      'can_manage': false,
      'can_manage_readers': false,
      'writable': true,
      'types': [type],
    }),
    'GET v2/cards/mine': (_) => result({
      'items': [card()],
      'next_page': null,
    }),
    'POST v2/cards/$id/credential': (_) {
      proofs++;
      return result({
        'card': card(),
        'credential': 'NEQ1.test.stub',
        'expires_at': '2026-10-25T10:00:25Z',
        'key_id': id,
        'public_key': 'test-only',
      });
    },
    'POST v2/cards/$id/freeze': (RequestOptions request) {
      if (allowed) {
        frozen = true;
        freezes++;
      }
      return result(card());
    },
    'GET v2/cards/$id/portrait': (_) => allowed
        ? FakeBinaryResponse(
            base64Decode(
              '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAFAAQDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwCvRRRQB//Z',
            ),
            contentType: 'image/jpeg',
          )
        : const FakeResponse({}, status: 403),
  };
}
