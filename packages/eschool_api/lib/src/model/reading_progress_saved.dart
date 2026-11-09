//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reading_week.dart';
import 'package:eschool_api/src/model/reading_progress.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reading_progress_saved.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingProgressSaved {
  /// Returns a new [ReadingProgressSaved] instance.
  ReadingProgressSaved({
    required this.progress,

    required this.reading,

    required this.badges,
  });

  @JsonKey(name: r'progress', required: true, includeIfNull: false)
  final ReadingProgress progress;

  @JsonKey(name: r'reading', required: true, includeIfNull: false)
  final ReadingWeek reading;

  /// Titles of the reading badges earned with this save.
  @JsonKey(name: r'badges', required: true, includeIfNull: false)
  final List<String> badges;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingProgressSaved &&
          other.progress == progress &&
          other.reading == reading &&
          other.badges == badges;

  @override
  int get hashCode => progress.hashCode + reading.hashCode + badges.hashCode;

  factory ReadingProgressSaved.fromJson(Map<String, dynamic> json) =>
      _$ReadingProgressSavedFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingProgressSavedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
