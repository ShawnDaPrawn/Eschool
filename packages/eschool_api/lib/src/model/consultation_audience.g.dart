// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_audience.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationAudience _$ConsultationAudienceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationAudience',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'kind',
        'target_id',
        'session_year_id',
        'parents',
        'include_staff',
      ],
    );
    final val = ConsultationAudience(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$ConsultationAudienceKindEnumEnumMap,
          v,
          unknownValue: ConsultationAudienceKindEnum.unknownDefaultOpenApi,
        ),
      ),
      targetId: $checkedConvert('target_id', (v) => (v as num?)?.toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      parents: $checkedConvert('parents', (v) => v as bool),
      includeStaff: $checkedConvert('include_staff', (v) => v as bool),
      userIds: $checkedConvert(
        'user_ids',
        (v) => (v as List<dynamic>?)?.map((e) => (e as num).toInt()).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'targetId': 'target_id',
    'sessionYearId': 'session_year_id',
    'includeStaff': 'include_staff',
    'userIds': 'user_ids',
  },
);

Map<String, dynamic> _$ConsultationAudienceToJson(
  ConsultationAudience instance,
) => <String, dynamic>{
  'kind': _$ConsultationAudienceKindEnumEnumMap[instance.kind]!,
  'target_id': instance.targetId,
  'session_year_id': instance.sessionYearId,
  'parents': instance.parents,
  'include_staff': instance.includeStaff,
  'user_ids': ?instance.userIds,
};

const _$ConsultationAudienceKindEnumEnumMap = {
  ConsultationAudienceKindEnum.school: 'school',
  ConsultationAudienceKindEnum.grade: 'grade',
  ConsultationAudienceKindEnum.homeroom: 'homeroom',
  ConsultationAudienceKindEnum.group: 'group',
  ConsultationAudienceKindEnum.activityGroup: 'activity_group',
  ConsultationAudienceKindEnum.custom: 'custom',
  ConsultationAudienceKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
