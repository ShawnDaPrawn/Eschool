//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_submission_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentSubmissionInput {
  /// Returns a new [IncidentSubmissionInput] instance.
  IncidentSubmissionInput({
    required this.secret,

    required this.categoryId,

    required this.body,

    required this.urgency,

    required this.anonymous,

    this.acknowledgedGuidance,

    this.identifyConsent,

    this.learnerId,
  });

  @JsonKey(name: r'secret', required: true, includeIfNull: false)
  final String secret;

  // minimum: 1
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(
    name: r'urgency',
    required: true,
    includeIfNull: false,
    unknownEnumValue: IncidentSubmissionInputUrgencyEnum.unknownDefaultOpenApi,
  )
  final IncidentSubmissionInputUrgencyEnum urgency;

  @JsonKey(name: r'anonymous', required: true, includeIfNull: false)
  final bool anonymous;

  @JsonKey(
    name: r'acknowledged_guidance',
    required: false,
    includeIfNull: false,
  )
  final bool? acknowledgedGuidance;

  @JsonKey(name: r'identify_consent', required: false, includeIfNull: false)
  final bool? identifyConsent;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentSubmissionInput &&
          other.secret == secret &&
          other.categoryId == categoryId &&
          other.body == body &&
          other.urgency == urgency &&
          other.anonymous == anonymous &&
          other.acknowledgedGuidance == acknowledgedGuidance &&
          other.identifyConsent == identifyConsent &&
          other.learnerId == learnerId;

  @override
  int get hashCode =>
      secret.hashCode +
      categoryId.hashCode +
      body.hashCode +
      urgency.hashCode +
      anonymous.hashCode +
      acknowledgedGuidance.hashCode +
      identifyConsent.hashCode +
      (learnerId == null ? 0 : learnerId.hashCode);

  factory IncidentSubmissionInput.fromJson(Map<String, dynamic> json) =>
      _$IncidentSubmissionInputFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentSubmissionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum IncidentSubmissionInputUrgencyEnum {
  @JsonValue(r'routine')
  routine(r'routine'),
  @JsonValue(r'urgent')
  urgent(r'urgent'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const IncidentSubmissionInputUrgencyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
