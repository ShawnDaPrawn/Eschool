// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_ended_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeEndedResponseData _$ChildModeEndedResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ChildModeEndedResponseData', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['ended']);
  final val = ChildModeEndedResponseData(
    ended: $checkedConvert('ended', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$ChildModeEndedResponseDataToJson(
  ChildModeEndedResponseData instance,
) => <String, dynamic>{'ended': instance.ended};
