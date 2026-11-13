// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shared_library_contribution_state_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SharedLibraryContributionStateResponse
_$SharedLibraryContributionStateResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SharedLibraryContributionStateResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = SharedLibraryContributionStateResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => SharedLibraryContributionState.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$SharedLibraryContributionStateResponseToJson(
  SharedLibraryContributionStateResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
