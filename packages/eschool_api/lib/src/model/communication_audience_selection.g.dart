// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_audience_selection.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationAudienceSelection _$CommunicationAudienceSelectionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CommunicationAudienceSelection',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['kind', 'session_year_id', 'parents'],
    );
    final val = CommunicationAudienceSelection(
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$CommunicationAudienceSelectionKindEnumEnumMap,
          v,
          unknownValue:
              CommunicationAudienceSelectionKindEnum.unknownDefaultOpenApi,
        ),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      targetId: $checkedConvert('target_id', (v) => (v as num?)?.toInt()),
      parents: $checkedConvert('parents', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'targetId': 'target_id',
  },
);

Map<String, dynamic> _$CommunicationAudienceSelectionToJson(
  CommunicationAudienceSelection instance,
) => <String, dynamic>{
  'kind': _$CommunicationAudienceSelectionKindEnumEnumMap[instance.kind]!,
  'session_year_id': instance.sessionYearId,
  'target_id': ?instance.targetId,
  'parents': instance.parents,
};

const _$CommunicationAudienceSelectionKindEnumEnumMap = {
  CommunicationAudienceSelectionKindEnum.school: 'school',
  CommunicationAudienceSelectionKindEnum.grade: 'grade',
  CommunicationAudienceSelectionKindEnum.homeroom: 'homeroom',
  CommunicationAudienceSelectionKindEnum.group: 'group',
  CommunicationAudienceSelectionKindEnum.activityGroup: 'activity_group',
  CommunicationAudienceSelectionKindEnum.custom: 'custom',
  CommunicationAudienceSelectionKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
