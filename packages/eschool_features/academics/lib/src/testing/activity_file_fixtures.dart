import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic pixels, names and records for full app profile journeys only.
Map<String, dynamic> materialGroupFixture({int school = 1, int id = 7}) => {
  'id': id,
  'school_id': school,
  'session_year_id': 2,
  'parent_id': null,
  'name': 'Hockey',
  'discipline': 'Sport',
  'season': '2026',
  'starts_on': '2026-01-01',
  'ends_on': '2026-12-31',
  'gender': 'any',
  'minimum_grade': null,
  'maximum_grade': null,
  'minimum_age': null,
  'maximum_age': null,
  'active': true,
  'revision': 1,
};
Map<String, dynamic> materialSessionFixture() => {
  'id': 9,
  'activity_group_id': 7,
  'on_date': '2026-02-10',
  'name': 'Practice',
  'venue': 'Field',
  'status': 'scheduled',
  'revision': 1,
};
Map<String, dynamic> materialRecordFixture() => {
  'id': 11,
  'activity_group_id': 7,
  'author_id': 12,
  'title': 'Fixture note',
  'markdown': '# Bring your kit',
  'published': true,
  'revision': 1,
};
Map<String, dynamic> materialDetailFixture({
  bool staff = true,
  int school = 1,
}) => {
  'group': materialGroupFixture(school: school),
  'sessions': [materialSessionFixture()],
  'materials': [
    {...materialRecordFixture(), 'html': '<h1>Bring your kit</h1>'},
  ],
  'can_manage': false,
  'can_capture': staff,
};

class MaterialFileClientFixture {
  MaterialFileClientFixture({this.staff = false});
  final bool staff;
  bool allowed = true, removed = false, pdf = false;
  int removalEffects = 0;
  final operations = <String>{};
  final pixels = base64Decode(
    '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAACAAIDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDjaKKK5jxz/9k=',
  );
  final pdfBytes = base64Decode(
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCAyMDAgMjAwXSAvUmVzb3VyY2VzIDw8IC9Gb250IDw8IC9GMSA1IDAgUiA+PiA+PiAvQ29udGVudHMgNCAwIFIgPj4KZW5kb2JqCjQgMCBvYmoKPDwgL0xlbmd0aCA0NiA+PgpzdHJlYW0KQlQgL0YxIDEyIFRmIDMwIDEwMCBUZCAoUHJpdmF0ZSBmaXh0dXJlKSBUaiBFVAplbmRzdHJlYW0KZW5kb2JqCjUgMCBvYmoKPDwgL1R5cGUgL0ZvbnQgL1N1YnR5cGUgL1R5cGUxIC9CYXNlRm9udCAvSGVsdmV0aWNhID4+CmVuZG9iagp4cmVmCjAgNgowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDAwMDkgMDAwMDAgbiAKMDAwMDAwMDA1OCAwMDAwMCBuIAowMDAwMDAwMTE1IDAwMDAwIG4gCjAwMDAwMDAyNDEgMDAwMDAgbiAKMDAwMDAwMDMzNyAwMDAwMCBuIAp0cmFpbGVyCjw8IC9TaXplIDYgL1Jvb3QgMSAwIFIgPj4Kc3RhcnR4cmVmCjQwNwolJUVPRgo=',
  );
  List<int> get bytes => pdf ? pdfBytes : pixels;
  String get name => 'Private synthetic worksheet.${pdf ? 'pdf' : 'jpg'}';
  String get mime => pdf ? 'application/pdf' : 'image/jpeg';
  Object ok(Object data) => {'error': false, 'data': data};
  Object refusal() =>
      const FakeResponse({'message': 'Group access withdrawn'}, status: 403);
  Map<String, Object> routes() => {
    'GET v2/activities/groups': (_) => allowed
        ? ok({
            'items': [materialGroupFixture()],
            'page': 1,
            'last_page': 1,
            'total': 1,
          })
        : refusal(),
    'GET v2/activities/groups/7': (_) =>
        allowed ? ok(materialDetailFixture(staff: staff)) : refusal(),
    'GET v2/activities/groups/7/materials/11/files': (_) => allowed
        ? ok({
            'items': removed
                ? []
                : [
                    {
                      'id': '550e8400-e29b-41d4-a716-446655440000',
                      'activity_group_id': 7,
                      'material_id': 11,
                      'name': name,
                      'mime': mime,
                      'bytes': bytes.length,
                      'revision': 1,
                      'removed': false,
                    },
                  ],
            'material_revision': removed ? 2 : 1,
            'can_upload': staff,
            'can_remove': staff,
          })
        : refusal(),
    'GET v2/activities/groups/7/materials/11/files/550e8400-e29b-41d4-a716-446655440000/content':
        (_) => allowed && !removed
        ? FakeBinaryResponse(bytes, contentType: mime)
        : refusal(),
    'POST v2/activities/groups/7/materials/11/files/550e8400-e29b-41d4-a716-446655440000/remove':
        (RequestOptions request) {
          if (!allowed || !staff) return refusal();
          final input = Map<String, dynamic>.from(request.data as Map);
          if (operations.add(input['operation_id'] as String)) removalEffects++;
          removed = true;
          return ok({
            'id': '550e8400-e29b-41d4-a716-446655440000',
            'activity_group_id': 7,
            'material_id': 11,
            'name': name,
            'mime': mime,
            'bytes': bytes.length,
            'revision': 2,
            'removed': true,
            'material_revision': 2,
          });
        },
  };
}
