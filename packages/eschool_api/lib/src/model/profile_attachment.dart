//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'profile_attachment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProfileAttachment {
  /// Returns a new [ProfileAttachment] instance.
  ProfileAttachment({
    required this.id,

    required this.name,

    required this.mime,

    required this.size,

    required this.url,
  });

  /// Opaque attachment identifier with its extension.
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'mime', required: true, includeIfNull: false)
  final String mime;

  // minimum: 0
  @JsonKey(name: r'size', required: true, includeIfNull: false)
  final int size;

  /// Authenticated asset URL. It requires the same person, field and school rights on every read.
  @JsonKey(name: r'url', required: true, includeIfNull: false)
  final String url;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProfileAttachment &&
          other.id == id &&
          other.name == name &&
          other.mime == mime &&
          other.size == size &&
          other.url == url;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      mime.hashCode +
      size.hashCode +
      url.hashCode;

  factory ProfileAttachment.fromJson(Map<String, dynamic> json) =>
      _$ProfileAttachmentFromJson(json);

  Map<String, dynamic> toJson() => _$ProfileAttachmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
