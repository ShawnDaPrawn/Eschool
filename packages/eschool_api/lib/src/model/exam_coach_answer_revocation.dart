//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_answer_revocation.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachAnswerRevocation {
  /// Returns a new [ExamCoachAnswerRevocation] instance.
  ExamCoachAnswerRevocation({required this.id, required this.state});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ExamCoachAnswerRevocationStateEnum.unknownDefaultOpenApi,
  )
  final ExamCoachAnswerRevocationStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachAnswerRevocation &&
          other.id == id &&
          other.state == state;

  @override
  int get hashCode => id.hashCode + state.hashCode;

  factory ExamCoachAnswerRevocation.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachAnswerRevocationFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachAnswerRevocationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ExamCoachAnswerRevocationStateEnum {
  @JsonValue(r'purged')
  purged(r'purged'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ExamCoachAnswerRevocationStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
