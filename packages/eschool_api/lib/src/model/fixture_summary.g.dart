// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSummary _$FixtureSummaryFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureSummary',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'kind',
            'competition',
            'discipline',
            'on_date',
            'starts_at',
            'venue',
            'field',
            'host_is_home',
            'status',
            'revision',
            'my_role',
            'my_state',
            'open',
            'host',
            'guest',
            'result',
          ],
        );
        final val = FixtureSummary(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          kind: $checkedConvert('kind', (v) => v as String),
          competition: $checkedConvert(
            'competition',
            (v) => v == null
                ? null
                : GuardianInvitationPreviewLearnersInner.fromJson(
                    v as Map<String, dynamic>,
                  ),
          ),
          discipline: $checkedConvert('discipline', (v) => v as String),
          onDate: $checkedConvert('on_date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          venue: $checkedConvert('venue', (v) => v as String),
          field: $checkedConvert('field', (v) => v as String?),
          hostIsHome: $checkedConvert('host_is_home', (v) => v as bool),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$FixtureSummaryStatusEnumEnumMap,
              v,
              unknownValue: FixtureSummaryStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          myRole: $checkedConvert(
            'my_role',
            (v) => $enumDecode(
              _$FixtureSummaryMyRoleEnumEnumMap,
              v,
              unknownValue: FixtureSummaryMyRoleEnum.unknownDefaultOpenApi,
            ),
          ),
          myState: $checkedConvert(
            'my_state',
            (v) => $enumDecode(
              _$FixtureSummaryMyStateEnumEnumMap,
              v,
              unknownValue: FixtureSummaryMyStateEnum.unknownDefaultOpenApi,
            ),
          ),
          open: $checkedConvert('open', (v) => v as bool),
          host: $checkedConvert(
            'host',
            (v) => FixtureTeam.fromJson(v as Map<String, dynamic>),
          ),
          guest: $checkedConvert(
            'guest',
            (v) => FixtureTeam.fromJson(v as Map<String, dynamic>),
          ),
          result: $checkedConvert(
            'result',
            (v) => FixtureResultState.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'hostIsHome': 'host_is_home',
        'myRole': 'my_role',
        'myState': 'my_state',
      },
    );

Map<String, dynamic> _$FixtureSummaryToJson(FixtureSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': instance.kind,
      'competition': instance.competition?.toJson(),
      'discipline': instance.discipline,
      'on_date': instance.onDate,
      'starts_at': instance.startsAt,
      'venue': instance.venue,
      'field': instance.field,
      'host_is_home': instance.hostIsHome,
      'status': _$FixtureSummaryStatusEnumEnumMap[instance.status]!,
      'revision': instance.revision,
      'my_role': _$FixtureSummaryMyRoleEnumEnumMap[instance.myRole]!,
      'my_state': _$FixtureSummaryMyStateEnumEnumMap[instance.myState]!,
      'open': instance.open,
      'host': instance.host.toJson(),
      'guest': instance.guest.toJson(),
      'result': instance.result.toJson(),
    };

const _$FixtureSummaryStatusEnumEnumMap = {
  FixtureSummaryStatusEnum.scheduled: 'scheduled',
  FixtureSummaryStatusEnum.cancelled: 'cancelled',
  FixtureSummaryStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$FixtureSummaryMyRoleEnumEnumMap = {
  FixtureSummaryMyRoleEnum.host: 'host',
  FixtureSummaryMyRoleEnum.guest: 'guest',
  FixtureSummaryMyRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$FixtureSummaryMyStateEnumEnumMap = {
  FixtureSummaryMyStateEnum.invited: 'invited',
  FixtureSummaryMyStateEnum.accepted: 'accepted',
  FixtureSummaryMyStateEnum.declined: 'declined',
  FixtureSummaryMyStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
