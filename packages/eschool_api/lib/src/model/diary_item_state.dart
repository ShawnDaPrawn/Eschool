//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/diary_item_source.dart';
import 'package:json_annotation/json_annotation.dart';

part 'diary_item_state.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryItemState {
  /// Returns a new [DiaryItemState] instance.
  DiaryItemState({
    required this.source_,

    required this.id,

    required this.done,

    required this.remindAt,

    required this.note,
  });

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiaryItemSource.unknownDefaultOpenApi,
  )
  final DiaryItemSource source_;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'done', required: true, includeIfNull: false)
  final bool done;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'remind_at', required: true, includeIfNull: true)
  final String? remindAt;

  @JsonKey(name: r'note', required: true, includeIfNull: true)
  final String? note;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryItemState &&
          other.source_ == source_ &&
          other.id == id &&
          other.done == done &&
          other.remindAt == remindAt &&
          other.note == note;

  @override
  int get hashCode =>
      source_.hashCode +
      id.hashCode +
      done.hashCode +
      (remindAt == null ? 0 : remindAt.hashCode) +
      (note == null ? 0 : note.hashCode);

  factory DiaryItemState.fromJson(Map<String, dynamic> json) =>
      _$DiaryItemStateFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryItemStateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
