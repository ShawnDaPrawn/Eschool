// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'update_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

UpdateBursaryApplicationRequest _$UpdateBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('UpdateBursaryApplicationRequest', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'household'],
  );
  final val = UpdateBursaryApplicationRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    household: $checkedConvert(
      'household',
      (v) => BursaryHouseholdInput.fromJson(v as Map<String, dynamic>),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$UpdateBursaryApplicationRequestToJson(
  UpdateBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'household': instance.household.toJson(),
};
