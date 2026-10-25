// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_nomination_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionNominationPageResponse _$RecognitionNominationPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RecognitionNominationPageResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = RecognitionNominationPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => RecognitionNominationPage.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$RecognitionNominationPageResponseToJson(
  RecognitionNominationPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
