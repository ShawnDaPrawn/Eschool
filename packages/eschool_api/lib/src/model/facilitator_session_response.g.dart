// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'facilitator_session_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FacilitatorSessionResponse _$FacilitatorSessionResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FacilitatorSessionResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = FacilitatorSessionResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => FacilitatorProfile.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => v as String?),
  );
  return val;
});

Map<String, dynamic> _$FacilitatorSessionResponseToJson(
  FacilitatorSessionResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
