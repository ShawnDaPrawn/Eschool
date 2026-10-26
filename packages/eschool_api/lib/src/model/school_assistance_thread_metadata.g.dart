// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_thread_metadata.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceThreadMetadata _$SchoolAssistanceThreadMetadataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceThreadMetadata', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['id', 'state', 'revision', 'created_at'],
  );
  final val = SchoolAssistanceThreadMetadata(
    id: $checkedConvert('id', (v) => v as String),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$SchoolAssistanceThreadMetadataStateEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceThreadMetadataStateEnum.unknownDefaultOpenApi,
      ),
    ),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    createdAt: $checkedConvert(
      'created_at',
      (v) => DateTime.parse(v as String),
    ),
  );
  return val;
}, fieldKeyMap: const {'createdAt': 'created_at'});

Map<String, dynamic> _$SchoolAssistanceThreadMetadataToJson(
  SchoolAssistanceThreadMetadata instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': _$SchoolAssistanceThreadMetadataStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'created_at': instance.createdAt.toIso8601String(),
};

const _$SchoolAssistanceThreadMetadataStateEnumEnumMap = {
  SchoolAssistanceThreadMetadataStateEnum.open: 'open',
  SchoolAssistanceThreadMetadataStateEnum.closed: 'closed',
  SchoolAssistanceThreadMetadataStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
