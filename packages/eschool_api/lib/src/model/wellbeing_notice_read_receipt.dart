//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_notice_read_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingNoticeReadReceipt {
  /// Returns a new [WellbeingNoticeReadReceipt] instance.
  WellbeingNoticeReadReceipt({required this.id, required this.readAt});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'read_at', required: true, includeIfNull: false)
  final DateTime readAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingNoticeReadReceipt &&
          other.id == id &&
          other.readAt == readAt;

  @override
  int get hashCode => id.hashCode + readAt.hashCode;

  factory WellbeingNoticeReadReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingNoticeReadReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingNoticeReadReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
