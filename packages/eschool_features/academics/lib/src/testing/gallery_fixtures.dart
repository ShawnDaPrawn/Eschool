import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic client integration data only. No real learner or school content.
class GalleryClientFixture {
  GalleryClientFixture({
    required this.account,
    this.family = false,
    this.learner = 11,
  });
  final int account, learner;
  final bool family;
  bool granted = true, removed = false;
  final mutations = <RequestOptions>[];
  Map<String, dynamic> ok(Object data) => {'error': false, 'data': data};
  Map<String, dynamic> photo() => {
    'id': 8,
    'public_id': 'synthetic-photo',
    'caption': 'Private synthetic gallery caption',
    'captured_on': '2026-10-25',
    'school_year': '2026',
    'session_year_id': 7,
    'activity_group_id': 2,
    'scope': 'group',
    'width': 2,
    'height': 2,
    'active': true,
    'revision': 1,
  };
  Map<String, Object> routes() => {
    'GET v2/gallery/configuration': (RequestOptions _) => !granted
        ? const FakeResponse({'message': 'Photo access withdrawn'}, status: 403)
        : ok({
            'school_id': 1,
            'account_id': account,
            'session_year_id': 7,
            'today': '2026-10-25',
            'years': [
              {
                'id': 7,
                'name': '2026',
                'starts_on': '2026-01-01',
                'ends_on': '2026-12-31',
              },
            ],
            'children': family
                ? [
                    {
                      'id': learner,
                      'name': 'Synthetic learner',
                      'handle': 'learner.demo',
                      'can_approve': true,
                      'archived': false,
                    },
                  ]
                : [],
            'groups': [
              {
                'id': 2,
                'name': 'Synthetic gallery group',
                'session_year_id': 7,
                'can_upload': !family,
                'learner_ids': family ? [learner] : [],
              },
            ],
            'guardian_required': true,
            'settings_revision': 0,
            'can_manage': false,
            'can_write': true,
          }),
    'GET v2/gallery/learners/$learner/photos': (RequestOptions _) => ok({
      'items': removed
          ? []
          : [
              {
                'tag_id': 9,
                'tag_revision': 1,
                'status': 'approved',
                'retained_on_transfer': false,
                'photo': photo(),
              },
            ],
      'total': removed ? 0 : 1,
      'next_before': null,
    }),
    'GET v2/gallery/groups/2/photos': (RequestOptions _) => ok({
      'items': removed ? [] : [photo()],
      'next_before': null,
    }),
    'GET v2/gallery/photos/8/content': (RequestOptions _) => FakeBinaryResponse(
      base64Decode(
        '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAACAAIDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDjaKKK5jxz/9k=',
      ),
      contentType: 'image/jpeg',
    ),
    'POST v2/gallery/photos': (RequestOptions r) {
      mutations.add(r);
      return ok({'id': 8, 'revision': 1});
    },
    'POST v2/gallery/tags/9/remove': (RequestOptions r) {
      mutations.add(r);
      removed = true;
      return ok({'id': 9, 'revision': 2});
    },
    'POST v2/gallery/photos/8/withdraw': (RequestOptions r) {
      mutations.add(r);
      removed = true;
      return ok({'id': 8, 'revision': 2});
    },
  };
}
