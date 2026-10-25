// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_award_profile_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAwardProfileResponse _$RecognitionAwardProfileResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionAwardProfileResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionAwardProfileResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionAwardProfile.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionAwardProfileResponseToJson(
  RecognitionAwardProfileResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
