//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_operation_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationOperationInput {
  /// Returns a new [FoundationOperationInput] instance.
  FoundationOperationInput({required this.operationId});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationOperationInput && other.operationId == operationId;

  @override
  int get hashCode => operationId.hashCode;

  factory FoundationOperationInput.fromJson(Map<String, dynamic> json) =>
      _$FoundationOperationInputFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationOperationInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
