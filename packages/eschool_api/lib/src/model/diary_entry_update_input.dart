//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_entry_update_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryEntryUpdateInput {
  /// Returns a new [DiaryEntryUpdateInput] instance.
  DiaryEntryUpdateInput({
    required this.revision,

    required this.title,

    this.body,

    this.date,

    this.remindAt,

    this.done,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: false, includeIfNull: false)
  final String? body;

  /// The day the entry belongs to. Required for a note; a to-do without a date shows until it is done.
  @JsonKey(name: r'date', required: false, includeIfNull: false)
  final DateTime? date;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'remind_at', required: false, includeIfNull: false)
  final String? remindAt;

  @JsonKey(name: r'done', required: false, includeIfNull: false)
  final bool? done;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryEntryUpdateInput &&
          other.revision == revision &&
          other.title == title &&
          other.body == body &&
          other.date == date &&
          other.remindAt == remindAt &&
          other.done == done;

  @override
  int get hashCode =>
      revision.hashCode +
      title.hashCode +
      (body == null ? 0 : body.hashCode) +
      (date == null ? 0 : date.hashCode) +
      (remindAt == null ? 0 : remindAt.hashCode) +
      done.hashCode;

  factory DiaryEntryUpdateInput.fromJson(Map<String, dynamic> json) =>
      _$DiaryEntryUpdateInputFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryEntryUpdateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
