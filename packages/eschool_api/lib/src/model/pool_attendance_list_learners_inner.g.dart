// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_attendance_list_learners_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolAttendanceListLearnersInner _$PoolAttendanceListLearnersInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolAttendanceListLearnersInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'status']);
  final val = PoolAttendanceListLearnersInner(
    name: $checkedConvert('name', (v) => v as String),
    status: $checkedConvert('status', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$PoolAttendanceListLearnersInnerToJson(
  PoolAttendanceListLearnersInner instance,
) => <String, dynamic>{'name': instance.name, 'status': instance.status};
