// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_scores.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureScores _$FixtureScoresFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FixtureScores', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['host_score', 'guest_score']);
  final val = FixtureScores(
    hostScore: $checkedConvert('host_score', (v) => (v as num).toInt()),
    guestScore: $checkedConvert('guest_score', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'hostScore': 'host_score', 'guestScore': 'guest_score'});

Map<String, dynamic> _$FixtureScoresToJson(FixtureScores instance) =>
    <String, dynamic>{
      'host_score': instance.hostScore,
      'guest_score': instance.guestScore,
    };
