// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cast_consultation_vote_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CastConsultationVoteResponse _$CastConsultationVoteResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CastConsultationVoteResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = CastConsultationVoteResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationVoteReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$CastConsultationVoteResponseToJson(
  CastConsultationVoteResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
