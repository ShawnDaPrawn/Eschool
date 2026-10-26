// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_close_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceCloseRequest _$SchoolAssistanceCloseRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceCloseRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'expected_revision', 'persona'],
    );
    final val = SchoolAssistanceCloseRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      persona: $checkedConvert(
        'persona',
        (v) => $enumDecode(
          _$SchoolAssistanceCloseRequestPersonaEnumEnumMap,
          v,
          unknownValue:
              SchoolAssistanceCloseRequestPersonaEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
  },
);

Map<String, dynamic> _$SchoolAssistanceCloseRequestToJson(
  SchoolAssistanceCloseRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'persona':
      _$SchoolAssistanceCloseRequestPersonaEnumEnumMap[instance.persona]!,
};

const _$SchoolAssistanceCloseRequestPersonaEnumEnumMap = {
  SchoolAssistanceCloseRequestPersonaEnum.parent: 'parent',
  SchoolAssistanceCloseRequestPersonaEnum.office: 'office',
  SchoolAssistanceCloseRequestPersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
