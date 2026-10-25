//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'transfer_document.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TransferDocument {
  /// Returns a new [TransferDocument] instance.
  TransferDocument({
    required this.id,

    required this.definitionId,

    required this.name,

    required this.mime,

    required this.extension_,

    required this.bytes,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'definition_id', required: true, includeIfNull: false)
  final int definitionId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'mime', required: true, includeIfNull: false)
  final String mime;

  @JsonKey(name: r'extension', required: true, includeIfNull: false)
  final String extension_;

  // minimum: 0
  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TransferDocument &&
          other.id == id &&
          other.definitionId == definitionId &&
          other.name == name &&
          other.mime == mime &&
          other.extension_ == extension_ &&
          other.bytes == bytes;

  @override
  int get hashCode =>
      id.hashCode +
      definitionId.hashCode +
      name.hashCode +
      mime.hashCode +
      extension_.hashCode +
      bytes.hashCode;

  factory TransferDocument.fromJson(Map<String, dynamic> json) =>
      _$TransferDocumentFromJson(json);

  Map<String, dynamic> toJson() => _$TransferDocumentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
