// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_unavailable_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceUnavailableResponseData
_$SchoolAssistanceUnavailableResponseDataFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolAssistanceUnavailableResponseData', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['reason']);
      final val = SchoolAssistanceUnavailableResponseData(
        reason: $checkedConvert('reason', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SchoolAssistanceUnavailableResponseDataToJson(
  SchoolAssistanceUnavailableResponseData instance,
) => <String, dynamic>{'reason': instance.reason};
