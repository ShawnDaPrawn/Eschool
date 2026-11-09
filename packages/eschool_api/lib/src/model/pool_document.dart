//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_document.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolDocument {
  /// Returns a new [PoolDocument] instance.
  PoolDocument({
    required this.document,

    required this.version,

    required this.title,

    required this.text,
  });

  @JsonKey(
    name: r'document',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolDocumentDocumentEnum.unknownDefaultOpenApi,
  )
  final PoolDocumentDocumentEnum document;

  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final String version;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolDocument &&
          other.document == document &&
          other.version == version &&
          other.title == title &&
          other.text == text;

  @override
  int get hashCode =>
      document.hashCode + version.hashCode + title.hashCode + text.hashCode;

  factory PoolDocument.fromJson(Map<String, dynamic> json) =>
      _$PoolDocumentFromJson(json);

  Map<String, dynamic> toJson() => _$PoolDocumentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolDocumentDocumentEnum {
  @JsonValue(r'navigateed')
  navigateed(r'navigateed'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'pool_consent')
  poolConsent(r'pool_consent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolDocumentDocumentEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
