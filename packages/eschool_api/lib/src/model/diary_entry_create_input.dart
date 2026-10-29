//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diary_entry_create_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiaryEntryCreateInput {
  /// Returns a new [DiaryEntryCreateInput] instance.
  DiaryEntryCreateInput({
    required this.operationId,

    required this.kind,

    required this.title,

    this.body,

    this.date,

    this.remindAt,

    this.done,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: DiaryEntryCreateInputKindEnum.unknownDefaultOpenApi,
  )
  final DiaryEntryCreateInputKindEnum kind;

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
      other is DiaryEntryCreateInput &&
          other.operationId == operationId &&
          other.kind == kind &&
          other.title == title &&
          other.body == body &&
          other.date == date &&
          other.remindAt == remindAt &&
          other.done == done;

  @override
  int get hashCode =>
      operationId.hashCode +
      kind.hashCode +
      title.hashCode +
      (body == null ? 0 : body.hashCode) +
      (date == null ? 0 : date.hashCode) +
      (remindAt == null ? 0 : remindAt.hashCode) +
      done.hashCode;

  factory DiaryEntryCreateInput.fromJson(Map<String, dynamic> json) =>
      _$DiaryEntryCreateInputFromJson(json);

  Map<String, dynamic> toJson() => _$DiaryEntryCreateInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum DiaryEntryCreateInputKindEnum {
  @JsonValue(r'note')
  note(r'note'),
  @JsonValue(r'todo')
  todo(r'todo'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const DiaryEntryCreateInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
