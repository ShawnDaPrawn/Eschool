// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_snapshot.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceSnapshot _$CommunicationAudienceSnapshotFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationAudienceSnapshot',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['kind', 'session_year_id', 'target_id', 'parents'],
    );
    final val = CommunicationAudienceSnapshot(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$CommunicationAudienceSnapshotKindEnumEnumMap,
          v,
          unknownValue:
              CommunicationAudienceSnapshotKindEnum.unknownDefaultOpenApi,
        ),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      targetId: $checkedConvert('target_id', (v) => (v as num?)?.toInt()),
      parents: $checkedConvert('parents', (v) => v as bool),
      userIds: $checkedConvert(
        'user_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toSet(),
      ),
      name: $checkedConvert('name', (v) => v as String?),
      audienceRevision: $checkedConvert(
        'audience_revision',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'targetId': 'target_id',
    'userIds': 'user_ids',
    'audienceRevision': 'audience_revision',
  },
);

Map<String, dynamic> _$CommunicationAudienceSnapshotToJson(
  CommunicationAudienceSnapshot instance,
) => <String, dynamic>{
  'kind': _$CommunicationAudienceSnapshotKindEnumEnumMap[instance.kind]!,
  'session_year_id': instance.sessionYearId,
  'target_id': instance.targetId,
  'parents': instance.parents,
  'user_ids': ?instance.userIds?.toList(),
  'name': ?instance.name,
  'audience_revision': ?instance.audienceRevision,
};

const _$CommunicationAudienceSnapshotKindEnumEnumMap = {
  CommunicationAudienceSnapshotKindEnum.school: 'school',
  CommunicationAudienceSnapshotKindEnum.grade: 'grade',
  CommunicationAudienceSnapshotKindEnum.homeroom: 'homeroom',
  CommunicationAudienceSnapshotKindEnum.group: 'group',
  CommunicationAudienceSnapshotKindEnum.activityGroup: 'activity_group',
  CommunicationAudienceSnapshotKindEnum.custom: 'custom',
  CommunicationAudienceSnapshotKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
