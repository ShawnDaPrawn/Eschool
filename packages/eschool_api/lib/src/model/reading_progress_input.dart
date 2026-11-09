//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_progress_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingProgressInput {
  /// Returns a new [ReadingProgressInput] instance.
  ReadingProgressInput({required this.page, required this.seconds});

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  /// Seconds read since the last save; the server counts at most 900 per save.
  // minimum: 0
  // maximum: 86400
  @JsonKey(name: r'seconds', required: true, includeIfNull: false)
  final int seconds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingProgressInput &&
          other.page == page &&
          other.seconds == seconds;

  @override
  int get hashCode => page.hashCode + seconds.hashCode;

  factory ReadingProgressInput.fromJson(Map<String, dynamic> json) =>
      _$ReadingProgressInputFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingProgressInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
