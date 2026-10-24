// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'communication_scope.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CommunicationScope _$CommunicationScopeFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CommunicationScope', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['kind', 'target_id', 'name']);
      final val = CommunicationScope(
        kind: $checkedConvert(
          'kind',
          (v) => $enumDecode(
            _$CommunicationScopeKindEnumEnumMap,
            v,
            unknownValue: CommunicationScopeKindEnum.unknownDefaultOpenApi,
          ),
        ),
        targetId: $checkedConvert('target_id', (v) => (v as num?)?.toInt()),
        name: $checkedConvert('name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'targetId': 'target_id'});

Map<String, dynamic> _$CommunicationScopeToJson(CommunicationScope instance) =>
    <String, dynamic>{
      'kind': _$CommunicationScopeKindEnumEnumMap[instance.kind]!,
      'target_id': instance.targetId,
      'name': instance.name,
    };

const _$CommunicationScopeKindEnumEnumMap = {
  CommunicationScopeKindEnum.school: 'school',
  CommunicationScopeKindEnum.grade: 'grade',
  CommunicationScopeKindEnum.homeroom: 'homeroom',
  CommunicationScopeKindEnum.group: 'group',
  CommunicationScopeKindEnum.custom: 'custom',
  CommunicationScopeKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
