// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'approval_state_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ApprovalStateResponse _$ApprovalStateResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ApprovalStateResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = ApprovalStateResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => ApprovalState.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ApprovalStateResponseToJson(
  ApprovalStateResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
