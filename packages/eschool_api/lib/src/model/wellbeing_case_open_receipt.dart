//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_open_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseOpenReceipt {
  /// Returns a new [WellbeingCaseOpenReceipt] instance.
  WellbeingCaseOpenReceipt({
    required this.id,

    required this.learnerId,

    required this.status,

    required this.openedAt,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseOpenReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseOpenReceiptStatusEnum status;

  @JsonKey(name: r'opened_at', required: true, includeIfNull: false)
  final DateTime openedAt;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseOpenReceipt &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.status == status &&
          other.openedAt == openedAt &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      status.hashCode +
      openedAt.hashCode +
      revision.hashCode;

  factory WellbeingCaseOpenReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseOpenReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseOpenReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseOpenReceiptStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseOpenReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
