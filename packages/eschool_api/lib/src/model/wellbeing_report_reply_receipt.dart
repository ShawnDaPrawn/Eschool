//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_report_reply_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingReportReplyReceipt {
  /// Returns a new [WellbeingReportReplyReceipt] instance.
  WellbeingReportReplyReceipt({
    required this.id,

    required this.revision,

    required this.status,

    required this.threadId,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        WellbeingReportReplyReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingReportReplyReceiptStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'thread_id', required: true, includeIfNull: false)
  final int threadId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingReportReplyReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.status == status &&
          other.threadId == threadId;

  @override
  int get hashCode =>
      id.hashCode + revision.hashCode + status.hashCode + threadId.hashCode;

  factory WellbeingReportReplyReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingReportReplyReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingReportReplyReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingReportReplyReceiptStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'triaged')
  triaged(r'triaged'),
  @JsonValue(r'resolved')
  resolved(r'resolved'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingReportReplyReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
