import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import '../wellbeing/school_wall_time.dart';

const familyVisitFixtureMeeting = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
const familyVisitFixturePass = '7cf89daf-864a-47b5-a502-c322dd9602a2';

/// Synthetic transport only; it cannot authorise a real booking or collection.
class FamilyVisitSmokeFixture {
  FamilyVisitSmokeFixture({required this.accountId, this.staff = false});
  final int accountId;
  final bool staff;
  bool allowed = true, loseRequest = true;
  int effects = 0, meetingRevision = 1, passRevision = 0;
  String meetingStatus = 'open', passStatus = 'pending';
  Map<String, dynamic>? _requestBody;
  final writes = <Map<String, dynamic>>[];
  final _receipts = <String, Map<String, dynamic>>{},
      _hashes = <String, String>{};
  Object _result(Object data) => allowed
      ? {'error': false, 'message': 'OK', 'data': data, 'code': 200}
      : const FakeResponse({
          'error': true,
          'message': 'Your meeting access changed. Reopen this screen.',
          'data': null,
        }, status: 403);
  Map<String, dynamic> _slot() => {
    'id': familyVisitFixtureMeeting,
    'host_id': 12,
    'host_name': 'Synthetic meeting host',
    'title': 'Synthetic parent meeting',
    'venue': 'Synthetic private meeting room',
    'starts_at': '2099-11-01T15:00:00Z',
    'ends_at': '2099-11-01T15:15:00Z',
    'booking_closes_at': '2099-10-31T10:00:00Z',
    'status': staff ? 'booked' : meetingStatus,
    'revision': meetingRevision,
    'learner_id': staff || meetingStatus == 'booked' ? 11 : null,
    'learner_name': staff || meetingStatus == 'booked'
        ? 'Synthetic current learner'
        : null,
    'guardian_user_id': staff
        ? 21
        : meetingStatus == 'booked'
        ? accountId
        : null,
  };
  Map<String, dynamic> _pass() => {
    'id': familyVisitFixturePass,
    'learner_id': 11,
    'learner_name': 'Synthetic current learner',
    'requested_by': accountId,
    'approved_by': null,
    'collector': _requestBody!['collector'],
    'reason': _requestBody!['reason'],
    'starts_at': SchoolWallTime.utc(
      _requestBody!['starts_at'] as String,
      'Africa/Johannesburg',
    ).toIso8601String(),
    'ends_at': SchoolWallTime.utc(
      _requestBody!['ends_at'] as String,
      'Africa/Johannesburg',
    ).toIso8601String(),
    'status': passStatus,
    'revision': passRevision,
    'collected_at': null,
  };
  Object _write(String kind, RequestOptions request) {
    if (!allowed) return _result({});
    final body = Map<String, dynamic>.from(request.data as Map),
        operation = body['operation_id'] as String;
    writes.add({'kind': kind, 'body': body});
    final fingerprint = jsonEncode([kind, body]);
    if (_hashes.containsKey(operation)) {
      if (_hashes[operation] != fingerprint)
        return const FakeResponse({
          'error': true,
          'message': 'Changed exact request',
          'data': null,
        }, status: 409);
      return _result(_receipts[operation]!);
    }
    effects++;
    Map<String, dynamic> receipt;
    if (kind == 'book' || kind == 'unbook') {
      meetingStatus = kind == 'book' ? 'booked' : 'open';
      meetingRevision++;
      receipt = kind == 'book'
          ? _slot()
          : {
              'id': familyVisitFixtureMeeting,
              'status': 'open',
              'revision': meetingRevision,
            };
    } else if (kind == 'request') {
      _requestBody = body;
      passRevision = 1;
      passStatus = 'pending';
      receipt = _pass();
    } else {
      passRevision++;
      passStatus = 'cancelled';
      receipt = {
        'id': familyVisitFixturePass,
        'status': 'cancelled',
        'revision': passRevision,
      };
    }
    _hashes[operation] = fingerprint;
    _receipts[operation] = Map.from(receipt);
    if (kind == 'request' && loseRequest) {
      loseRequest = false;
      return const FakeResponse({
        'error': true,
        'message': 'Synthetic uncertain request',
        'data': null,
      }, status: 503);
    }
    return _result(receipt);
  }

  Map<String, Object> get routes => {
    'GET v2/family-visits/configuration': (_) => _result({
      'school_id': 1,
      'reader_id': accountId,
      'timezone': 'Africa/Johannesburg',
      'writable': true,
      'can_manage': false,
      'can_host': staff,
      'children': [
        if (!staff) {'id': 11, 'name': 'Synthetic current learner'},
      ],
      'hosts': <dynamic>[],
      'gate_rules': {
        'max_window_minutes': 240,
        'max_future_days': 30,
        'scan_max_age_seconds': 120,
        'revision': 0,
        'staff_approval_required': true,
        'collector_check_required': true,
      },
    }),
    'GET v2/family-visits/meetings': (_) => _result({
      'items': [_slot()],
      'next_page': null,
    }),
    'GET v2/family-visits/passes': (_) => _result({
      'items': [if (_requestBody != null) _pass()],
      'next_page': null,
    }),
    'POST v2/family-visits/meetings/$familyVisitFixtureMeeting/book':
        (RequestOptions r) => _write('book', r),
    'POST v2/family-visits/meetings/$familyVisitFixtureMeeting/unbook':
        (RequestOptions r) => _write('unbook', r),
    'POST v2/family-visits/passes': (RequestOptions r) => _write('request', r),
    'POST v2/family-visits/passes/$familyVisitFixturePass/cancel':
        (RequestOptions r) => _write('cancel', r),
  };
}
