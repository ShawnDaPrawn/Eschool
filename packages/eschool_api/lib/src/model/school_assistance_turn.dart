//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/school_assistance_answer.dart';
import 'package:eschool_api/src/model/school_assistance_citation.dart';
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_turn.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceTurn {
  /// Returns a new [SchoolAssistanceTurn] instance.
  SchoolAssistanceTurn({
    required this.id,

    required this.purpose,

    required this.state,

    required this.learnerId,

    required this.expiresAt,

    required this.language,

    required this.reviewText,

    required this.citations,

    required this.response,

    required this.schoolId,

    required this.readerId,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(
    name: r'purpose',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SchoolAssistanceTurnPurposeEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceTurnPurposeEnum purpose;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: SchoolAssistanceTurnStateEnum.unknownDefaultOpenApi,
  )
  final SchoolAssistanceTurnStateEnum state;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'review_text', required: true, includeIfNull: false)
  final String reviewText;

  @JsonKey(name: r'citations', required: true, includeIfNull: false)
  final List<SchoolAssistanceCitation> citations;

  @JsonKey(name: r'response', required: true, includeIfNull: true)
  final SchoolAssistanceAnswer? response;

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceTurn &&
          other.id == id &&
          other.purpose == purpose &&
          other.state == state &&
          other.learnerId == learnerId &&
          other.expiresAt == expiresAt &&
          other.language == language &&
          other.reviewText == reviewText &&
          other.citations == citations &&
          other.response == response &&
          other.schoolId == schoolId &&
          other.readerId == readerId;

  @override
  int get hashCode =>
      id.hashCode +
      purpose.hashCode +
      state.hashCode +
      learnerId.hashCode +
      expiresAt.hashCode +
      language.hashCode +
      reviewText.hashCode +
      citations.hashCode +
      (response == null ? 0 : response.hashCode) +
      schoolId.hashCode +
      readerId.hashCode;

  factory SchoolAssistanceTurn.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceTurnFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceTurnToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolAssistanceTurnPurposeEnum {
  @JsonValue(r'answer')
  answer(r'answer'),
  @JsonValue(r'translation')
  translation(r'translation'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceTurnPurposeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum SchoolAssistanceTurnStateEnum {
  @JsonValue(r'prepared')
  prepared(r'prepared'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolAssistanceTurnStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
