//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_progress.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingProgress {
  /// Returns a new [ReadingProgress] instance.
  ReadingProgress({
    required this.page,

    required this.furthestPage,

    required this.pageCount,

    required this.minutesRead,

    required this.finished,

    required this.finishedOn,
  });

  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'furthest_page', required: true, includeIfNull: false)
  final int furthestPage;

  @JsonKey(name: r'page_count', required: true, includeIfNull: true)
  final int? pageCount;

  @JsonKey(name: r'minutes_read', required: true, includeIfNull: false)
  final int minutesRead;

  @JsonKey(name: r'finished', required: true, includeIfNull: false)
  final bool finished;

  @JsonKey(name: r'finished_on', required: true, includeIfNull: true)
  final DateTime? finishedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingProgress &&
          other.page == page &&
          other.furthestPage == furthestPage &&
          other.pageCount == pageCount &&
          other.minutesRead == minutesRead &&
          other.finished == finished &&
          other.finishedOn == finishedOn;

  @override
  int get hashCode =>
      page.hashCode +
      furthestPage.hashCode +
      (pageCount == null ? 0 : pageCount.hashCode) +
      minutesRead.hashCode +
      finished.hashCode +
      (finishedOn == null ? 0 : finishedOn.hashCode);

  factory ReadingProgress.fromJson(Map<String, dynamic> json) =>
      _$ReadingProgressFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingProgressToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
