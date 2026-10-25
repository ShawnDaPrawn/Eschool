//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_health_value.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyHealthValue {
  /// Returns a new [FamilyHealthValue] instance.
  FamilyHealthValue({this.text, this.choices, this.flag, this.clear});

  @JsonKey(name: r'text', required: false, includeIfNull: false)
  final String? text;

  @JsonKey(name: r'choices', required: false, includeIfNull: false)
  final List<String>? choices;

  @JsonKey(name: r'flag', required: false, includeIfNull: false)
  final bool? flag;

  /// True explicitly clears an optional health field.
  @JsonKey(name: r'clear', required: false, includeIfNull: false)
  final bool? clear;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyHealthValue &&
          other.text == text &&
          other.choices == choices &&
          other.flag == flag &&
          other.clear == clear;

  @override
  int get hashCode =>
      text.hashCode + choices.hashCode + flag.hashCode + clear.hashCode;

  factory FamilyHealthValue.fromJson(Map<String, dynamic> json) =>
      _$FamilyHealthValueFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyHealthValueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
