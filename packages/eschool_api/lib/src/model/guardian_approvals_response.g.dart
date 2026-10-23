// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'guardian_approvals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GuardianApprovalsResponse _$GuardianApprovalsResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GuardianApprovalsResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GuardianApprovalsResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => (v as List<dynamic>)
          .map((e) => GuardianApproval.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$GuardianApprovalsResponseToJson(
  GuardianApprovalsResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.map((e) => e.toJson()).toList(),
  'code': instance.code,
};
