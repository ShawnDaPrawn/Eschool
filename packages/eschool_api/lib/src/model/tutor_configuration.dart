//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_home_language.dart';
import 'package:eschool_api/src/model/tutor_group_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_configuration.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorConfiguration {
  /// Returns a new [TutorConfiguration] instance.
  TutorConfiguration({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.sessionYearId,

    required this.groups,

    required this.inferenceEnabled,

    required this.photoRequiresApprovedCrop,

    required this.feedbackIsProvisional,

    this.checkpointsEnabled,

    this.tutorVersion,

    this.explainBackEnabled,

    this.homeLanguages,

    this.memoryDays,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'groups', required: true, includeIfNull: false)
  final List<TutorGroupChoice> groups;

  @JsonKey(name: r'inference_enabled', required: true, includeIfNull: false)
  final bool inferenceEnabled;

  @JsonKey(
    name: r'photo_requires_approved_crop',
    required: true,
    includeIfNull: false,
  )
  final bool photoRequiresApprovedCrop;

  @JsonKey(
    name: r'feedback_is_provisional',
    required: true,
    includeIfNull: false,
  )
  final bool feedbackIsProvisional;

  /// True only while the owning learner has current daily-practice access. Independent of external model activation.
  @JsonKey(name: r'checkpoints_enabled', required: false, includeIfNull: false)
  final bool? checkpointsEnabled;

  /// The configured tutor prompt version. Version 2 adapts to the learner's stage and grade, uses worked examples, offers explain_back turns, a learner memory and home-language explanations.
  @JsonKey(
    name: r'tutor_version',
    required: false,
    includeIfNull: false,
    unknownEnumValue: TutorConfigurationTutorVersionEnum.unknownDefaultOpenApi,
  )
  final TutorConfigurationTutorVersionEnum? tutorVersion;

  @JsonKey(name: r'explain_back_enabled', required: false, includeIfNull: false)
  final bool? explainBackEnabled;

  /// Home languages the tutor offers now. A language appears only when the deployment enables it and its safeguarding phrase list has a recorded professional review; a draft list keeps it out.
  @JsonKey(name: r'home_languages', required: false, includeIfNull: false)
  final List<TutorHomeLanguage>? homeLanguages;

  // minimum: 1
  // maximum: 365
  @JsonKey(name: r'memory_days', required: false, includeIfNull: false)
  final int? memoryDays;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorConfiguration &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.groups == groups &&
          other.inferenceEnabled == inferenceEnabled &&
          other.photoRequiresApprovedCrop == photoRequiresApprovedCrop &&
          other.feedbackIsProvisional == feedbackIsProvisional &&
          other.checkpointsEnabled == checkpointsEnabled &&
          other.tutorVersion == tutorVersion &&
          other.explainBackEnabled == explainBackEnabled &&
          other.homeLanguages == homeLanguages &&
          other.memoryDays == memoryDays;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      groups.hashCode +
      inferenceEnabled.hashCode +
      photoRequiresApprovedCrop.hashCode +
      feedbackIsProvisional.hashCode +
      checkpointsEnabled.hashCode +
      tutorVersion.hashCode +
      explainBackEnabled.hashCode +
      homeLanguages.hashCode +
      memoryDays.hashCode;

  factory TutorConfiguration.fromJson(Map<String, dynamic> json) =>
      _$TutorConfigurationFromJson(json);

  Map<String, dynamic> toJson() => _$TutorConfigurationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// The configured tutor prompt version. Version 2 adapts to the learner's stage and grade, uses worked examples, offers explain_back turns, a learner memory and home-language explanations.
enum TutorConfigurationTutorVersionEnum {
  @JsonValue(1)
  number1('1'),
  @JsonValue(2)
  number2('2'),
  @JsonValue(11184809)
  unknownDefaultOpenApi('11184809');

  const TutorConfigurationTutorVersionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
