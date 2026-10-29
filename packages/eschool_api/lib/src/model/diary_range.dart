//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diary_school_item.dart';
import 'package:eschool_api/src/model/diary_entry.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diary_range.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryRange {
  /// Returns a new [DiaryRange] instance.
  DiaryRange({
    required this.schoolId,

    required this.readerId,

    required this.today,

    required this.from,

    required this.to,

    required this.entries,

    required this.schoolItems,
  });

  /// The school the diary belongs to; the app refuses a reply for another school.
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  /// The signed-in learner's user id; the app refuses a reply for another account.
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final DateTime today;

  @JsonKey(name: r'from', required: true, includeIfNull: false)
  final DateTime from;

  @JsonKey(name: r'to', required: true, includeIfNull: false)
  final DateTime to;

  @JsonKey(name: r'entries', required: true, includeIfNull: false)
  final List<DiaryEntry> entries;

  @JsonKey(name: r'school_items', required: true, includeIfNull: false)
  final List<DiarySchoolItem> schoolItems;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryRange &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.today == today &&
          other.from == from &&
          other.to == to &&
          other.entries == entries &&
          other.schoolItems == schoolItems;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      today.hashCode +
      from.hashCode +
      to.hashCode +
      entries.hashCode +
      schoolItems.hashCode;

  factory DiaryRange.fromJson(Map<String, dynamic> json) =>
      _$DiaryRangeFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryRangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
