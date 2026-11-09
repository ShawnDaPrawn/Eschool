//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learner_passport_award.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearnerPassportAward {
  /// Returns a new [LearnerPassportAward] instance.
  LearnerPassportAward({
    required this.title,

    required this.criteria,

    required this.presentedOn,

    required this.event,

    required this.outsideOrganisation,

    required this.kind,
  });

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'criteria', required: true, includeIfNull: false)
  final String criteria;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final DateTime presentedOn;

  @JsonKey(name: r'event', required: true, includeIfNull: true)
  final String? event;

  @JsonKey(name: r'outside_organisation', required: true, includeIfNull: true)
  final String? outsideOrganisation;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearnerPassportAwardKindEnum.unknownDefaultOpenApi,
  )
  final LearnerPassportAwardKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearnerPassportAward &&
          other.title == title &&
          other.criteria == criteria &&
          other.presentedOn == presentedOn &&
          other.event == event &&
          other.outsideOrganisation == outsideOrganisation &&
          other.kind == kind;

  @override
  int get hashCode =>
      title.hashCode +
      criteria.hashCode +
      presentedOn.hashCode +
      (event == null ? 0 : event.hashCode) +
      (outsideOrganisation == null ? 0 : outsideOrganisation.hashCode) +
      kind.hashCode;

  factory LearnerPassportAward.fromJson(Map<String, dynamic> json) =>
      _$LearnerPassportAwardFromJson(json);

  Map<String, dynamic> toJson() => _$LearnerPassportAwardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearnerPassportAwardKindEnum {
  @JsonValue(r'catalogue')
  catalogue(r'catalogue'),
  @JsonValue(r'outside')
  outside(r'outside'),
  @JsonValue(r'badge')
  badge(r'badge'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearnerPassportAwardKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
