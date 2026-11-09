// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_standings_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionStandingsInner _$CompetitionStandingsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CompetitionStandingsInner', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'team',
      'played',
      'won',
      'drawn',
      'lost',
      'scored',
      'conceded',
      'difference',
      'points',
    ],
  );
  final val = CompetitionStandingsInner(
    team: $checkedConvert('team', (v) => v as String),
    played: $checkedConvert('played', (v) => (v as num).toInt()),
    won: $checkedConvert('won', (v) => (v as num).toInt()),
    drawn: $checkedConvert('drawn', (v) => (v as num).toInt()),
    lost: $checkedConvert('lost', (v) => (v as num).toInt()),
    scored: $checkedConvert('scored', (v) => (v as num).toInt()),
    conceded: $checkedConvert('conceded', (v) => (v as num).toInt()),
    difference: $checkedConvert('difference', (v) => (v as num).toInt()),
    points: $checkedConvert('points', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CompetitionStandingsInnerToJson(
  CompetitionStandingsInner instance,
) => <String, dynamic>{
  'team': instance.team,
  'played': instance.played,
  'won': instance.won,
  'drawn': instance.drawn,
  'lost': instance.lost,
  'scored': instance.scored,
  'conceded': instance.conceded,
  'difference': instance.difference,
  'points': instance.points,
};
