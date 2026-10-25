// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_audience_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationAudienceInput _$ConsultationAudienceInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationAudienceInput',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['kind']);
    final val = ConsultationAudienceInput(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$ConsultationAudienceInputKindEnumEnumMap,
          v,
          unknownValue: ConsultationAudienceInputKindEnum.unknownDefaultOpenApi,
        ),
      ),
      targetId: $checkedConvert('target_id', (v) => (v as num?)?.toInt()),
      parents: $checkedConvert('parents', (v) => v as bool?),
      includeStaff: $checkedConvert('include_staff', (v) => v as bool?),
    );
    return val;
  },
  fieldKeyMap: const {'targetId': 'target_id', 'includeStaff': 'include_staff'},
);

Map<String, dynamic> _$ConsultationAudienceInputToJson(
  ConsultationAudienceInput instance,
) => <String, dynamic>{
  'kind': _$ConsultationAudienceInputKindEnumEnumMap[instance.kind]!,
  'target_id': ?instance.targetId,
  'parents': ?instance.parents,
  'include_staff': ?instance.includeStaff,
};

const _$ConsultationAudienceInputKindEnumEnumMap = {
  ConsultationAudienceInputKindEnum.school: 'school',
  ConsultationAudienceInputKindEnum.grade: 'grade',
  ConsultationAudienceInputKindEnum.homeroom: 'homeroom',
  ConsultationAudienceInputKindEnum.group: 'group',
  ConsultationAudienceInputKindEnum.activityGroup: 'activity_group',
  ConsultationAudienceInputKindEnum.custom: 'custom',
  ConsultationAudienceInputKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
