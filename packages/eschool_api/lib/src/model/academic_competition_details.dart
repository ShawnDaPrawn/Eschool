//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_competition_details.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicCompetitionDetails {
  /// Returns a new [AcademicCompetitionDetails] instance.
  AcademicCompetitionDetails({
    required this.operationId,

    required this.revision,

    required this.motion,

    this.format,

    this.rounds,

    this.hostStance,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'motion', required: true, includeIfNull: false)
  final String motion;

  @JsonKey(name: r'format', required: false, includeIfNull: false)
  final String? format;

  // minimum: 1
  // maximum: 20
  @JsonKey(name: r'rounds', required: false, includeIfNull: false)
  final int? rounds;

  @JsonKey(
    name: r'host_stance',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        AcademicCompetitionDetailsHostStanceEnum.unknownDefaultOpenApi,
  )
  final AcademicCompetitionDetailsHostStanceEnum? hostStance;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicCompetitionDetails &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.motion == motion &&
          other.format == format &&
          other.rounds == rounds &&
          other.hostStance == hostStance;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      motion.hashCode +
      (format == null ? 0 : format.hashCode) +
      (rounds == null ? 0 : rounds.hashCode) +
      (hostStance == null ? 0 : hostStance.hashCode);

  factory AcademicCompetitionDetails.fromJson(Map<String, dynamic> json) =>
      _$AcademicCompetitionDetailsFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicCompetitionDetailsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AcademicCompetitionDetailsHostStanceEnum {
  @JsonValue(r'proposition')
  proposition(r'proposition'),
  @JsonValue(r'opposition')
  opposition(r'opposition'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AcademicCompetitionDetailsHostStanceEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
