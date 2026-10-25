import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('private staff inbox retains scoped pagination, writable state and UTC read instant', () {
    final json = <String, dynamic>{
      'school_id': 2, 'reader_id': 7, 'writable': true,
      'items': [{'id': 9, 'feature': 'wellbeing', 'title': 'Private school update', 'message': 'Open your private area.', 'writable': false, 'read_at': null}],
      'page': 1, 'last_page': 2, 'unread_count': 26,
    };
    final page = StaffPrivateNoticePage.fromJson(json);
    expect(page.schoolId, 2);
    expect(page.readerId, 7);
    expect(page.items.single.writable, isFalse);
    expect(page.items.single.readAt, isNull);
    expect(StaffPrivateNoticePage.fromJson(page.toJson()).unreadCount, 26);
    final read = StaffPrivateNoticeRead.fromJson({'id': 9, 'school_id': 2, 'reader_id': 7, 'read_at': '2026-02-10T11:12:13Z'});
    expect(read.readAt.toUtc(), DateTime.utc(2026,2,10,11,12,13));
  });
  test('older facilitator profiles preserve absence of the optional inbox capability', () {
    final old = FacilitatorFeatures.fromJson({'groups': false, 'messages': true, 'counselling': false});
    expect(old.privateNotices, isNull);
    expect(old.toJson().containsKey('private_notices'), isFalse);
    final current = FacilitatorFeatures.fromJson({...old.toJson(), 'private_notices': true});
    expect(current.privateNotices, isTrue);
  });
}
