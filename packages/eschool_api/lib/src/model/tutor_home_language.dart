//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_home_language.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorHomeLanguage {
  /// Returns a new [TutorHomeLanguage] instance.
  TutorHomeLanguage({required this.code, required this.name});

  @JsonKey(
    name: r'code',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorHomeLanguageCodeEnum.unknownDefaultOpenApi,
  )
  final TutorHomeLanguageCodeEnum code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorHomeLanguage && other.code == code && other.name == name;

  @override
  int get hashCode => code.hashCode + name.hashCode;

  factory TutorHomeLanguage.fromJson(Map<String, dynamic> json) =>
      _$TutorHomeLanguageFromJson(json);

  Map<String, dynamic> toJson() => _$TutorHomeLanguageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorHomeLanguageCodeEnum {
  @JsonValue(r'af')
  af(r'af'),
  @JsonValue(r'zu')
  zu(r'zu'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorHomeLanguageCodeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
