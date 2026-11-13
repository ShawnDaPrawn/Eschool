//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_awards_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAwardsWrite {
  /// Returns a new [AcademicAwardsWrite] instance.
  AcademicAwardsWrite({
    required this.operationId,

    required this.awardDefinitionId,

    required this.basis,

    required this.learnerIds,

    required this.presentedOn,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'award_definition_id', required: true, includeIfNull: false)
  final int awardDefinitionId;

  @JsonKey(
    name: r'basis',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicAwardsWriteBasisEnum.unknownDefaultOpenApi,
  )
  final AcademicAwardsWriteBasisEnum basis;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final List<int> learnerIds;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final String presentedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAwardsWrite &&
          other.operationId == operationId &&
          other.awardDefinitionId == awardDefinitionId &&
          other.basis == basis &&
          other.learnerIds == learnerIds &&
          other.presentedOn == presentedOn;

  @override
  int get hashCode =>
      operationId.hashCode +
      awardDefinitionId.hashCode +
      basis.hashCode +
      learnerIds.hashCode +
      presentedOn.hashCode;

  factory AcademicAwardsWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicAwardsWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicAwardsWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicAwardsWriteBasisEnum {
  @JsonValue(r'participation')
  participation(r'participation'),
  @JsonValue(r'placing')
  placing(r'placing'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicAwardsWriteBasisEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
