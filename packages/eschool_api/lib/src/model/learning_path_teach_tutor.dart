//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_teach_tutor.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathTeachTutor {
  /// Returns a new [LearningPathTeachTutor] instance.
  LearningPathTeachTutor({
    required this.available,

    required this.sessionId,

    required this.reason,
  });

  @JsonKey(name: r'available', required: true, includeIfNull: false)
  final bool available;

  @JsonKey(name: r'session_id', required: true, includeIfNull: true)
  final String? sessionId;

  @JsonKey(
    name: r'reason',
    required: true,
    includeIfNull: true,
    unknownEnumValue: LearningPathTeachTutorReasonEnum.unknownDefaultOpenApi,
  )
  final LearningPathTeachTutorReasonEnum? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathTeachTutor &&
          other.available == available &&
          other.sessionId == sessionId &&
          other.reason == reason;

  @override
  int get hashCode =>
      available.hashCode +
      (sessionId == null ? 0 : sessionId.hashCode) +
      (reason == null ? 0 : reason.hashCode);

  factory LearningPathTeachTutor.fromJson(Map<String, dynamic> json) =>
      _$LearningPathTeachTutorFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathTeachTutorToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathTeachTutorReasonEnum {
  @JsonValue(r'not_ready')
  notReady(r'not_ready'),
  @JsonValue(r'not_available')
  notAvailable(r'not_available'),
  @JsonValue(r'no_school_material')
  noSchoolMaterial(r'no_school_material'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathTeachTutorReasonEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
