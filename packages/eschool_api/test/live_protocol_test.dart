import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test(
    'live configuration preserves the complete credential-specific protocol shape',
    () {
      final json = <String, dynamic>{
        'lease_id': '75133b39-0476-4ba0-a627-a152d042687a',
        'channel':
            'private-ne.school.1.user.7.session.75133b39-0476-4ba0-a627-a152d042687a',
        'key': 'local-live-key',
        'host': '127.0.0.1',
        'port': 8086,
        'scheme': 'ws',
        'heartbeat_seconds': 20,
        'expires_in': 45,
      };
      expect(LiveSessionConfiguration.fromJson(json).toJson(), json);
      expect(
        LiveAuthorizeRequest.fromJson({
          'socket_id': '123.456',
          'channel_name': json['channel'],
        }).toJson(),
        {'socket_id': '123.456', 'channel_name': json['channel']},
      );
    },
  );
  test(
    'presence has counts and references without member identities or message content',
    () {
      final json = <String, dynamic>{
        'type': 'group.presence',
        'school_id': 1,
        'group_id': 3,
        'revision': 123456,
        'online_count': 0,
      };
      expect(LivePresence.fromJson(json).toJson(), json);
      expect(
        LivePresenceRequest.fromJson({
          'lease_id': '75133b39-0476-4ba0-a627-a152d042687a',
        }).toJson(),
        {'lease_id': '75133b39-0476-4ba0-a627-a152d042687a'},
      );
    },
  );
}
