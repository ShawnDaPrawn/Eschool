//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'competition_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CompetitionCreate {
  /// Returns a new [CompetitionCreate] instance.
  CompetitionCreate({
    required this.operationId,

    required this.name,

    required this.kind,

    required this.discipline,

    required this.season,

    required this.pointsWin,

    required this.pointsDraw,

    required this.pointsLoss,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CompetitionCreateKindEnum.unknownDefaultOpenApi,
  )
  final CompetitionCreateKindEnum kind;

  @JsonKey(name: r'discipline', required: true, includeIfNull: false)
  final String discipline;

  @JsonKey(name: r'season', required: true, includeIfNull: false)
  final String season;

  // minimum: 0
  // maximum: 10
  @JsonKey(name: r'points_win', required: true, includeIfNull: false)
  final int pointsWin;

  // minimum: 0
  // maximum: 10
  @JsonKey(name: r'points_draw', required: true, includeIfNull: false)
  final int pointsDraw;

  // minimum: 0
  // maximum: 10
  @JsonKey(name: r'points_loss', required: true, includeIfNull: false)
  final int pointsLoss;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CompetitionCreate &&
          other.operationId == operationId &&
          other.name == name &&
          other.kind == kind &&
          other.discipline == discipline &&
          other.season == season &&
          other.pointsWin == pointsWin &&
          other.pointsDraw == pointsDraw &&
          other.pointsLoss == pointsLoss;

  @override
  int get hashCode =>
      operationId.hashCode +
      name.hashCode +
      kind.hashCode +
      discipline.hashCode +
      season.hashCode +
      pointsWin.hashCode +
      pointsDraw.hashCode +
      pointsLoss.hashCode;

  factory CompetitionCreate.fromJson(Map<String, dynamic> json) =>
      _$CompetitionCreateFromJson(json);

  Map<String, dynamic> toJson() => _$CompetitionCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CompetitionCreateKindEnum {
  @JsonValue(r'sport')
  sport(r'sport'),
  @JsonValue(r'debate')
  debate(r'debate'),
  @JsonValue(r'quiz')
  quiz(r'quiz'),
  @JsonValue(r'speech')
  speech(r'speech'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CompetitionCreateKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
