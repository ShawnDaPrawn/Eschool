//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'facilitator_policy.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FacilitatorPolicy {
  /// Returns a new [FacilitatorPolicy] instance.
  FacilitatorPolicy({
    required this.document,

    required this.version,

    required this.title,

    required this.text,
  });

  @JsonKey(
    name: r'document',
    required: true,
    includeIfNull: false,
    unknownEnumValue: FacilitatorPolicyDocumentEnum.unknownDefaultOpenApi,
  )
  final FacilitatorPolicyDocumentEnum document;

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FacilitatorPolicy &&
          other.document == document &&
          other.version == version &&
          other.title == title &&
          other.text == text;

  @override
  int get hashCode =>
      document.hashCode + version.hashCode + title.hashCode + text.hashCode;

  factory FacilitatorPolicy.fromJson(Map<String, dynamic> json) =>
      _$FacilitatorPolicyFromJson(json);

  Map<String, dynamic> toJson() => _$FacilitatorPolicyToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum FacilitatorPolicyDocumentEnum {
  @JsonValue(r'navigateed')
  navigateed(r'navigateed'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const FacilitatorPolicyDocumentEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
