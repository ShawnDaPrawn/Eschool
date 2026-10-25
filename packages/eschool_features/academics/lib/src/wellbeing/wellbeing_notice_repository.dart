import 'package:eschool_api/eschool_api.dart';

/// Generic notice metadata is scoped to the same private reader generation.
abstract interface class WellbeingNoticeRepository {
  bool get isActive;
  Future<WellbeingNoticePage> notices(int page);
  Future<WellbeingNoticeReadReceipt> readNotice(int id);
}
