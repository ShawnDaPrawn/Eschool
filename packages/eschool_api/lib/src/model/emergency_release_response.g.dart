// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_release_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyReleaseResponse _$EmergencyReleaseResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyReleaseResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = EmergencyReleaseResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => EmergencyReleaseRecord.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$EmergencyReleaseResponseCodeEnumEnumMap,
        v,
        unknownValue: EmergencyReleaseResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$EmergencyReleaseResponseToJson(
  EmergencyReleaseResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$EmergencyReleaseResponseCodeEnumEnumMap[instance.code]!,
};

const _$EmergencyReleaseResponseCodeEnumEnumMap = {
  EmergencyReleaseResponseCodeEnum.number200: 200,
  EmergencyReleaseResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
