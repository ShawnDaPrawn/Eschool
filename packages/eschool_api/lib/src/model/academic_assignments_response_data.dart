//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_assignment.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_assignments_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicAssignmentsResponseData {
  /// Returns a new [AcademicAssignmentsResponseData] instance.
  AcademicAssignmentsResponseData({
    required this.assignments,

    required this.currentPage,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'assignments', required: true, includeIfNull: false)
  final List<AcademicAssignment> assignments;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicAssignmentsResponseData &&
          other.assignments == assignments &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      assignments.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory AcademicAssignmentsResponseData.fromJson(Map<String, dynamic> json) =>
      _$AcademicAssignmentsResponseDataFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicAssignmentsResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
