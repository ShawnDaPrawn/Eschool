//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_feedback.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_turn.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorTurn {
  /// Returns a new [TutorTurn] instance.
  TutorTurn({
    required this.id,

    required this.operationId,

    required this.state,

    required this.question,

    required this.working,

    required this.response,

    required this.failureCode,

    required this.supportMessage,

    required this.provisional,

    this.turnType,

    this.explainLanguage,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorTurnStateEnum.unknownDefaultOpenApi,
  )
  final TutorTurnStateEnum state;

  @JsonKey(name: r'question', required: true, includeIfNull: true)
  final String? question;

  @JsonKey(name: r'working', required: true, includeIfNull: true)
  final String? working;

  @JsonKey(name: r'response', required: true, includeIfNull: true)
  final TutorFeedback? response;

  @JsonKey(name: r'failure_code', required: true, includeIfNull: true)
  final String? failureCode;

  @JsonKey(name: r'support_message', required: true, includeIfNull: true)
  final String? supportMessage;

  @JsonKey(name: r'provisional', required: true, includeIfNull: false)
  final bool provisional;

  /// ask for an ordinary question, explain_back when the learner explained the idea in their own words (tutor v2).
  @JsonKey(
    name: r'turn_type',
    required: false,
    includeIfNull: false,
    unknownEnumValue: TutorTurnTurnTypeEnum.unknownDefaultOpenApi,
  )
  final TutorTurnTurnTypeEnum? turnType;

  /// The home language the tutor was asked to explain in (tutor v2), or null for English.
  @JsonKey(
    name: r'explain_language',
    required: false,
    includeIfNull: false,
    unknownEnumValue: TutorTurnExplainLanguageEnum.unknownDefaultOpenApi,
  )
  final TutorTurnExplainLanguageEnum? explainLanguage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorTurn &&
          other.id == id &&
          other.operationId == operationId &&
          other.state == state &&
          other.question == question &&
          other.working == working &&
          other.response == response &&
          other.failureCode == failureCode &&
          other.supportMessage == supportMessage &&
          other.provisional == provisional &&
          other.turnType == turnType &&
          other.explainLanguage == explainLanguage;

  @override
  int get hashCode =>
      id.hashCode +
      operationId.hashCode +
      state.hashCode +
      (question == null ? 0 : question.hashCode) +
      (working == null ? 0 : working.hashCode) +
      (response == null ? 0 : response.hashCode) +
      (failureCode == null ? 0 : failureCode.hashCode) +
      (supportMessage == null ? 0 : supportMessage.hashCode) +
      provisional.hashCode +
      turnType.hashCode +
      (explainLanguage == null ? 0 : explainLanguage.hashCode);

  factory TutorTurn.fromJson(Map<String, dynamic> json) =>
      _$TutorTurnFromJson(json);

  Map<String, dynamic> toJson() => _$TutorTurnToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorTurnStateEnum {
  @JsonValue(r'pending')
  pending(r'pending'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'failed')
  failed(r'failed'),
  @JsonValue(r'quarantined')
  quarantined(r'quarantined'),
  @JsonValue(r'purged')
  purged(r'purged'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorTurnStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// ask for an ordinary question, explain_back when the learner explained the idea in their own words (tutor v2).
enum TutorTurnTurnTypeEnum {
  @JsonValue(r'ask')
  ask(r'ask'),
  @JsonValue(r'explain_back')
  explainBack(r'explain_back'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorTurnTurnTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// The home language the tutor was asked to explain in (tutor v2), or null for English.
enum TutorTurnExplainLanguageEnum {
  @JsonValue(r'af')
  af(r'af'),
  @JsonValue(r'zu')
  zu(r'zu'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorTurnExplainLanguageEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
