// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_competition.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicCompetition _$AcademicCompetitionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicCompetition',
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
        'side',
        'their_sheet',
        'can_decide',
        'can_schedule',
        'can_report',
        'academic',
      ],
    );
    final val = AcademicCompetition(
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
          _$AcademicCompetitionStatusEnumEnumMap,
          v,
          unknownValue: AcademicCompetitionStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      myRole: $checkedConvert(
        'my_role',
        (v) => $enumDecode(
          _$AcademicCompetitionMyRoleEnumEnumMap,
          v,
          unknownValue: AcademicCompetitionMyRoleEnum.unknownDefaultOpenApi,
        ),
      ),
      myState: $checkedConvert(
        'my_state',
        (v) => $enumDecode(
          _$AcademicCompetitionMyStateEnumEnumMap,
          v,
          unknownValue: AcademicCompetitionMyStateEnum.unknownDefaultOpenApi,
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
      side: $checkedConvert(
        'side',
        (v) =>
            v == null ? null : FixtureSide.fromJson(v as Map<String, dynamic>),
      ),
      theirSheet: $checkedConvert(
        'their_sheet',
        (v) => (v as List<dynamic>?)
            ?.map((e) => FixtureSheetEntry.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      canDecide: $checkedConvert('can_decide', (v) => v as bool),
      canSchedule: $checkedConvert('can_schedule', (v) => v as bool),
      canReport: $checkedConvert('can_report', (v) => v as bool),
      academic: $checkedConvert(
        'academic',
        (v) => AcademicCompetitionDetail.fromJson(v as Map<String, dynamic>),
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
    'theirSheet': 'their_sheet',
    'canDecide': 'can_decide',
    'canSchedule': 'can_schedule',
    'canReport': 'can_report',
  },
);

Map<String, dynamic> _$AcademicCompetitionToJson(
  AcademicCompetition instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': instance.kind,
  'competition': instance.competition?.toJson(),
  'discipline': instance.discipline,
  'on_date': instance.onDate,
  'starts_at': instance.startsAt,
  'venue': instance.venue,
  'field': instance.field,
  'host_is_home': instance.hostIsHome,
  'status': _$AcademicCompetitionStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
  'my_role': _$AcademicCompetitionMyRoleEnumEnumMap[instance.myRole]!,
  'my_state': _$AcademicCompetitionMyStateEnumEnumMap[instance.myState]!,
  'open': instance.open,
  'host': instance.host.toJson(),
  'guest': instance.guest.toJson(),
  'result': instance.result.toJson(),
  'side': instance.side?.toJson(),
  'their_sheet': instance.theirSheet?.map((e) => e.toJson()).toList(),
  'can_decide': instance.canDecide,
  'can_schedule': instance.canSchedule,
  'can_report': instance.canReport,
  'academic': instance.academic.toJson(),
};

const _$AcademicCompetitionStatusEnumEnumMap = {
  AcademicCompetitionStatusEnum.scheduled: 'scheduled',
  AcademicCompetitionStatusEnum.cancelled: 'cancelled',
  AcademicCompetitionStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionMyRoleEnumEnumMap = {
  AcademicCompetitionMyRoleEnum.host: 'host',
  AcademicCompetitionMyRoleEnum.guest: 'guest',
  AcademicCompetitionMyRoleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$AcademicCompetitionMyStateEnumEnumMap = {
  AcademicCompetitionMyStateEnum.invited: 'invited',
  AcademicCompetitionMyStateEnum.accepted: 'accepted',
  AcademicCompetitionMyStateEnum.declined: 'declined',
  AcademicCompetitionMyStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
