//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryEntry {
  /// Returns a new [DiaryEntry] instance.
  DiaryEntry({
    required this.id,

    required this.kind,

    required this.title,

    required this.body,

    required this.date,

    required this.remindAt,

    required this.done,

    required this.revision,

    required this.updatedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiaryEntryKindEnum.unknownDefaultOpenApi,
  )
  final DiaryEntryKindEnum kind;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'body', required: true, includeIfNull: true)
  final String? body;

  @JsonKey(name: r'date', required: true, includeIfNull: true)
  final DateTime? date;

  /// School-local wall time, YYYY-MM-DD HH:MM.
  @JsonKey(name: r'remind_at', required: true, includeIfNull: true)
  final String? remindAt;

  @JsonKey(name: r'done', required: true, includeIfNull: false)
  final bool done;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: true)
  final String? updatedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiaryEntry &&
          other.id == id &&
          other.kind == kind &&
          other.title == title &&
          other.body == body &&
          other.date == date &&
          other.remindAt == remindAt &&
          other.done == done &&
          other.revision == revision &&
          other.updatedAt == updatedAt;

  @override
  int get hashCode =>
      id.hashCode +
      kind.hashCode +
      title.hashCode +
      (body == null ? 0 : body.hashCode) +
      (date == null ? 0 : date.hashCode) +
      (remindAt == null ? 0 : remindAt.hashCode) +
      done.hashCode +
      revision.hashCode +
      (updatedAt == null ? 0 : updatedAt.hashCode);

  factory DiaryEntry.fromJson(Map<String, dynamic> json) =>
      _$DiaryEntryFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DiaryEntryKindEnum {
  @JsonValue(r'note')
  note(r'note'),
  @JsonValue(r'todo')
  todo(r'todo'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiaryEntryKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
