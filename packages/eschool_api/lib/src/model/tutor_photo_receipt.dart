//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_photo_receipt.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorPhotoReceipt {
  /// Returns a new [TutorPhotoReceipt] instance.
  TutorPhotoReceipt({
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
    unknownEnumValue: TutorPhotoReceiptMimeEnum.unknownDefaultOpenApi,
  )
  final TutorPhotoReceiptMimeEnum mime;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorPhotoReceipt &&
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

  factory TutorPhotoReceipt.fromJson(Map<String, dynamic> json) =>
      _$TutorPhotoReceiptFromJson(json);

  Map<String, dynamic> toJson() => _$TutorPhotoReceiptToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorPhotoReceiptMimeEnum {
  @JsonValue(r'image/png')
  imageSlashPng(r'image/png'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorPhotoReceiptMimeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
