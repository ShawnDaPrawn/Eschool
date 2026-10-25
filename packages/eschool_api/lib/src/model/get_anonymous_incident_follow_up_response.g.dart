// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_anonymous_incident_follow_up_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetAnonymousIncidentFollowUpResponse
_$GetAnonymousIncidentFollowUpResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetAnonymousIncidentFollowUpResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetAnonymousIncidentFollowUpResponse(
        error: $checkedConvert('error', (v) => v as bool),
        message: $checkedConvert('message', (v) => v as String),
        data: $checkedConvert(
          'data',
          (v) => IncidentPublicView.fromJson(v as Map<String, dynamic>),
        ),
        code: $checkedConvert('code', (v) => (v as num).toInt()),
      );
      return val;
    });

Map<String, dynamic> _$GetAnonymousIncidentFollowUpResponseToJson(
  GetAnonymousIncidentFollowUpResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
