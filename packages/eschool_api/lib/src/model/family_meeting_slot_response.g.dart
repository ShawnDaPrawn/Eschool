// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_slot_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingSlotResponse _$FamilyMeetingSlotResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyMeetingSlotResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyMeetingSlotResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyMeetingSlot.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyMeetingSlotResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyMeetingSlotResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyMeetingSlotResponseToJson(
  FamilyMeetingSlotResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyMeetingSlotResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyMeetingSlotResponseCodeEnumEnumMap = {
  FamilyMeetingSlotResponseCodeEnum.number200: 200,
  FamilyMeetingSlotResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
