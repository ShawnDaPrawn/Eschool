// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCategory _$WellbeingCategoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WellbeingCategory', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'name',
          'emergency_guidance',
          'active',
          'revision',
        ],
      );
      final val = WellbeingCategory(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        emergencyGuidance: $checkedConvert(
          'emergency_guidance',
          (v) => v as String,
        ),
        active: $checkedConvert('active', (v) => v as bool),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'emergencyGuidance': 'emergency_guidance'});

Map<String, dynamic> _$WellbeingCategoryToJson(WellbeingCategory instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'emergency_guidance': instance.emergencyGuidance,
      'active': instance.active,
      'revision': instance.revision,
    };
