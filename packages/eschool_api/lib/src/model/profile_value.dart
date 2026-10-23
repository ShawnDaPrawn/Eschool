//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'profile_value.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileValue {
  /// Returns a new [ProfileValue] instance.
  ProfileValue({this.text, this.choices, this.flag, this.clear});

  @JsonKey(name: r'text', required: false, includeIfNull: false)
  final String? text;

  @JsonKey(name: r'choices', required: false, includeIfNull: false)
  final List<String>? choices;

  @JsonKey(name: r'flag', required: false, includeIfNull: false)
  final bool? flag;

  /// True clears an optional value; omit for other values.
  @JsonKey(name: r'clear', required: false, includeIfNull: false)
  final bool? clear;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileValue &&
          other.text == text &&
          other.choices == choices &&
          other.flag == flag &&
          other.clear == clear;

  @override
  int get hashCode =>
      text.hashCode + choices.hashCode + flag.hashCode + clear.hashCode;

  factory ProfileValue.fromJson(Map<String, dynamic> json) =>
      _$ProfileValueFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileValueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
