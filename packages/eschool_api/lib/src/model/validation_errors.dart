//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'validation_errors.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ValidationErrors {
  /// Returns a new [ValidationErrors] instance.
  ValidationErrors({required this.errors});

  /// Messages by field name (nested fields use dots, for example `items.0.amount`).
  @JsonKey(name: r'errors', required: true, includeIfNull: false)
  final Map<String, List<String>> errors;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ValidationErrors && other.errors == errors;

  @override
  int get hashCode => errors.hashCode;

  factory ValidationErrors.fromJson(Map<String, dynamic> json) =>
      _$ValidationErrorsFromJson(json);

  Map<String, dynamic> toJson() => _$ValidationErrorsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
