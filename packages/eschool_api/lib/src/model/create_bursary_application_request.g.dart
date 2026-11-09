// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'create_bursary_application_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CreateBursaryApplicationRequest _$CreateBursaryApplicationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'CreateBursaryApplicationRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'learner_id',
        'session_year_id',
        'household',
      ],
    );
    final val = CreateBursaryApplicationRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      household: $checkedConvert(
        'household',
        (v) => BursaryHouseholdInput.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerId': 'learner_id',
    'sessionYearId': 'session_year_id',
  },
);

Map<String, dynamic> _$CreateBursaryApplicationRequestToJson(
  CreateBursaryApplicationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
  'session_year_id': instance.sessionYearId,
  'household': instance.household.toJson(),
};
