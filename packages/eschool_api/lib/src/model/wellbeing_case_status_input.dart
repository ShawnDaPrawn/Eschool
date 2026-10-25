//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_status_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseStatusInput {
  /// Returns a new [WellbeingCaseStatusInput] instance.
  WellbeingCaseStatusInput({
    required this.operationId,

    required this.revision,

    required this.status,

    required this.reason,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseStatusInputStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseStatusInputStatusEnum status;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseStatusInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.status == status &&
          other.reason == reason;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      status.hashCode +
      reason.hashCode;

  factory WellbeingCaseStatusInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseStatusInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseStatusInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseStatusInputStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseStatusInputStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
