//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_source.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentSource {
  /// Returns a new [ContentSource] instance.
  ContentSource({
    required this.name,

    required this.extension_,

    required this.mime,

    required this.bytes,

    required this.scanState,

    required this.scannedAt,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'extension', required: true, includeIfNull: false)
  final String extension_;

  @JsonKey(name: r'mime', required: true, includeIfNull: false)
  final String mime;

  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  @JsonKey(
    name: r'scan_state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ContentSourceScanStateEnum.unknownDefaultOpenApi,
  )
  final ContentSourceScanStateEnum scanState;

  @JsonKey(name: r'scanned_at', required: true, includeIfNull: true)
  final DateTime? scannedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentSource &&
          other.name == name &&
          other.extension_ == extension_ &&
          other.mime == mime &&
          other.bytes == bytes &&
          other.scanState == scanState &&
          other.scannedAt == scannedAt;

  @override
  int get hashCode =>
      name.hashCode +
      extension_.hashCode +
      mime.hashCode +
      bytes.hashCode +
      scanState.hashCode +
      (scannedAt == null ? 0 : scannedAt.hashCode);

  factory ContentSource.fromJson(Map<String, dynamic> json) =>
      _$ContentSourceFromJson(json);

  Map<String, dynamic> toJson() => _$ContentSourceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ContentSourceScanStateEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'clean')
  clean(r'clean'),
  @JsonValue(r'infected')
  infected(r'infected'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ContentSourceScanStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
