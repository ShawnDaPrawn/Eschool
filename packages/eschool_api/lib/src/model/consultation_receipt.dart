//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationReceipt {
  /// Returns a new [ConsultationReceipt] instance.
  ConsultationReceipt({
    required this.id,

    required this.revision,

    required this.status,
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
    unknownEnumValue: ConsultationReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final ConsultationReceiptStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationReceipt &&
          other.id == id &&
          other.revision == revision &&
          other.status == status;

  @override
  int get hashCode => id.hashCode + revision.hashCode + status.hashCode;

  factory ConsultationReceipt.fromJson(Map<String, dynamic> json) =>
      _$ConsultationReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ConsultationReceiptStatusEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ConsultationReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
