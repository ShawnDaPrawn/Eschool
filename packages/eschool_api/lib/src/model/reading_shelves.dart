//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_week.dart';
import 'package:eschool_api/src/model/reading_shelf.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_shelves.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingShelves {
  /// Returns a new [ReadingShelves] instance.
  ReadingShelves({
    required this.gradeSet,

    required this.shelves,

    required this.reading,

    required this.aiChecks,
  });

  /// False when the learner's homeroom has no grade yet, so no shelf applies.
  @JsonKey(name: r'grade_set', required: true, includeIfNull: false)
  final bool gradeSet;

  @JsonKey(name: r'shelves', required: true, includeIfNull: false)
  final List<ReadingShelf> shelves;

  @JsonKey(name: r'reading', required: true, includeIfNull: false)
  final ReadingWeek reading;

  /// Whether AI-written comprehension checks are available. Always false for now.
  @JsonKey(name: r'ai_checks', required: true, includeIfNull: false)
  final bool aiChecks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingShelves &&
          other.gradeSet == gradeSet &&
          other.shelves == shelves &&
          other.reading == reading &&
          other.aiChecks == aiChecks;

  @override
  int get hashCode =>
      gradeSet.hashCode +
      shelves.hashCode +
      reading.hashCode +
      aiChecks.hashCode;

  factory ReadingShelves.fromJson(Map<String, dynamic> json) =>
      _$ReadingShelvesFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingShelvesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
