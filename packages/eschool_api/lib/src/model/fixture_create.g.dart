// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureCreate _$FixtureCreateFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureCreate',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'activity_group_id',
            'team_label',
            'discipline',
            'on_date',
            'venue',
            'host_is_home',
          ],
        );
        final val = FixtureCreate(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          teamLabel: $checkedConvert('team_label', (v) => v as String),
          discipline: $checkedConvert('discipline', (v) => v as String),
          sharedCompetitionId: $checkedConvert(
            'shared_competition_id',
            (v) => (v as num?)?.toInt(),
          ),
          guestSchoolId: $checkedConvert(
            'guest_school_id',
            (v) => (v as num?)?.toInt(),
          ),
          externalOpponent: $checkedConvert(
            'external_opponent',
            (v) => v as String?,
          ),
          onDate: $checkedConvert('on_date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          venue: $checkedConvert('venue', (v) => v as String),
          field: $checkedConvert('field', (v) => v as String?),
          hostIsHome: $checkedConvert('host_is_home', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'activityGroupId': 'activity_group_id',
        'teamLabel': 'team_label',
        'sharedCompetitionId': 'shared_competition_id',
        'guestSchoolId': 'guest_school_id',
        'externalOpponent': 'external_opponent',
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'hostIsHome': 'host_is_home',
      },
    );

Map<String, dynamic> _$FixtureCreateToJson(FixtureCreate instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'activity_group_id': instance.activityGroupId,
      'team_label': instance.teamLabel,
      'discipline': instance.discipline,
      'shared_competition_id': ?instance.sharedCompetitionId,
      'guest_school_id': ?instance.guestSchoolId,
      'external_opponent': ?instance.externalOpponent,
      'on_date': instance.onDate,
      'starts_at': ?instance.startsAt,
      'venue': instance.venue,
      'field': ?instance.field,
      'host_is_home': instance.hostIsHome,
    };
