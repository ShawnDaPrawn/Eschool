// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_office_thread_metadata.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceOfficeThreadMetadata
_$SchoolAssistanceOfficeThreadMetadataFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SchoolAssistanceOfficeThreadMetadata',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'state',
            'revision',
            'created_at',
            'assigned_to_reader',
          ],
        );
        final val = SchoolAssistanceOfficeThreadMetadata(
          id: $checkedConvert('id', (v) => v as String),
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$SchoolAssistanceOfficeThreadMetadataStateEnumEnumMap,
              v,
              unknownValue: SchoolAssistanceOfficeThreadMetadataStateEnum
                  .unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          createdAt: $checkedConvert(
            'created_at',
            (v) => DateTime.parse(v as String),
          ),
          assignedToReader: $checkedConvert(
            'assigned_to_reader',
            (v) => v as bool,
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'createdAt': 'created_at',
        'assignedToReader': 'assigned_to_reader',
      },
    );

Map<String, dynamic> _$SchoolAssistanceOfficeThreadMetadataToJson(
  SchoolAssistanceOfficeThreadMetadata instance,
) => <String, dynamic>{
  'id': instance.id,
  'state':
      _$SchoolAssistanceOfficeThreadMetadataStateEnumEnumMap[instance.state]!,
  'revision': instance.revision,
  'created_at': instance.createdAt.toIso8601String(),
  'assigned_to_reader': instance.assignedToReader,
};

const _$SchoolAssistanceOfficeThreadMetadataStateEnumEnumMap = {
  SchoolAssistanceOfficeThreadMetadataStateEnum.open: 'open',
  SchoolAssistanceOfficeThreadMetadataStateEnum.closed: 'closed',
  SchoolAssistanceOfficeThreadMetadataStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
