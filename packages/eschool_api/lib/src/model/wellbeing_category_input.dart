//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_category_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCategoryInput {
  /// Returns a new [WellbeingCategoryInput] instance.
  WellbeingCategoryInput({
    required this.operationId,

    required this.revision,

    required this.name,

    required this.emergencyGuidance,

    required this.active,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCategoryInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.name == name &&
          other.emergencyGuidance == emergencyGuidance &&
          other.active == active;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      name.hashCode +
      emergencyGuidance.hashCode +
      active.hashCode;

  factory WellbeingCategoryInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCategoryInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCategoryInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
