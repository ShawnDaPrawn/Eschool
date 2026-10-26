// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'set_foundation_eligibility_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SetFoundationEligibilityResponse _$SetFoundationEligibilityResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SetFoundationEligibilityResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SetFoundationEligibilityResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FoundationEligibility.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SetFoundationEligibilityResponseToJson(
  SetFoundationEligibilityResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
