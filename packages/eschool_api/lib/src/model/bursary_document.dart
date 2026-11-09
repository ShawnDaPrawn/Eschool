//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'bursary_document.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BursaryDocument {
  /// Returns a new [BursaryDocument] instance.
  BursaryDocument({
    required this.id,

    required this.category,

    required this.mime,

    required this.bytes,

    this.revision,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'category', required: true, includeIfNull: false)
  final String category;

  @JsonKey(name: r'mime', required: true, includeIfNull: false)
  final String mime;

  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BursaryDocument &&
          other.id == id &&
          other.category == category &&
          other.mime == mime &&
          other.bytes == bytes &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      category.hashCode +
      mime.hashCode +
      bytes.hashCode +
      revision.hashCode;

  factory BursaryDocument.fromJson(Map<String, dynamic> json) =>
      _$BursaryDocumentFromJson(json);

  Map<String, dynamic> toJson() => _$BursaryDocumentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
