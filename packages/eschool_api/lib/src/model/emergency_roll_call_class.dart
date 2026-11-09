//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/emergency_roll_call_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'emergency_roll_call_class.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyRollCallClass {
  /// Returns a new [EmergencyRollCallClass] instance.
  EmergencyRollCallClass({
    required this.classSectionId,

    required this.name,

    required this.accounted,

    required this.unaccounted,

    required this.released,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: true, includeIfNull: false)
  final int classSectionId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  @JsonKey(name: r'accounted', required: true, includeIfNull: false)
  final int accounted;

  // minimum: 0
  @JsonKey(name: r'unaccounted', required: true, includeIfNull: false)
  final int unaccounted;

  // minimum: 0
  @JsonKey(name: r'released', required: true, includeIfNull: false)
  final int released;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<EmergencyRollCallLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyRollCallClass &&
          other.classSectionId == classSectionId &&
          other.name == name &&
          other.accounted == accounted &&
          other.unaccounted == unaccounted &&
          other.released == released &&
          other.learners == learners;

  @override
  int get hashCode =>
      classSectionId.hashCode +
      name.hashCode +
      accounted.hashCode +
      unaccounted.hashCode +
      released.hashCode +
      learners.hashCode;

  factory EmergencyRollCallClass.fromJson(Map<String, dynamic> json) =>
      _$EmergencyRollCallClassFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyRollCallClassToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
