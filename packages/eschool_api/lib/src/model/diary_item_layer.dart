//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_item_layer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryItemLayer {
  /// Returns a new [DiaryItemLayer] instance.
  DiaryItemLayer({
    required this.done,

    required this.doneAt,

    required this.remindAt,

    required this.note,
  });

  /// Handed-in homework is done; otherwise the learner's own tick.
  @JsonKey(name: r'done', required: true, includeIfNull: false)
  final bool done;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'done_at', required: true, includeIfNull: true)
  final String? doneAt;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'remind_at', required: true, includeIfNull: true)
  final String? remindAt;

  @JsonKey(name: r'note', required: true, includeIfNull: true)
  final String? note;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryItemLayer &&
          other.done == done &&
          other.doneAt == doneAt &&
          other.remindAt == remindAt &&
          other.note == note;

  @override
  int get hashCode =>
      done.hashCode +
      (doneAt == null ? 0 : doneAt.hashCode) +
      (remindAt == null ? 0 : remindAt.hashCode) +
      (note == null ? 0 : note.hashCode);

  factory DiaryItemLayer.fromJson(Map<String, dynamic> json) =>
      _$DiaryItemLayerFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryItemLayerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
