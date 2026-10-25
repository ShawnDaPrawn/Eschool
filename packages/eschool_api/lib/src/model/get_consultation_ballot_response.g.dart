// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_consultation_ballot_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetConsultationBallotResponse _$GetConsultationBallotResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GetConsultationBallotResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GetConsultationBallotResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ConsultationBallot.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GetConsultationBallotResponseToJson(
  GetConsultationBallotResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
