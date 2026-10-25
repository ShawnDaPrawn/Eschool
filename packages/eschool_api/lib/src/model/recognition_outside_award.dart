//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_outside_award.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionOutsideAward {
  /// Returns a new [RecognitionOutsideAward] instance.
  RecognitionOutsideAward({
    required this.operationId,

    required this.learnerId,

    required this.sessionYearId,

    required this.title,

    required this.criteria,

    required this.outsideOrganisation,

    required this.presentedOn,

    required this.event,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'criteria', required: true, includeIfNull: false)
  final String criteria;

  @JsonKey(name: r'outside_organisation', required: true, includeIfNull: false)
  final String outsideOrganisation;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final String presentedOn;

  @JsonKey(name: r'event', required: true, includeIfNull: true)
  final String? event;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionOutsideAward &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.criteria == criteria &&
          other.outsideOrganisation == outsideOrganisation &&
          other.presentedOn == presentedOn &&
          other.event == event;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      criteria.hashCode +
      outsideOrganisation.hashCode +
      presentedOn.hashCode +
      (event == null ? 0 : event.hashCode);

  factory RecognitionOutsideAward.fromJson(Map<String, dynamic> json) =>
      _$RecognitionOutsideAwardFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionOutsideAwardToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
