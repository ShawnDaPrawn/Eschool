// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_application_list_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryApplicationListResponse _$BursaryApplicationListResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('BursaryApplicationListResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = BursaryApplicationListResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => BursaryApplicationList.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$BursaryApplicationListResponseCodeEnumEnumMap,
        v,
        unknownValue:
            BursaryApplicationListResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$BursaryApplicationListResponseToJson(
  BursaryApplicationListResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$BursaryApplicationListResponseCodeEnumEnumMap[instance.code]!,
};

const _$BursaryApplicationListResponseCodeEnumEnumMap = {
  BursaryApplicationListResponseCodeEnum.number200: 200,
  BursaryApplicationListResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
