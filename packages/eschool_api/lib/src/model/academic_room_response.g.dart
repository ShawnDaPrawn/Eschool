// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_room_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicRoomResponse _$AcademicRoomResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicRoomResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = AcademicRoomResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => AcademicRoom.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$AcademicRoomResponseToJson(
  AcademicRoomResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
