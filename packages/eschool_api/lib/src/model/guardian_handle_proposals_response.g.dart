// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_handle_proposals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianHandleProposalsResponse _$GuardianHandleProposalsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianHandleProposalsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GuardianHandleProposalsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => (v as List<dynamic>)
          .map(
            (e) => GuardianHandleProposal.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GuardianHandleProposalsResponseToJson(
  GuardianHandleProposalsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.map((e) => e.toJson()).toList(),
  'code': instance.code,
};
