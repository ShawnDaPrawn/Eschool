// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_fixture.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerFixture _$LearnerFixtureFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'LearnerFixture',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'discipline',
            'on_date',
            'starts_at',
            'venue',
            'field',
            'status',
            'competition',
            'team_label',
            'opponent',
            'home',
            'result',
          ],
        );
        final val = LearnerFixture(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          discipline: $checkedConvert('discipline', (v) => v as String),
          onDate: $checkedConvert('on_date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          venue: $checkedConvert('venue', (v) => v as String),
          field: $checkedConvert('field', (v) => v as String?),
          status: $checkedConvert('status', (v) => v as String),
          competition: $checkedConvert('competition', (v) => v as String?),
          teamLabel: $checkedConvert('team_label', (v) => v as String),
          opponent: $checkedConvert('opponent', (v) => v as String),
          home: $checkedConvert('home', (v) => v as bool),
          result: $checkedConvert(
            'result',
            (v) => LearnerFixtureResult.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'teamLabel': 'team_label',
      },
    );

Map<String, dynamic> _$LearnerFixtureToJson(LearnerFixture instance) =>
    <String, dynamic>{
      'id': instance.id,
      'discipline': instance.discipline,
      'on_date': instance.onDate,
      'starts_at': instance.startsAt,
      'venue': instance.venue,
      'field': instance.field,
      'status': instance.status,
      'competition': instance.competition,
      'team_label': instance.teamLabel,
      'opponent': instance.opponent,
      'home': instance.home,
      'result': instance.result.toJson(),
    };
