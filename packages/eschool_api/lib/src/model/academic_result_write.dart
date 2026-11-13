//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_entrant_result.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_result_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicResultWrite {
  /// Returns a new [AcademicResultWrite] instance.
  AcademicResultWrite({
    required this.operationId,

    required this.state,

    required this.revision,

    required this.hostScore,

    required this.guestScore,

    required this.winner,

    required this.entrants,

    this.reasons,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicResultWriteStateEnum.unknownDefaultOpenApi,
  )
  final AcademicResultWriteStateEnum state;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 0
  // maximum: 9999
  @JsonKey(name: r'host_score', required: true, includeIfNull: false)
  final int hostScore;

  // minimum: 0
  // maximum: 9999
  @JsonKey(name: r'guest_score', required: true, includeIfNull: false)
  final int guestScore;

  /// A debate's adjudicators name the winner; in a quiz or speech contest it must follow the scores.
  @JsonKey(
    name: r'winner',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AcademicResultWriteWinnerEnum.unknownDefaultOpenApi,
  )
  final AcademicResultWriteWinnerEnum winner;

  @JsonKey(name: r'entrants', required: true, includeIfNull: false)
  final List<AcademicEntrantResult> entrants;

  @JsonKey(name: r'reasons', required: false, includeIfNull: false)
  final String? reasons;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicResultWrite &&
          other.operationId == operationId &&
          other.state == state &&
          other.revision == revision &&
          other.hostScore == hostScore &&
          other.guestScore == guestScore &&
          other.winner == winner &&
          other.entrants == entrants &&
          other.reasons == reasons;

  @override
  int get hashCode =>
      operationId.hashCode +
      state.hashCode +
      revision.hashCode +
      hostScore.hashCode +
      guestScore.hashCode +
      winner.hashCode +
      entrants.hashCode +
      (reasons == null ? 0 : reasons.hashCode);

  factory AcademicResultWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicResultWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicResultWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicResultWriteStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'final')
  final_(r'final'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicResultWriteStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

/// A debate's adjudicators name the winner; in a quiz or speech contest it must follow the scores.
enum AcademicResultWriteWinnerEnum {
  @JsonValue(r'host')
  host(r'host'),
  @JsonValue(r'guest')
  guest(r'guest'),
  @JsonValue(r'draw')
  draw(r'draw'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicResultWriteWinnerEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
