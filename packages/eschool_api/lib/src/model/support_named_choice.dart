//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'support_named_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SupportNamedChoice {
  /// Returns a new [SupportNamedChoice] instance.
  SupportNamedChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SupportNamedChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory SupportNamedChoice.fromJson(Map<String, dynamic> json) =>
      _$SupportNamedChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$SupportNamedChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
