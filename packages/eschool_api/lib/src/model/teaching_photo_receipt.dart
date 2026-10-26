//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_photo_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingPhotoReceipt {
  /// Returns a new [TeachingPhotoReceipt] instance.
  TeachingPhotoReceipt({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.sha256,

    required this.mime,

    required this.operationId,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'sha256', required: true, includeIfNull: false)
  final String sha256;

  @JsonKey(
    name: r'mime',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TeachingPhotoReceiptMimeEnum.unknownDefaultOpenApi,
  )
  final TeachingPhotoReceiptMimeEnum mime;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingPhotoReceipt &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.sha256 == sha256 &&
          other.mime == mime &&
          other.operationId == operationId;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      sha256.hashCode +
      mime.hashCode +
      operationId.hashCode;

  factory TeachingPhotoReceipt.fromJson(Map<String, dynamic> json) =>
      _$TeachingPhotoReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingPhotoReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TeachingPhotoReceiptMimeEnum {
  @JsonValue(r'image/png')
  imageSlashPng(r'image/png'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TeachingPhotoReceiptMimeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
