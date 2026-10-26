// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pathway_mark.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PathwayMark _$PathwayMarkFromJson(Map<String, dynamic> json) => $checkedCreate(
  'PathwayMark',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['subject_id', 'percentage_bp']);
    final val = PathwayMark(
      subjectId: $checkedConvert('subject_id', (v) => (v as num).toInt()),
      percentageBp: $checkedConvert(
        'percentage_bp',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'subjectId': 'subject_id',
    'percentageBp': 'percentage_bp',
  },
);

Map<String, dynamic> _$PathwayMarkToJson(PathwayMark instance) =>
    <String, dynamic>{
      'subject_id': instance.subjectId,
      'percentage_bp': instance.percentageBp,
    };
