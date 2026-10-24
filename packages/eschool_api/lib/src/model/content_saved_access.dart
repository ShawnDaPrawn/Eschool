//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'content_saved_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ContentSavedAccess {
  /// Returns a new [ContentSavedAccess] instance.
  ContentSavedAccess({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.allowedVersionIds,

    required this.serverTime,

    required this.offlineUntil,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'allowed_version_ids', required: true, includeIfNull: false)
  final Set<int> allowedVersionIds;

  @JsonKey(name: r'server_time', required: true, includeIfNull: false)
  final DateTime serverTime;

  @JsonKey(name: r'offline_until', required: true, includeIfNull: false)
  final DateTime offlineUntil;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ContentSavedAccess &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.allowedVersionIds == allowedVersionIds &&
          other.serverTime == serverTime &&
          other.offlineUntil == offlineUntil;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      allowedVersionIds.hashCode +
      serverTime.hashCode +
      offlineUntil.hashCode;

  factory ContentSavedAccess.fromJson(Map<String, dynamic> json) =>
      _$ContentSavedAccessFromJson(json);

  Map<String, dynamic> toJson() => _$ContentSavedAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
