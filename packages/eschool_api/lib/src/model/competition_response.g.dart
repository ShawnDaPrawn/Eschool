// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionResponse _$CompetitionResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CompetitionResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = CompetitionResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => Competition.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CompetitionResponseToJson(
  CompetitionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
