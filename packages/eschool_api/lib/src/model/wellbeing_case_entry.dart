//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_entry.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseEntry {
  /// Returns a new [WellbeingCaseEntry] instance.
  WellbeingCaseEntry({
    required this.id,

    required this.authorId,

    required this.supersedesId,

    required this.kind,

    required this.body,

    required this.happenedAt,

    required this.createdAt,

    required this.authorName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'author_id', required: true, includeIfNull: false)
  final int authorId;

  // minimum: 1
  @JsonKey(name: r'supersedes_id', required: true, includeIfNull: true)
  final int? supersedesId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseEntryKindEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseEntryKindEnum kind;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'happened_at', required: true, includeIfNull: false)
  final DateTime happenedAt;

  @JsonKey(name: r'created_at', required: true, includeIfNull: false)
  final DateTime createdAt;

  @JsonKey(name: r'author_name', required: true, includeIfNull: false)
  final String authorName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseEntry &&
          other.id == id &&
          other.authorId == authorId &&
          other.supersedesId == supersedesId &&
          other.kind == kind &&
          other.body == body &&
          other.happenedAt == happenedAt &&
          other.createdAt == createdAt &&
          other.authorName == authorName;

  @override
  int get hashCode =>
      id.hashCode +
      authorId.hashCode +
      (supersedesId == null ? 0 : supersedesId.hashCode) +
      kind.hashCode +
      body.hashCode +
      happenedAt.hashCode +
      createdAt.hashCode +
      authorName.hashCode;

  factory WellbeingCaseEntry.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseEntryFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseEntryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseEntryKindEnum {
  @JsonValue(r'note')
  note(r'note'),
  @JsonValue(r'escalation')
  escalation(r'escalation'),
  @JsonValue(r'status')
  status(r'status'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseEntryKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
