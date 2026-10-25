//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_present.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionPresent {
  /// Returns a new [RecognitionPresent] instance.
  RecognitionPresent({
    required this.operationId,

    required this.learnerId,

    required this.sessionYearId,

    required this.awardDefinitionId,

    required this.awardNominationId,

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

  // minimum: 1
  @JsonKey(name: r'award_definition_id', required: true, includeIfNull: false)
  final int awardDefinitionId;

  // minimum: 1
  @JsonKey(name: r'award_nomination_id', required: true, includeIfNull: true)
  final int? awardNominationId;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final String presentedOn;

  @JsonKey(name: r'event', required: true, includeIfNull: true)
  final String? event;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionPresent &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.awardDefinitionId == awardDefinitionId &&
          other.awardNominationId == awardNominationId &&
          other.presentedOn == presentedOn &&
          other.event == event;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      awardDefinitionId.hashCode +
      (awardNominationId == null ? 0 : awardNominationId.hashCode) +
      presentedOn.hashCode +
      (event == null ? 0 : event.hashCode);

  factory RecognitionPresent.fromJson(Map<String, dynamic> json) =>
      _$RecognitionPresentFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionPresentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
