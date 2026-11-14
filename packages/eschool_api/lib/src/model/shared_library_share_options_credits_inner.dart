//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_share_options_credits_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryShareOptionsCreditsInner {
  /// Returns a new [SharedLibraryShareOptionsCreditsInner] instance.
  SharedLibraryShareOptionsCreditsInner({
    required this.value,

    required this.label,
  });

  @JsonKey(
    name: r'value',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SharedLibraryShareOptionsCreditsInnerValueEnum.unknownDefaultOpenApi,
  )
  final SharedLibraryShareOptionsCreditsInnerValueEnum value;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryShareOptionsCreditsInner &&
          other.value == value &&
          other.label == label;

  @override
  int get hashCode => value.hashCode + label.hashCode;

  factory SharedLibraryShareOptionsCreditsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$SharedLibraryShareOptionsCreditsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryShareOptionsCreditsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SharedLibraryShareOptionsCreditsInnerValueEnum {
  @JsonValue(r'named')
  named(r'named'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'anonymous')
  anonymous(r'anonymous'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SharedLibraryShareOptionsCreditsInnerValueEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
