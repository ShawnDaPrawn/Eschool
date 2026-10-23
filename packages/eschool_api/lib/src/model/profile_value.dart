//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/profile_attachment.dart';
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
  ProfileValue({this.text, this.choices, this.flag, this.clear, this.file});

  @JsonKey(name: r'text', required: false, includeIfNull: false)
  final String? text;

  @JsonKey(name: r'choices', required: false, includeIfNull: false)
  final List<String>? choices;

  @JsonKey(name: r'flag', required: false, includeIfNull: false)
  final bool? flag;

  /// True clears an optional value; omit for other values.
  @JsonKey(name: r'clear', required: false, includeIfNull: false)
  final bool? clear;

  /// Read-only metadata; uploads use the file action, and clear removes the current reference.
  @JsonKey(name: r'file', required: false, includeIfNull: false)
  final ProfileAttachment? file;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileValue &&
          other.text == text &&
          other.choices == choices &&
          other.flag == flag &&
          other.clear == clear &&
          other.file == file;

  @override
  int get hashCode =>
      text.hashCode +
      choices.hashCode +
      flag.hashCode +
      clear.hashCode +
      file.hashCode;

  factory ProfileValue.fromJson(Map<String, dynamic> json) =>
      _$ProfileValueFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileValueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
