//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThreadResult {
  /// Returns a new [SchoolAssistanceThreadResult] instance.
  SchoolAssistanceThreadResult({
    required this.id,

    required this.revision,

    required this.state,

    this.messageId,

    this.assignedUserId,

    this.reason,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolAssistanceThreadResultStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceThreadResultStateEnum state;

  @JsonKey(name: r'message_id', required: false, includeIfNull: false)
  final String? messageId;

  // minimum: 1
  @JsonKey(name: r'assigned_user_id', required: false, includeIfNull: false)
  final int? assignedUserId;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThreadResult &&
          other.id == id &&
          other.revision == revision &&
          other.state == state &&
          other.messageId == messageId &&
          other.assignedUserId == assignedUserId &&
          other.reason == reason &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      state.hashCode +
      messageId.hashCode +
      assignedUserId.hashCode +
      reason.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceThreadResult.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceThreadResultFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceThreadResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceThreadResultStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceThreadResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
