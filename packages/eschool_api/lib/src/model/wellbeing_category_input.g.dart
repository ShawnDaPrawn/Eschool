// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_category_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCategoryInput _$WellbeingCategoryInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCategoryInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'name',
        'emergency_guidance',
        'active',
      ],
    );
    final val = WellbeingCategoryInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      emergencyGuidance: $checkedConvert(
        'emergency_guidance',
        (v) => v as String,
      ),
      active: $checkedConvert('active', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'emergencyGuidance': 'emergency_guidance',
  },
);

Map<String, dynamic> _$WellbeingCategoryInputToJson(
  WellbeingCategoryInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'name': instance.name,
  'emergency_guidance': instance.emergencyGuidance,
  'active': instance.active,
};
