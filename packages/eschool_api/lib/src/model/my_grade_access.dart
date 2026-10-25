//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/my_grade_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradeAccess {
  /// Returns a new [MyGradeAccess] instance.
  MyGradeAccess({
    required this.schoolId,

    required this.readerId,

    required this.sessionYearId,

    required this.today,

    required this.writable,

    required this.grades,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'writable', required: true, includeIfNull: false)
  final bool writable;

  @JsonKey(name: r'grades', required: true, includeIfNull: false)
  final List<MyGradeChoice> grades;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradeAccess &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.sessionYearId == sessionYearId &&
          other.today == today &&
          other.writable == writable &&
          other.grades == grades;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      sessionYearId.hashCode +
      today.hashCode +
      writable.hashCode +
      grades.hashCode;

  factory MyGradeAccess.fromJson(Map<String, dynamic> json) =>
      _$MyGradeAccessFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradeAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
