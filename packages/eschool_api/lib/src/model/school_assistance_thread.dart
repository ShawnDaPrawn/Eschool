//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_message.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_thread.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceThread {
  /// Returns a new [SchoolAssistanceThread] instance.
  SchoolAssistanceThread({
    required this.id,

    required this.revision,

    required this.state,

    required this.learnerId,

    required this.expiresAt,

    required this.page,

    required this.lastPage,

    required this.messages,

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
    unknownEnumValue: SchoolAssistanceThreadStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceThreadStateEnum state;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'messages', required: true, includeIfNull: false)
  final List<SchoolAssistanceMessage> messages;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceThread &&
          other.id == id &&
          other.revision == revision &&
          other.state == state &&
          other.learnerId == learnerId &&
          other.expiresAt == expiresAt &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.messages == messages &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      id.hashCode +
      revision.hashCode +
      state.hashCode +
      learnerId.hashCode +
      expiresAt.hashCode +
      page.hashCode +
      lastPage.hashCode +
      messages.hashCode +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceThread.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceThreadFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceThreadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceThreadStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceThreadStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
