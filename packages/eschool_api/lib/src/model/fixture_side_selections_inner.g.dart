// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_side_selections_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSideSelectionsInner _$FixtureSideSelectionsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FixtureSideSelectionsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'learner_name',
        'sheet_label',
        'position',
      ],
    );
    final val = FixtureSideSelectionsInner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      sheetLabel: $checkedConvert('sheet_label', (v) => v as String),
      position: $checkedConvert('position', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'sheetLabel': 'sheet_label',
  },
);

Map<String, dynamic> _$FixtureSideSelectionsInnerToJson(
  FixtureSideSelectionsInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'sheet_label': instance.sheetLabel,
  'position': instance.position,
};
