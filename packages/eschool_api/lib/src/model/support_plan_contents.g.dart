// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_plan_contents.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportPlanContents _$SupportPlanContentsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportPlanContents', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['sias_stage', 'needs', 'accommodations'],
      );
      final val = SupportPlanContents(
        siasStage: $checkedConvert('sias_stage', (v) => v as String),
        needs: $checkedConvert('needs', (v) => v as String),
        accommodations: $checkedConvert('accommodations', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'siasStage': 'sias_stage'});

Map<String, dynamic> _$SupportPlanContentsToJson(
  SupportPlanContents instance,
) => <String, dynamic>{
  'sias_stage': instance.siasStage,
  'needs': instance.needs,
  'accommodations': instance.accommodations,
};
