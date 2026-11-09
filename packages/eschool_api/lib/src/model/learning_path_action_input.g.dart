// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learning_path_action_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearningPathActionInput _$LearningPathActionInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'LearningPathActionInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'expected_revision', 'action'],
    );
    final val = LearningPathActionInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      action: $checkedConvert(
        'action',
        (v) => $enumDecode(
          _$LearningPathActionInputActionEnumEnumMap,
          v,
          unknownValue: LearningPathActionInputActionEnum.unknownDefaultOpenApi,
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

Map<String, dynamic> _$LearningPathActionInputToJson(
  LearningPathActionInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'action': _$LearningPathActionInputActionEnumEnumMap[instance.action]!,
};

const _$LearningPathActionInputActionEnumEnumMap = {
  LearningPathActionInputActionEnum.next: 'next',
  LearningPathActionInputActionEnum.openTutor: 'open_tutor',
  LearningPathActionInputActionEnum.practise: 'practise',
  LearningPathActionInputActionEnum.check: 'check',
  LearningPathActionInputActionEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
