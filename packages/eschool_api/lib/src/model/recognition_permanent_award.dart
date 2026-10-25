//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_permanent_award.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPermanentAward {
  /// Returns a new [RecognitionPermanentAward] instance.
  RecognitionPermanentAward({
    required this.id,

    required this.sessionYearId,

    required this.title,

    required this.criteria,

    required this.presentedOn,

    required this.event,

    required this.outsideOrganisation,

    required this.sourceKind,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'criteria', required: true, includeIfNull: false)
  final String criteria;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final String presentedOn;

  @JsonKey(name: r'event', required: true, includeIfNull: true)
  final String? event;

  @JsonKey(name: r'outside_organisation', required: true, includeIfNull: true)
  final String? outsideOrganisation;

  @JsonKey(name: r'source_kind', required: true, includeIfNull: false)
  final String sourceKind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPermanentAward &&
          other.id == id &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.criteria == criteria &&
          other.presentedOn == presentedOn &&
          other.event == event &&
          other.outsideOrganisation == outsideOrganisation &&
          other.sourceKind == sourceKind;

  @override
  int get hashCode =>
      id.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      criteria.hashCode +
      presentedOn.hashCode +
      (event == null ? 0 : event.hashCode) +
      (outsideOrganisation == null ? 0 : outsideOrganisation.hashCode) +
      sourceKind.hashCode;

  factory RecognitionPermanentAward.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPermanentAwardFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionPermanentAwardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
