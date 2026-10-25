//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_actions.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeActions {
  /// Returns a new [MyGradeActions] instance.
  MyGradeActions({
    required this.registers,

    required this.messages,

    required this.interventions,
  });

  @JsonKey(name: r'registers', required: true, includeIfNull: false)
  final bool registers;

  @JsonKey(name: r'messages', required: true, includeIfNull: false)
  final bool messages;

  @JsonKey(name: r'interventions', required: true, includeIfNull: false)
  final bool interventions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeActions &&
          other.registers == registers &&
          other.messages == messages &&
          other.interventions == interventions;

  @override
  int get hashCode =>
      registers.hashCode + messages.hashCode + interventions.hashCode;

  factory MyGradeActions.fromJson(Map<String, dynamic> json) =>
      _$MyGradeActionsFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeActionsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
