// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_identified_incident_follow_up_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetIdentifiedIncidentFollowUpResponse
_$GetIdentifiedIncidentFollowUpResponseFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GetIdentifiedIncidentFollowUpResponse', json, (
      $checkedConvert,
    ) {
      $checkKeys(
        json,
        requiredKeys: const ['error', 'message', 'data', 'code'],
      );
      final val = GetIdentifiedIncidentFollowUpResponse(
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

Map<String, dynamic> _$GetIdentifiedIncidentFollowUpResponseToJson(
  GetIdentifiedIncidentFollowUpResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
