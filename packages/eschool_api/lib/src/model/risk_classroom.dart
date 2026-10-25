//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_classroom_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_classroom.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskClassroom {
  /// Returns a new [RiskClassroom] instance.
  RiskClassroom({required this.groupId, required this.learners});

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<RiskClassroomLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskClassroom &&
          other.groupId == groupId &&
          other.learners == learners;

  @override
  int get hashCode => groupId.hashCode + learners.hashCode;

  factory RiskClassroom.fromJson(Map<String, dynamic> json) =>
      _$RiskClassroomFromJson(json);

  Map<String, dynamic> toJson() => _$RiskClassroomToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
