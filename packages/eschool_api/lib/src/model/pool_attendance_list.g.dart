// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_attendance_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolAttendanceList _$PoolAttendanceListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PoolAttendanceList', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['booking_id', 'on_date', 'learners']);
  final val = PoolAttendanceList(
    bookingId: $checkedConvert('booking_id', (v) => (v as num).toInt()),
    onDate: $checkedConvert('on_date', (v) => v as String),
    learners: $checkedConvert(
      'learners',
      (v) => (v as List<dynamic>)
          .map(
            (e) => PoolAttendanceListLearnersInner.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'bookingId': 'booking_id', 'onDate': 'on_date'});

Map<String, dynamic> _$PoolAttendanceListToJson(PoolAttendanceList instance) =>
    <String, dynamic>{
      'booking_id': instance.bookingId,
      'on_date': instance.onDate,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
