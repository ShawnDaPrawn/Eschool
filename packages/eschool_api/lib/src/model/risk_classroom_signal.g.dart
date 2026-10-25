// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_classroom_signal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskClassroomSignal _$RiskClassroomSignalFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'RiskClassroomSignal',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['source', 'score', 'reason_code', 'as_of'],
        );
        final val = RiskClassroomSignal(
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$RiskClassroomSignalSource_EnumEnumMap,
              v,
              unknownValue:
                  RiskClassroomSignalSource_Enum.unknownDefaultOpenApi,
            ),
          ),
          score: $checkedConvert('score', (v) => (v as num?)?.toInt()),
          reasonCode: $checkedConvert('reason_code', (v) => v as String),
          asOf: $checkedConvert('as_of', (v) => v as String),
        );
        return val;
      },
      fieldKeyMap: const {
        'source_': 'source',
        'reasonCode': 'reason_code',
        'asOf': 'as_of',
      },
    );

Map<String, dynamic> _$RiskClassroomSignalToJson(
  RiskClassroomSignal instance,
) => <String, dynamic>{
  'source': _$RiskClassroomSignalSource_EnumEnumMap[instance.source_]!,
  'score': instance.score,
  'reason_code': instance.reasonCode,
  'as_of': instance.asOf,
};

const _$RiskClassroomSignalSource_EnumEnumMap = {
  RiskClassroomSignalSource_Enum.academic: 'academic',
  RiskClassroomSignalSource_Enum.attendance: 'attendance',
  RiskClassroomSignalSource_Enum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
