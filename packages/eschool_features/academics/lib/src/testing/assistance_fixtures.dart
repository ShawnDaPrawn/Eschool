import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

/// Synthetic transport only; never evidence of a real provider's answer quality.
class AssistanceSmokeFixture {
  AssistanceSmokeFixture({required this.reader, this.office = false});
  final int reader;
  final bool office;
  bool allowed = true, claimed = false, handedOff = false;
  bool opened = false;
  int revision = 1;
  static const document = 'a8705678-17aa-48aa-88bb-123456789abc';
  static const thread = 'b8705678-17aa-48aa-88bb-123456789abc';
  static const message = 'c8705678-17aa-48aa-88bb-123456789abc';
  static const turn = 'd8705678-17aa-48aa-88bb-123456789abc';
  final writes = <Map<String, dynamic>>[];
  final messages = <Map<String, dynamic>>[
    {
      'id': message,
      'author_kind': 'parent',
      'text': 'PRIVATE original Parent question',
      'language': 'en',
      'sent_at': '2026-10-26T10:00:00Z',
    },
  ];
  String _review = '', _purpose = 'answer';
  Map<String, dynamic> _data(Map<String, dynamic> data) => {
    'error': false,
    'data': {...data, 'school_id': 1, 'reader_id': reader},
  };
  FakeResponse _deny(int status) => FakeResponse({
    'error': true,
    'message': 'School assistance access changed',
    'code': status,
  }, status: status);
  Map<String, dynamic> _doc() => {
    'id': document,
    'document_key': document,
    'revision': 1,
    'title': 'School office hours',
    'source_reference': 'Approved school handbook',
    'expires_on': null,
    'state': 'published',
  };
  Map<String, dynamic> _turn() => {
    'id': turn,
    'purpose': _purpose,
    'state': 'prepared',
    'learner_id': 11,
    'expires_at': DateTime.now()
        .toUtc()
        .add(const Duration(minutes: 14))
        .toIso8601String(),
    'language': 'en',
    'review_text': _review,
    'citations': [
      {
        'token': 's1',
        'kind': _purpose == 'answer' ? 'document' : 'support_message',
        'title': _purpose == 'answer'
            ? 'School office hours'
            : 'Original message',
        'locator': {'id': _purpose == 'answer' ? document : message},
      },
    ],
    'response': null,
  };
  Object _read(RequestOptions r, Map<String, dynamic> body) =>
      allowed ? _data(body) : _deny(403);
  Object _write(String action, RequestOptions r) {
    if (!allowed) return _deny(403);
    final body = Map<String, dynamic>.from(r.data as Map);
    writes.add({'action': action, 'body': jsonDecode(jsonEncode(body))});
    if (action == 'claim') claimed = true;
    if (action == 'reassign') handedOff = true;
    if (action == 'open') {
      opened = true;
      messages[0]['text'] = body['text'];
    }
    if (action == 'reply')
      messages.add({
        'id': 'e8705678-17aa-48aa-88bb-123456789abc',
        'author_kind': office ? 'office' : 'parent',
        'text': body['text'],
        'language': body['language'],
        'sent_at': DateTime.now().toUtc().toIso8601String(),
      });
    if (action == 'answer' || action == 'translate') {
      _purpose = action == 'answer' ? 'answer' : 'translation';
      _review = jsonEncode(
        action == 'answer'
            ? {
                'task': 'answer',
                'question': body['question'],
                'language': body['language'],
                'sources': [
                  {
                    'source': 's1',
                    'text': jsonEncode({
                      'title': 'School office hours',
                      'markdown': 'PRIVATE approved source opens at 08:00.',
                      'reference': 'School handbook',
                    }),
                  },
                ],
              }
            : {
                'task': 'translate',
                'text': messages.first['text'],
                'language': body['language'],
              },
      );
      return _data(_turn());
    }
    revision++;
    return _data({
      'id': thread,
      'revision': revision,
      'state': 'open',
      'message_id': message,
    });
  }

  Map<String, Object> get routes => {
    'GET v2/school-assistance/configuration': (RequestOptions r) => _read(r, {
      'can_parent': !office,
      'can_office': office,
      'writable': true,
      'languages': ['en', 'af'],
      'answer_available': false,
      'translation_available': false,
    }),
    for (final root in ['family/learners/11', 'office'])
      'GET v2/school-assistance/$root/documents': (RequestOptions r) =>
          _read(r, {
            'page': 1,
            'last_page': 1,
            'items': [_doc()],
          }),
    for (final root in ['family/learners/11', 'office'])
      'GET v2/school-assistance/$root/documents/$document':
          (RequestOptions r) => _read(r, {
            ..._doc(),
            'markdown': 'PRIVATE approved source opens at 08:00.',
          }),
    'GET v2/school-assistance/family/learners/11/threads': (RequestOptions r) =>
        _read(r, {
          'page': 1,
          'last_page': 1,
          'items': [
            if (opened)
              {
                'id': thread,
                'revision': revision,
                'state': 'open',
                'created_at': '2026-10-26T10:00:00Z',
              },
          ],
        }),
    'GET v2/school-assistance/office/queue': (RequestOptions r) => _read(r, {
      'page': 1,
      'last_page': 1,
      'items': [
        {
          'id': thread,
          'revision': revision,
          'state': 'open',
          'created_at': '2026-10-26T10:00:00Z',
          'assigned_to_reader': claimed && !handedOff,
        },
      ],
    }),
    'GET v2/school-assistance/threads/$thread': (RequestOptions r) =>
        office && (!claimed || handedOff)
        ? _deny(404)
        : _read(r, {
            'id': thread,
            'revision': revision,
            'state': 'open',
            'learner_id': 11,
            'expires_at': DateTime.now()
                .toUtc()
                .add(const Duration(days: 1))
                .toIso8601String(),
            'page': 1,
            'last_page': 1,
            'messages': messages,
          }),
    'GET v2/school-assistance/office/staff': (RequestOptions r) => _read(r, {
      'staff': [
        {'id': 13, 'name': 'Current second office reader'},
      ],
    }),
    'GET v2/school-assistance/turns/$turn': (RequestOptions r) =>
        _read(r, _turn()),
    'POST v2/school-assistance/family/learners/11/threads':
        (RequestOptions r) => _write('open', r),
    'POST v2/school-assistance/family/learners/11/answers':
        (RequestOptions r) => _write('answer', r),
    for (final root in ['family', 'office'])
      'POST v2/school-assistance/$root/learners/11/translations':
          (RequestOptions r) => _write('translate', r),
    'POST v2/school-assistance/threads/$thread/reply': (RequestOptions r) =>
        _write('reply', r),
    'POST v2/school-assistance/office/threads/$thread/claim':
        (RequestOptions r) => _write('claim', r),
    'POST v2/school-assistance/office/threads/$thread/reassign':
        (RequestOptions r) => _write('reassign', r),
  };
}
