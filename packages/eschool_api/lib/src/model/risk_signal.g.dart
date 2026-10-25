// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_signal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskSignal _$RiskSignalFromJson(Map<String, dynamic> json) => $checkedCreate(
  'RiskSignal',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'source',
        'score',
        'signal_count',
        'reason_code',
        'grade_level_id',
        'as_of',
      ],
    );
    final val = RiskSignal(
      source_: $checkedConvert(
        'source',
        (v) => $enumDecode(
          _$RiskSignalSource_EnumEnumMap,
          v,
          unknownValue: RiskSignalSource_Enum.unknownDefaultOpenApi,
        ),
      ),
      score: $checkedConvert('score', (v) => (v as num?)?.toInt()),
      signalCount: $checkedConvert('signal_count', (v) => (v as num?)?.toInt()),
      reasonCode: $checkedConvert('reason_code', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      asOf: $checkedConvert('as_of', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'source_': 'source',
    'signalCount': 'signal_count',
    'reasonCode': 'reason_code',
    'gradeLevelId': 'grade_level_id',
    'asOf': 'as_of',
  },
);

Map<String, dynamic> _$RiskSignalToJson(RiskSignal instance) =>
    <String, dynamic>{
      'source': _$RiskSignalSource_EnumEnumMap[instance.source_]!,
      'score': instance.score,
      'signal_count': instance.signalCount,
      'reason_code': instance.reasonCode,
      'grade_level_id': instance.gradeLevelId,
      'as_of': instance.asOf,
    };

const _$RiskSignalSource_EnumEnumMap = {
  RiskSignalSource_Enum.academic: 'academic',
  RiskSignalSource_Enum.attendance: 'attendance',
  RiskSignalSource_Enum.discipline: 'discipline',
  RiskSignalSource_Enum.wellbeing: 'wellbeing',
  RiskSignalSource_Enum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
