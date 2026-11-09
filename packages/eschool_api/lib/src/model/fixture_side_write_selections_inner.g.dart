// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_side_write_selections_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSideWriteSelectionsInner _$FixtureSideWriteSelectionsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FixtureSideWriteSelectionsInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id']);
  final val = FixtureSideWriteSelectionsInner(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    sheetLabel: $checkedConvert('sheet_label', (v) => v as String?),
    position: $checkedConvert('position', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'sheetLabel': 'sheet_label'});

Map<String, dynamic> _$FixtureSideWriteSelectionsInnerToJson(
  FixtureSideWriteSelectionsInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'sheet_label': ?instance.sheetLabel,
  'position': ?instance.position,
};
