// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contributions_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributionsResponse _$SharedLibraryContributionsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryContributionsResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SharedLibraryContributionsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SharedLibraryContributions.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryContributionsResponseToJson(
  SharedLibraryContributionsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
