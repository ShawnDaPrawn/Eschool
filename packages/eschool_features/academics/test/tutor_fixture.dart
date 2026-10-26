import 'dart:async';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';

const tutorSession = '10000000-0000-4000-8000-000000000001';
const tutorOperation = '10000000-0000-4000-8000-000000000002';
const tutorPhoto = '10000000-0000-4000-8000-000000000003';
Map<String, dynamic> tutorConfigurationJson({bool enabled = true}) => {
  'school_id': 1,
  'reader_id': 7,
  'learner_id': 9,
  'session_year_id': 2,
  'groups': [
    {
      'id': 3,
      'name': '7A maths',
      'subject_id': 4,
      'subject_name': 'Mathematics',
      'sources': [
        {'id': 5, 'title': 'Fractions'},
      ],
    },
  ],
  'inference_enabled': enabled,
  'photo_requires_approved_crop': true,
  'feedback_is_provisional': true,
};
Map<String, dynamic> tutorTurnJson({
  String operation = tutorOperation,
  String state = 'completed',
  String? failure,
}) => {
  'id': '10000000-0000-4000-8000-000000000004',
  'operation_id': operation,
  'state': state,
  'question': ['quarantined', 'purged'].contains(state)
      ? null
      : 'How do fractions work?',
  'working': ['quarantined', 'purged'].contains(state)
      ? null
      : 'I drew two equal parts.',
  'response': state == 'completed'
      ? {
          'subject_token': 'synthetic-subject-token',
          'kind': 'hint',
          'text': 'Compare the equal parts.',
          'question': 'Which part did you shade?',
        }
      : null,
  'failure_code': failure,
  'support_message': state == 'quarantined' ? 'Talk to a trusted adult.' : null,
  'provisional': true,
};
Map<String, dynamic> tutorReceiptJson({String operation = tutorOperation}) => {
  'school_id': 1,
  'reader_id': 7,
  'id': tutorPhoto,
  'operation_id': operation,
  'sha256': '0' * 64,
  'mime': 'image/png',
};

class FakeTutorRepository implements TutorRepository {
  bool active = true, enabled = true, loseSend = false, loseUpload = false;
  Completer<TutorSessionDetail>? delayedRead;
  List<Map<String, dynamic>> turns = [], photos = [];
  final List<TutorTurnIntent> writes = [];
  int uploads = 0, reads = 0, configurations = 0, revokes = 0;
  @override
  int get schoolId => 1;
  @override
  int get readerId => 7;
  @override
  bool get isActive => active;
  @override
  Future<TutorConfiguration> configuration() async {
    configurations++;
    return TutorConfiguration.fromJson(
      tutorConfigurationJson(enabled: enabled),
    );
  }

  @override
  Future<TutorSessionList> sessions({int page = 1}) async =>
      TutorSessionList.fromJson({
        'school_id': 1,
        'reader_id': 7,
        'learner_id': 9,
        'page': page,
        'last_page': 1,
        'sessions': [
          {'id': tutorSession, 'subject_id': 4, 'state': 'open'},
        ],
      });
  TutorSessionDetail detail() => TutorSessionDetail.fromJson({
    'school_id': 1,
    'reader_id': 7,
    'id': tutorSession,
    'learner_id': 9,
    'subject_id': 4,
    'group_id': 3,
    'session_year_id': 2,
    'state': 'open',
    'page': 1,
    'last_page': 1,
    'turns': turns,
    'pending_photos': photos,
  });
  @override
  Future<TutorSessionDetail> read(String session, {int page = 1}) async {
    reads++;
    return delayedRead?.future ?? detail();
  }

  @override
  Future<TutorSessionResult> create(TutorSessionWrite input) async =>
      TutorSessionResult.fromJson({
        'school_id': 1,
        'reader_id': 7,
        'id': tutorSession,
        'learner_id': 9,
        'group_id': 3,
        'subject_id': 4,
        'session_year_id': 2,
        'state': 'open',
      });
  @override
  Future<TutorTurnResult> send(TutorTurnIntent intent) async {
    writes.add(intent);
    turns = [
      {
        ...tutorTurnJson(
          operation: intent.operation,
          state: loseSend ? 'pending' : 'completed',
        ),
        'question': intent.body['question'],
        'working': intent.body['working'],
      },
    ];
    if (loseSend) throw StateError('Synthetic lost response');
    return TutorTurnResult.fromJson({
      'school_id': 1,
      'reader_id': 7,
      ...turns.single,
    });
  }

  @override
  Future<TutorTurnResult> status(String session, String operation) async =>
      TutorTurnResult.fromJson({
        'school_id': 1,
        'reader_id': 7,
        ...turns.firstWhere((turn) => turn['operation_id'] == operation),
      });
  @override
  Future<TutorPhotoReceipt> upload(TutorPhotoIntent intent) async {
    uploads++;
    photos = [tutorReceiptJson(operation: intent.operation)];
    if (loseUpload) throw StateError('Synthetic lost upload response');
    return TutorPhotoReceipt.fromJson(photos.single);
  }

  @override
  Future<void> revoke(String session, String photo, String operation) async {
    revokes++;
    photos = [];
  }

  @override
  Future<Uint8List> preview(
    String session,
    TutorPhotoReceipt reference,
  ) async => throw StateError('No synthetic pixels');
  @override
  Future<TutorSessionResult> close(String session, String operation) async =>
      throw UnimplementedError();
}
