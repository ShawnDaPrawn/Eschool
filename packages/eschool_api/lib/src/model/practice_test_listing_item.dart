//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_listing_sitting.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_listing_item.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestListingItem {
  /// Returns a new [PracticeTestListingItem] instance.
  PracticeTestListingItem({
    required this.blueprintId,

    required this.kind,

    required this.groupId,

    required this.title,

    required this.questionCount,

    required this.timeLimitMinutes,

    required this.opensAt,

    required this.closesAt,

    required this.window,

    required this.test,
  });

  // minimum: 1
  @JsonKey(name: r'blueprint_id', required: true, includeIfNull: false)
  final int blueprintId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestListingItemKindEnum.unknownDefaultOpenApi,
  )
  final PracticeTestListingItemKindEnum kind;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'title', required: true, includeIfNull: true)
  final String? title;

  // minimum: 1
  @JsonKey(name: r'question_count', required: true, includeIfNull: false)
  final int questionCount;

  @JsonKey(name: r'time_limit_minutes', required: true, includeIfNull: true)
  final int? timeLimitMinutes;

  @JsonKey(name: r'opens_at', required: true, includeIfNull: true)
  final DateTime? opensAt;

  @JsonKey(name: r'closes_at', required: true, includeIfNull: true)
  final DateTime? closesAt;

  @JsonKey(
    name: r'window',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PracticeTestListingItemWindowEnum.unknownDefaultOpenApi,
  )
  final PracticeTestListingItemWindowEnum window;

  @JsonKey(name: r'test', required: true, includeIfNull: true)
  final PracticeTestListingSitting? test;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestListingItem &&
          other.blueprintId == blueprintId &&
          other.kind == kind &&
          other.groupId == groupId &&
          other.title == title &&
          other.questionCount == questionCount &&
          other.timeLimitMinutes == timeLimitMinutes &&
          other.opensAt == opensAt &&
          other.closesAt == closesAt &&
          other.window == window &&
          other.test == test;

  @override
  int get hashCode =>
      blueprintId.hashCode +
      kind.hashCode +
      groupId.hashCode +
      (title == null ? 0 : title.hashCode) +
      questionCount.hashCode +
      (timeLimitMinutes == null ? 0 : timeLimitMinutes.hashCode) +
      (opensAt == null ? 0 : opensAt.hashCode) +
      (closesAt == null ? 0 : closesAt.hashCode) +
      window.hashCode +
      (test == null ? 0 : test.hashCode);

  factory PracticeTestListingItem.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestListingItemFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestListingItemToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PracticeTestListingItemKindEnum {
  @JsonValue(r'quick_quiz')
  quickQuiz(r'quick_quiz'),
  @JsonValue(r'cycle_test')
  cycleTest(r'cycle_test'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestListingItemKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum PracticeTestListingItemWindowEnum {
  @JsonValue(r'upcoming')
  upcoming(r'upcoming'),
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PracticeTestListingItemWindowEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
