//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/teaching_learner_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_learner_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingLearnerPage {
  /// Returns a new [TeachingLearnerPage] instance.
  TeachingLearnerPage({
    required this.schoolId,

    required this.readerId,

    required this.groupId,

    required this.items,

    required this.page,

    required this.lastPage,
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

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<TeachingLearnerChoice> items;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingLearnerPage &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.groupId == groupId &&
          other.items == items &&
          other.page == page &&
          other.lastPage == lastPage;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      groupId.hashCode +
      items.hashCode +
      page.hashCode +
      lastPage.hashCode;

  factory TeachingLearnerPage.fromJson(Map<String, dynamic> json) =>
      _$TeachingLearnerPageFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingLearnerPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
