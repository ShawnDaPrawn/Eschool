// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_meeting_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilyMeetingPageResponse _$FamilyMeetingPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilyMeetingPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FamilyMeetingPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FamilyMeetingPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$FamilyMeetingPageResponseCodeEnumEnumMap,
        v,
        unknownValue: FamilyMeetingPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilyMeetingPageResponseToJson(
  FamilyMeetingPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$FamilyMeetingPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$FamilyMeetingPageResponseCodeEnumEnumMap = {
  FamilyMeetingPageResponseCodeEnum.number200: 200,
  FamilyMeetingPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
