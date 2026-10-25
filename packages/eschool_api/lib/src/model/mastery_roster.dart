//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/mastery_roster_learner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'mastery_roster.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasteryRoster {
  /// Returns a new [MasteryRoster] instance.
  MasteryRoster({
    required this.groupId,

    required this.currentPage,

    required this.lastPage,

    required this.total,

    required this.learners,
  });

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

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
  final List<MasteryRosterLearner> learners;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasteryRoster &&
          other.groupId == groupId &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total &&
          other.learners == learners;

  @override
  int get hashCode =>
      groupId.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode +
      learners.hashCode;

  factory MasteryRoster.fromJson(Map<String, dynamic> json) =>
      _$MasteryRosterFromJson(json);

  Map<String, dynamic> toJson() => _$MasteryRosterToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
