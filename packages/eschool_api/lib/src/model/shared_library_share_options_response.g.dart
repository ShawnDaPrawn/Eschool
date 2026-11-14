// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_share_options_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryShareOptionsResponse _$SharedLibraryShareOptionsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryShareOptionsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SharedLibraryShareOptionsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SharedLibraryShareOptions.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryShareOptionsResponseToJson(
  SharedLibraryShareOptionsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
