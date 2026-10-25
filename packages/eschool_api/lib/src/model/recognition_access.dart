//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAccess {
  /// Returns a new [RecognitionAccess] instance.
  RecognitionAccess({
    required this.module,

    required this.schoolId,

    required this.accountId,

    required this.sessionYearId,

    required this.today,

    required this.family,

    required this.canWrite,

    required this.manager,

    required this.gradeIds,
  });

  @JsonKey(name: r'module', required: true, includeIfNull: false)
  final String module;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'account_id', required: true, includeIfNull: false)
  final int accountId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'family', required: true, includeIfNull: false)
  final bool family;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'manager', required: true, includeIfNull: false)
  final bool manager;

  @JsonKey(name: r'grade_ids', required: true, includeIfNull: false)
  final List<int> gradeIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAccess &&
          other.module == module &&
          other.schoolId == schoolId &&
          other.accountId == accountId &&
          other.sessionYearId == sessionYearId &&
          other.today == today &&
          other.family == family &&
          other.canWrite == canWrite &&
          other.manager == manager &&
          other.gradeIds == gradeIds;

  @override
  int get hashCode =>
      module.hashCode +
      schoolId.hashCode +
      accountId.hashCode +
      sessionYearId.hashCode +
      today.hashCode +
      family.hashCode +
      canWrite.hashCode +
      manager.hashCode +
      gradeIds.hashCode;

  factory RecognitionAccess.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAccessFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
