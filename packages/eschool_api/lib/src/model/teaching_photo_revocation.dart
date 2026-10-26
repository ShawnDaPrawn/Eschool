//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_photo_revocation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingPhotoRevocation {
  /// Returns a new [TeachingPhotoRevocation] instance.
  TeachingPhotoRevocation({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.revoked,

    required this.writeOperationId,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'revoked', required: true, includeIfNull: false)
  final bool revoked;

  @JsonKey(name: r'write_operation_id', required: true, includeIfNull: false)
  final String writeOperationId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingPhotoRevocation &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.revoked == revoked &&
          other.writeOperationId == writeOperationId;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      revoked.hashCode +
      writeOperationId.hashCode;

  factory TeachingPhotoRevocation.fromJson(Map<String, dynamic> json) =>
      _$TeachingPhotoRevocationFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingPhotoRevocationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
