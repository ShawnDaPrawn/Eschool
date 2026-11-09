// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_decision.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryDecision _$BursaryDecisionFromJson(Map<String, dynamic> json) =>
    $checkedCreate('BursaryDecision', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'state', 'revision', 'fee_discount_id'],
      );
      final val = BursaryDecision(
        id: $checkedConvert('id', (v) => v as String),
        state: $checkedConvert('state', (v) => v as String),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        feeDiscountId: $checkedConvert(
          'fee_discount_id',
          (v) => (v as num?)?.toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'feeDiscountId': 'fee_discount_id'});

Map<String, dynamic> _$BursaryDecisionToJson(BursaryDecision instance) =>
    <String, dynamic>{
      'id': instance.id,
      'state': instance.state,
      'revision': instance.revision,
      'fee_discount_id': instance.feeDiscountId,
    };
