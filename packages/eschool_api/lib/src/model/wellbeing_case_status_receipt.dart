//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_status_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseStatusReceipt {
  /// Returns a new [WellbeingCaseStatusReceipt] instance.
  WellbeingCaseStatusReceipt({
    required this.id,

    required this.learnerId,

    required this.status,

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
    unknownEnumValue:
        WellbeingCaseStatusReceiptStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseStatusReceiptStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseStatusReceipt &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode + learnerId.hashCode + status.hashCode + revision.hashCode;

  factory WellbeingCaseStatusReceipt.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseStatusReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseStatusReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseStatusReceiptStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseStatusReceiptStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
