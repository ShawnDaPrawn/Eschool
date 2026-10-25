// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_people_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPeoplePageResponse _$SecureCardPeoplePageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardPeoplePageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SecureCardPeoplePageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SecureCardPeoplePage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$SecureCardPeoplePageResponseCodeEnumEnumMap,
        v,
        unknownValue:
            SecureCardPeoplePageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$SecureCardPeoplePageResponseToJson(
  SecureCardPeoplePageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$SecureCardPeoplePageResponseCodeEnumEnumMap[instance.code]!,
};

const _$SecureCardPeoplePageResponseCodeEnumEnumMap = {
  SecureCardPeoplePageResponseCodeEnum.number200: 200,
  SecureCardPeoplePageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
