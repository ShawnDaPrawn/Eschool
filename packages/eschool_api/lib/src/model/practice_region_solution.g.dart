// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_region_solution.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeRegionSolution _$PracticeRegionSolutionFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeRegionSolution', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['region', 'label']);
  final val = PracticeRegionSolution(
    region: $checkedConvert('region', (v) => v as String),
    label: $checkedConvert('label', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$PracticeRegionSolutionToJson(
  PracticeRegionSolution instance,
) => <String, dynamic>{'region': instance.region, 'label': instance.label};
