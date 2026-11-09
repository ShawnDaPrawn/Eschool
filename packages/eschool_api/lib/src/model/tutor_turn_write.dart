//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_turn_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorTurnWrite {
  /// Returns a new [TutorTurnWrite] instance.
  TutorTurnWrite({
    required this.operationId,

    required this.question,

    this.working,

    this.photoIds,

    this.turnType,

    this.language,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final String question;

  @JsonKey(name: r'working', required: false, includeIfNull: false)
  final String? working;

  @JsonKey(name: r'photo_ids', required: false, includeIfNull: false)
  final Set<String>? photoIds;

  /// explain_back sends the learner's own explanation of the idea for kind feedback. Needs tutor v2.
  @JsonKey(
    name: r'turn_type',
    required: false,
    includeIfNull: false,
    unknownEnumValue: TutorTurnWriteTurnTypeEnum.unknownDefaultOpenApi,
  )
  final TutorTurnWriteTurnTypeEnum? turnType;

  /// Ask for the explanation in a home language listed in the configuration's home_languages (tutor v2 only). Any other language, including one whose safeguarding phrase list is still a draft, is refused with 422.
  @JsonKey(
    name: r'language',
    required: false,
    includeIfNull: false,
    unknownEnumValue: TutorTurnWriteLanguageEnum.unknownDefaultOpenApi,
  )
  final TutorTurnWriteLanguageEnum? language;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorTurnWrite &&
          other.operationId == operationId &&
          other.question == question &&
          other.working == working &&
          other.photoIds == photoIds &&
          other.turnType == turnType &&
          other.language == language;

  @override
  int get hashCode =>
      operationId.hashCode +
      question.hashCode +
      (working == null ? 0 : working.hashCode) +
      photoIds.hashCode +
      turnType.hashCode +
      (language == null ? 0 : language.hashCode);

  factory TutorTurnWrite.fromJson(Map<String, dynamic> json) =>
      _$TutorTurnWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TutorTurnWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// explain_back sends the learner's own explanation of the idea for kind feedback. Needs tutor v2.
enum TutorTurnWriteTurnTypeEnum {
  @JsonValue(r'ask')
  ask(r'ask'),
  @JsonValue(r'explain_back')
  explainBack(r'explain_back'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorTurnWriteTurnTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// Ask for the explanation in a home language listed in the configuration's home_languages (tutor v2 only). Any other language, including one whose safeguarding phrase list is still a draft, is refused with 422.
enum TutorTurnWriteLanguageEnum {
  @JsonValue(r'af')
  af(r'af'),
  @JsonValue(r'zu')
  zu(r'zu'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorTurnWriteLanguageEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
