// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'my_grade_signal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MyGradeSignal _$MyGradeSignalFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MyGradeSignal',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const ['source', 'score', 'reason_code', 'as_of'],
        );
        final val = MyGradeSignal(
          source_: $checkedConvert(
            'source',
            (v) => $enumDecode(
              _$MyGradeSignalSource_EnumEnumMap,
              v,
              unknownValue: MyGradeSignalSource_Enum.unknownDefaultOpenApi,
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

Map<String, dynamic> _$MyGradeSignalToJson(MyGradeSignal instance) =>
    <String, dynamic>{
      'source': _$MyGradeSignalSource_EnumEnumMap[instance.source_]!,
      'score': instance.score,
      'reason_code': instance.reasonCode,
      'as_of': instance.asOf,
    };

const _$MyGradeSignalSource_EnumEnumMap = {
  MyGradeSignalSource_Enum.academic: 'academic',
  MyGradeSignalSource_Enum.attendance: 'attendance',
  MyGradeSignalSource_Enum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
