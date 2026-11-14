//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_share_options_licence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryShareOptionsLicence {
  /// Returns a new [SharedLibraryShareOptionsLicence] instance.
  SharedLibraryShareOptionsLicence({
    required this.code,

    required this.name,

    required this.url,
  });

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'url', required: true, includeIfNull: false)
  final String url;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryShareOptionsLicence &&
          other.code == code &&
          other.name == name &&
          other.url == url;

  @override
  int get hashCode => code.hashCode + name.hashCode + url.hashCode;

  factory SharedLibraryShareOptionsLicence.fromJson(
    Map<String, dynamic> json,
  ) => _$SharedLibraryShareOptionsLicenceFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryShareOptionsLicenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
