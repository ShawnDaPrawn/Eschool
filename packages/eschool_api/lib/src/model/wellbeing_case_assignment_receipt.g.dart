// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_assignment_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCaseAssignmentReceipt _$WellbeingCaseAssignmentReceiptFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingCaseAssignmentReceipt',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'support_case_id',
        'user_id',
        'active',
        'revision',
        'starts_on',
        'ends_on',
      ],
    );
    final val = WellbeingCaseAssignmentReceipt(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      supportCaseId: $checkedConvert(
        'support_case_id',
        (v) => (v as num).toInt(),
      ),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'supportCaseId': 'support_case_id',
    'userId': 'user_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$WellbeingCaseAssignmentReceiptToJson(
  WellbeingCaseAssignmentReceipt instance,
) => <String, dynamic>{
  'id': instance.id,
  'support_case_id': instance.supportCaseId,
  'user_id': instance.userId,
  'active': instance.active,
  'revision': instance.revision,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
};
