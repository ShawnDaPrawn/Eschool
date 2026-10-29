//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_item_state_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryItemStateInput {
  /// Returns a new [DiaryItemStateInput] instance.
  DiaryItemStateInput({required this.done, this.remindAt, this.note});

  @JsonKey(name: r'done', required: true, includeIfNull: false)
  final bool done;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'remind_at', required: false, includeIfNull: false)
  final String? remindAt;

  @JsonKey(name: r'note', required: false, includeIfNull: false)
  final String? note;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryItemStateInput &&
          other.done == done &&
          other.remindAt == remindAt &&
          other.note == note;

  @override
  int get hashCode =>
      done.hashCode +
      (remindAt == null ? 0 : remindAt.hashCode) +
      (note == null ? 0 : note.hashCode);

  factory DiaryItemStateInput.fromJson(Map<String, dynamic> json) =>
      _$DiaryItemStateInputFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryItemStateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
