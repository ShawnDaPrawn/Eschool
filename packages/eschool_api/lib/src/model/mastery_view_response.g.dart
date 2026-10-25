// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_view_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryViewResponse _$MasteryViewResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('MasteryViewResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = MasteryViewResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => MasteryView.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$MasteryViewResponseToJson(
  MasteryViewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
