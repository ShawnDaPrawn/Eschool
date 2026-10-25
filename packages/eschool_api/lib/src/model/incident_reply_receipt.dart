//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_reply_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentReplyReceipt {
  /// Returns a new [IncidentReplyReceipt] instance.
  IncidentReplyReceipt({
    required this.posted,

    required this.operationId,

    required this.createdAt,
  });

  @JsonKey(name: r'posted', required: true, includeIfNull: false)
  final bool posted;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'created_at', required: true, includeIfNull: true)
  final DateTime? createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentReplyReceipt &&
          other.posted == posted &&
          other.operationId == operationId &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      posted.hashCode +
      operationId.hashCode +
      (createdAt == null ? 0 : createdAt.hashCode);

  factory IncidentReplyReceipt.fromJson(Map<String, dynamic> json) =>
      _$IncidentReplyReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentReplyReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
