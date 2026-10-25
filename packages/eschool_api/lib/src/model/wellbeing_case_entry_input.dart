//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_case_entry_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingCaseEntryInput {
  /// Returns a new [WellbeingCaseEntryInput] instance.
  WellbeingCaseEntryInput({
    required this.operationId,

    required this.revision,

    required this.kind,

    required this.body,

    required this.happenedAt,

    this.supersedesId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingCaseEntryInputKindEnum.unknownDefaultOpenApi,
  )
  final WellbeingCaseEntryInputKindEnum kind;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'happened_at', required: true, includeIfNull: false)
  final DateTime happenedAt;

  // minimum: 1
  @JsonKey(name: r'supersedes_id', required: false, includeIfNull: false)
  final int? supersedesId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingCaseEntryInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.kind == kind &&
          other.body == body &&
          other.happenedAt == happenedAt &&
          other.supersedesId == supersedesId;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      kind.hashCode +
      body.hashCode +
      happenedAt.hashCode +
      (supersedesId == null ? 0 : supersedesId.hashCode);

  factory WellbeingCaseEntryInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingCaseEntryInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingCaseEntryInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingCaseEntryInputKindEnum {
  @JsonValue(r'note')
  note(r'note'),
  @JsonValue(r'escalation')
  escalation(r'escalation'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingCaseEntryInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
