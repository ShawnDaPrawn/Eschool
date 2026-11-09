// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_preferences_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryPreferencesResponse _$FamilySummaryPreferencesResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('FamilySummaryPreferencesResponse', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'code', 'data']);
  final val = FamilySummaryPreferencesResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
    data: $checkedConvert(
      'data',
      (v) => FamilySummaryPreferencesResponseData.fromJson(
        v as Map<String, dynamic>,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$FamilySummaryPreferencesResponseToJson(
  FamilySummaryPreferencesResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'code': instance.code,
  'data': instance.data.toJson(),
};
