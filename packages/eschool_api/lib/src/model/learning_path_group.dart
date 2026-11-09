//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_group_learners_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_group.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathGroup {
  /// Returns a new [LearningPathGroup] instance.
  LearningPathGroup({
    required this.schoolId,

    required this.readerId,

    required this.groupId,

    required this.subjectId,

    required this.currentPage,

    required this.lastPage,

    required this.total,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @JsonKey(name: r'learners', required: true, includeIfNull: false)
  final List<LearningPathGroupLearnersInner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathGroup &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.groupId == groupId &&
          other.subjectId == subjectId &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.learners == learners;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      groupId.hashCode +
      subjectId.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode +
      learners.hashCode;

  factory LearningPathGroup.fromJson(Map<String, dynamic> json) =>
      _$LearningPathGroupFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathGroupToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
