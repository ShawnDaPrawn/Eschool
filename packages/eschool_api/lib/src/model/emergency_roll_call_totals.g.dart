// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_totals.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallTotals _$EmergencyRollCallTotalsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyRollCallTotals', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['accounted', 'unaccounted', 'released'],
  );
  final val = EmergencyRollCallTotals(
    accounted: $checkedConvert('accounted', (v) => (v as num).toInt()),
    unaccounted: $checkedConvert('unaccounted', (v) => (v as num).toInt()),
    released: $checkedConvert('released', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$EmergencyRollCallTotalsToJson(
  EmergencyRollCallTotals instance,
) => <String, dynamic>{
  'accounted': instance.accounted,
  'unaccounted': instance.unaccounted,
  'released': instance.released,
};
