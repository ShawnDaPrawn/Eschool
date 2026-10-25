//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/progress_learner_option.dart';
import 'package:json_annotation/json_annotation.dart';

part 'progress_learner_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressLearnerList {
  /// Returns a new [ProgressLearnerList] instance.
  ProgressLearnerList({
    required this.currentPage,

    required this.lastPage,

    required this.total,

    required this.learners,
  });

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
  final List<ProgressLearnerOption> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressLearnerList &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.learners == learners;

  @override
  int get hashCode =>
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode +
      learners.hashCode;

  factory ProgressLearnerList.fromJson(Map<String, dynamic> json) =>
      _$ProgressLearnerListFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressLearnerListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
