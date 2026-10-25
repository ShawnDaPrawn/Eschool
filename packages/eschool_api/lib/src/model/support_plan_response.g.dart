// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_plan_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportPlanResponse _$SupportPlanResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportPlanResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = SupportPlanResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => SupportPlan.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SupportPlanResponseToJson(
  SupportPlanResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
