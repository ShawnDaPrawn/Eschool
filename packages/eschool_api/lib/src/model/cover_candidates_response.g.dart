// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_candidates_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverCandidatesResponse _$CoverCandidatesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CoverCandidatesResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = CoverCandidatesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => CoverCandidates.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
});

Map<String, dynamic> _$CoverCandidatesResponseToJson(
  CoverCandidatesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
