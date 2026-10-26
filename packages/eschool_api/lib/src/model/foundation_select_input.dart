//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_select_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationSelectInput {
  /// Returns a new [FoundationSelectInput] instance.
  FoundationSelectInput({
    required this.operationId,

    required this.classroomAuthorization,

    required this.locator,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'classroom_authorization',
    required: true,
    includeIfNull: false,
  )
  final String classroomAuthorization;

  /// Printed unsigned locator. It selects an approved current-group learner and never records a gate scan.
  @JsonKey(name: r'locator', required: true, includeIfNull: false)
  final String locator;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationSelectInput &&
          other.operationId == operationId &&
          other.classroomAuthorization == classroomAuthorization &&
          other.locator == locator;

  @override
  int get hashCode =>
      operationId.hashCode + classroomAuthorization.hashCode + locator.hashCode;

  factory FoundationSelectInput.fromJson(Map<String, dynamic> json) =>
      _$FoundationSelectInputFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationSelectInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
