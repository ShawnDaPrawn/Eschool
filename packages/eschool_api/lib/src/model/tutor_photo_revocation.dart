//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_photo_revocation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorPhotoRevocation {
  /// Returns a new [TutorPhotoRevocation] instance.
  TutorPhotoRevocation({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.revoked,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorPhotoRevocation &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.revoked == revoked;

  @override
  int get hashCode =>
      schoolId.hashCode + readerId.hashCode + id.hashCode + revoked.hashCode;

  factory TutorPhotoRevocation.fromJson(Map<String, dynamic> json) =>
      _$TutorPhotoRevocationFromJson(json);

  Map<String, dynamic> toJson() => _$TutorPhotoRevocationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
