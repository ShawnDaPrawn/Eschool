//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_entrant_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_result.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicResult {
  /// Returns a new [AcademicResult] instance.
  AcademicResult({
    required this.state,

    required this.hostScore,

    required this.guestScore,

    required this.winner,

    required this.entrants,

    required this.reasons,

    required this.revision,

    required this.submittedAt,
  });

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicResultStateEnum.unknownDefaultOpenApi,
  )
  final AcademicResultStateEnum state;

  @JsonKey(name: r'host_score', required: true, includeIfNull: true)
  final int? hostScore;

  @JsonKey(name: r'guest_score', required: true, includeIfNull: true)
  final int? guestScore;

  @JsonKey(
    name: r'winner',
    required: true,
    includeIfNull: true,
    unknownEnumValue: AcademicResultWinnerEnum.unknownDefaultOpenApi,
  )
  final AcademicResultWinnerEnum? winner;

  @JsonKey(name: r'entrants', required: true, includeIfNull: false)
  final List<AcademicEntrantResult> entrants;

  @JsonKey(name: r'reasons', required: true, includeIfNull: true)
  final String? reasons;

  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'submitted_at', required: true, includeIfNull: true)
  final DateTime? submittedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicResult &&
          other.state == state &&
          other.hostScore == hostScore &&
          other.guestScore == guestScore &&
          other.winner == winner &&
          other.entrants == entrants &&
          other.reasons == reasons &&
          other.revision == revision &&
          other.submittedAt == submittedAt;

  @override
  int get hashCode =>
      state.hashCode +
      (hostScore == null ? 0 : hostScore.hashCode) +
      (guestScore == null ? 0 : guestScore.hashCode) +
      (winner == null ? 0 : winner.hashCode) +
      entrants.hashCode +
      (reasons == null ? 0 : reasons.hashCode) +
      revision.hashCode +
      (submittedAt == null ? 0 : submittedAt.hashCode);

  factory AcademicResult.fromJson(Map<String, dynamic> json) =>
      _$AcademicResultFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicResultToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicResultStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'submitted')
  submitted(r'submitted'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicResultStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum AcademicResultWinnerEnum {
  @JsonValue(r'host')
  host(r'host'),
  @JsonValue(r'guest')
  guest(r'guest'),
  @JsonValue(r'draw')
  draw(r'draw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicResultWinnerEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
