// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_signals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSignalsResponse _$RiskSignalsResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskSignalsResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = RiskSignalsResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => RiskSignals.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$RiskSignalsResponseToJson(
  RiskSignalsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
