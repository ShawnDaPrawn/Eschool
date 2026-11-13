//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_competition_detail_awards_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCompetitionDetailAwardsInner {
  /// Returns a new [AcademicCompetitionDetailAwardsInner] instance.
  AcademicCompetitionDetailAwardsInner({
    required this.learnerId,

    required this.basis,

    required this.awardPresentationId,

    required this.title,
  });

  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'basis',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        AcademicCompetitionDetailAwardsInnerBasisEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailAwardsInnerBasisEnum basis;

  @JsonKey(name: r'award_presentation_id', required: true, includeIfNull: false)
  final int awardPresentationId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCompetitionDetailAwardsInner &&
          other.learnerId == learnerId &&
          other.basis == basis &&
          other.awardPresentationId == awardPresentationId &&
          other.title == title;

  @override
  int get hashCode =>
      learnerId.hashCode +
      basis.hashCode +
      awardPresentationId.hashCode +
      title.hashCode;

  factory AcademicCompetitionDetailAwardsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicCompetitionDetailAwardsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicCompetitionDetailAwardsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicCompetitionDetailAwardsInnerBasisEnum {
  @JsonValue(r'participation')
  participation(r'participation'),
  @JsonValue(r'placing')
  placing(r'placing'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailAwardsInnerBasisEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
