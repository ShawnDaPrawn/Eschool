import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';

class FakeReporting implements ReportingRepository {
  @override
  bool isActive = true;
  bool writable = true, deny = false, anonymous = true, offlineOnce = false;
  String code = 'a' * 64, body = 'Private reporter concern';
  int revision = 1, submissionCount = 0;
  final photos = <Map<String, dynamic>>[], photoRequests = <String>[];
  bool photoOfflineOnce = false;
  final submissions = <String>[],
      replies = <String>[],
      identifications = <Map<String, dynamic>>[];
  void guard() {
    if (deny) throw ApiException('Not your report', statusCode: 403);
  }

  @override
  Future<WellbeingAccess> access() async {
    guard();
    return WellbeingAccess(
      canConfigure: false,
      professional: false,
      canTriage: false,
      canCasework: false,
      canWrite: writable,
      today: '2026-02-10',
      schoolSlug: 'synthetic',
      emergencyGuidance: 'Speak to a trusted adult now if unsafe.',
    );
  }

  @override
  Future<IncidentPublicConfiguration> configuration(String slug) async {
    guard();
    return IncidentPublicConfiguration.fromJson({
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
    });
  }

  IncidentPublicView view() => IncidentPublicView.fromJson({
    'anonymous': anonymous,
    'body': body,
    'urgency': 'routine',
    'status': 'open',
    'revision': revision,
    'submitted_at': '2026-02-10T08:00:00Z',
    'emergency_guidance': 'Speak to a trusted adult now if unsafe.',
    'threads': [],
    'has_older': false,
    'next_before': null,
    'photos': photos,
  });
  @override
  Future<IncidentSubmissionReceipt> submit(Map<String, dynamic> input) async {
    guard();
    final json = jsonEncode(input);
    submissions.add(json);
    if (submissions.length == 1) {
      submissionCount++;
      code = input['secret'];
      body = input['body'];
      anonymous = false;
    } else if (submissions.first != json) {
      throw ApiException('Changed intent', statusCode: 409);
    }
    if (offlineOnce) {
      offlineOnce = false;
      throw ApiException('Connection lost');
    }
    return IncidentSubmissionReceipt(
      received: true,
      submittedAt: DateTime.utc(2026, 2, 10, 8),
      emergencyGuidance: 'Seek trusted help',
    );
  }

  @override
  Future<IncidentPublicView> read(String secret, {int? before}) async {
    guard();
    if (secret != code) throw ApiException('Not your report', statusCode: 403);
    return view();
  }

  @override
  Future<IncidentPublicView> identify(
    String secret,
    int revision, {
    int? learner,
  }) async {
    guard();
    identifications.add({
      'secret': secret,
      'revision': revision,
      'learner': learner,
    });
    anonymous = false;
    this.revision++;
    return view();
  }

  @override
  Future<void> reply(String secret, Map<String, dynamic> input) async {
    guard();
    replies.add(jsonEncode(input));
    revision++;
  }

  @override
  Future<IncidentPhoto> uploadPhoto(
    String secret,
    Uint8List bytes,
    String operation,
  ) async {
    guard();
    if (secret != code) throw ApiException('Not your report', statusCode: 403);
    photoRequests.add(
      jsonEncode({
        'secret': secret,
        'bytes': bytes.toList(),
        'operation_id': operation,
      }),
    );
    if (photos.isEmpty) {
      revision++;
      photos.add({
        'id': '11111111-1111-4111-8111-111111111111',
        'mime': 'image/jpeg',
        'bytes': bytes.length,
        'width': 1,
        'height': 1,
        'thumbnail_base64': base64Encode(bytes),
        'created_at': '2026-02-10T08:00:00.000Z',
        'report_revision': revision,
      });
    }
    if (photoOfflineOnce) {
      photoOfflineOnce = false;
      throw ApiException('Connection lost');
    }
    return IncidentPhoto.fromJson(photos.single);
  }

  @override
  Uri anonymousForm(String slug) =>
      Uri.parse('https://school.example/school/$slug/report');
}
