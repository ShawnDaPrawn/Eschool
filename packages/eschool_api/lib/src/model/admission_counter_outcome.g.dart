// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'admission_counter_outcome.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AdmissionCounterOutcome _$AdmissionCounterOutcomeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AdmissionCounterOutcome',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'actor_user_id',
        'vendor_id',
        'receipt',
        'replayed',
      ],
    );
    final val = AdmissionCounterOutcome(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      actorUserId: $checkedConvert('actor_user_id', (v) => (v as num).toInt()),
      vendorId: $checkedConvert('vendor_id', (v) => (v as num).toInt()),
      receipt: $checkedConvert(
        'receipt',
        (v) => AdmissionCounterTicket.fromJson(v as Map<String, dynamic>),
      ),
      replayed: $checkedConvert('replayed', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'actorUserId': 'actor_user_id',
    'vendorId': 'vendor_id',
  },
);

Map<String, dynamic> _$AdmissionCounterOutcomeToJson(
  AdmissionCounterOutcome instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'actor_user_id': instance.actorUserId,
  'vendor_id': instance.vendorId,
  'receipt': instance.receipt.toJson(),
  'replayed': instance.replayed,
};
