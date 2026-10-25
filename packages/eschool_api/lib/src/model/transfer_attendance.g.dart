// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_attendance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferAttendance _$TransferAttendanceFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TransferAttendance', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['on_date', 'kind', 'state']);
      final val = TransferAttendance(
        onDate: $checkedConvert('on_date', (v) => v as String),
        kind: $checkedConvert('kind', (v) => v as String),
        state: $checkedConvert('state', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'onDate': 'on_date'});

Map<String, dynamic> _$TransferAttendanceToJson(TransferAttendance instance) =>
    <String, dynamic>{
      'on_date': instance.onDate,
      'kind': instance.kind,
      'state': instance.state,
    };
