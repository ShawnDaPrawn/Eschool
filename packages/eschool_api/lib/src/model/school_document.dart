//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_document.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolDocument {
  /// Returns a new [SchoolDocument] instance.
  SchoolDocument({
    this.id,

    required this.title,

    required this.kind,

    required this.url,
  });

  @JsonKey(name: r'id', required: false, includeIfNull: false)
  final String? id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'kind', required: true, includeIfNull: true)
  final String? kind;

  @JsonKey(name: r'url', required: true, includeIfNull: false)
  final String url;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolDocument &&
          other.id == id &&
          other.title == title &&
          other.kind == kind &&
          other.url == url;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      (kind == null ? 0 : kind.hashCode) +
      url.hashCode;

  factory SchoolDocument.fromJson(Map<String, dynamic> json) =>
      _$SchoolDocumentFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolDocumentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
