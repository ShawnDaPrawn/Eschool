// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_terms_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTermsResponse _$SchoolTermsResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolTermsResponse', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'code', 'data'],
      );
      final val = SchoolTermsResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
        data: $checkedConvert(
          'data',
          (v) => SchoolTermsResponseData.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    });

Map<String, dynamic> _$SchoolTermsResponseToJson(
  SchoolTermsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
