//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'weekly_digest_list_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WeeklyDigestListItem {
  /// Returns a new [WeeklyDigestListItem] instance.
  WeeklyDigestListItem({
    required this.id,

    required this.learnerId,

    required this.language,

    required this.weekStart,

    required this.weekEnd,

    required this.title,

    required this.opened,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'week_start', required: true, includeIfNull: false)
  final String weekStart;

  @JsonKey(name: r'week_end', required: true, includeIfNull: false)
  final String weekEnd;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'opened', required: true, includeIfNull: false)
  final bool opened;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WeeklyDigestListItem &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.language == language &&
          other.weekStart == weekStart &&
          other.weekEnd == weekEnd &&
          other.title == title &&
          other.opened == opened;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      language.hashCode +
      weekStart.hashCode +
      weekEnd.hashCode +
      title.hashCode +
      opened.hashCode;

  factory WeeklyDigestListItem.fromJson(Map<String, dynamic> json) =>
      _$WeeklyDigestListItemFromJson(json);

  Map<String, dynamic> toJson() => _$WeeklyDigestListItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
