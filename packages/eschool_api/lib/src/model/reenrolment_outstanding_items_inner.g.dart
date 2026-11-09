// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'reenrolment_outstanding_items_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ReenrolmentOutstandingItemsInner _$ReenrolmentOutstandingItemsInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ReenrolmentOutstandingItemsInner',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'name',
        'accepted_count',
        'fee_responsible_count',
        'awaiting',
      ],
    );
    final val = ReenrolmentOutstandingItemsInner(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      acceptedCount: $checkedConvert(
        'accepted_count',
        (v) => (v as num).toInt(),
      ),
      feeResponsibleCount: $checkedConvert(
        'fee_responsible_count',
        (v) => (v as num).toInt(),
      ),
      awaiting: $checkedConvert(
        'awaiting',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ReenrolmentOutstandingItemsInnerAwaitingInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'acceptedCount': 'accepted_count',
    'feeResponsibleCount': 'fee_responsible_count',
  },
);

Map<String, dynamic> _$ReenrolmentOutstandingItemsInnerToJson(
  ReenrolmentOutstandingItemsInner instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'name': instance.name,
  'accepted_count': instance.acceptedCount,
  'fee_responsible_count': instance.feeResponsibleCount,
  'awaiting': instance.awaiting.map((e) => e.toJson()).toList(),
};
