// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_copy_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryCopyResponse _$SharedLibraryCopyResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryCopyResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SharedLibraryCopyResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SharedLibraryCopy.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryCopyResponseToJson(
  SharedLibraryCopyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
