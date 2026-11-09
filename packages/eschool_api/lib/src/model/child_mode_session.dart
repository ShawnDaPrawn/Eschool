//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/child_mode_session_school.dart';
import 'package:json_annotation/json_annotation.dart';

part 'child_mode_session.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ChildModeSession {
  /// Returns a new [ChildModeSession] instance.
  ChildModeSession({
    required this.learnerId,

    required this.firstName,

    required this.school,

    required this.opens,

    required this.startedAt,

    required this.expiresAt,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'first_name', required: true, includeIfNull: false)
  final String firstName;

  @JsonKey(name: r'school', required: true, includeIfNull: false)
  final ChildModeSessionSchool school;

  /// What the child may open, in the order the app shows it.
  @JsonKey(
    name: r'opens',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ChildModeSessionOpensEnum.unknownDefaultOpenApi,
  )
  final List<ChildModeSessionOpensEnum> opens;

  @JsonKey(name: r'started_at', required: true, includeIfNull: false)
  final DateTime startedAt;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ChildModeSession &&
          other.learnerId == learnerId &&
          other.firstName == firstName &&
          other.school == school &&
          other.opens == opens &&
          other.startedAt == startedAt &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      learnerId.hashCode +
      firstName.hashCode +
      school.hashCode +
      opens.hashCode +
      startedAt.hashCode +
      expiresAt.hashCode;

  factory ChildModeSession.fromJson(Map<String, dynamic> json) =>
      _$ChildModeSessionFromJson(json);

  Map<String, dynamic> toJson() => _$ChildModeSessionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ChildModeSessionOpensEnum {
  @JsonValue(r'my_day')
  myDay(r'my_day'),
  @JsonValue(r'practice')
  practice(r'practice'),
  @JsonValue(r'tutor')
  tutor(r'tutor'),
  @JsonValue(r'library')
  library_(r'library'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ChildModeSessionOpensEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
