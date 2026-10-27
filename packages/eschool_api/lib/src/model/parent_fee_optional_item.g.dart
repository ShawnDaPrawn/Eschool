// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_optional_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeOptionalItem _$ParentFeeOptionalItemFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeOptionalItem',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'fee_item_id',
        'name',
        'scope',
        'billing',
        'learner_id',
        'learner_name',
        'currency',
        'available',
        'can_approve',
        'price_cents',
        'share_cents',
        'vat_cents',
        'selected',
        'current_terms_approved',
        'revision',
        'fingerprint',
      ],
    );
    final val = ParentFeeOptionalItem(
      feeItemId: $checkedConvert('fee_item_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      scope: $checkedConvert(
        'scope',
        (v) => $enumDecode(
          _$ParentFeeOptionalItemScopeEnumEnumMap,
          v,
          unknownValue: ParentFeeOptionalItemScopeEnum.unknownDefaultOpenApi,
        ),
      ),
      billing: $checkedConvert(
        'billing',
        (v) => $enumDecode(
          _$ParentFeeOptionalItemBillingEnumEnumMap,
          v,
          unknownValue: ParentFeeOptionalItemBillingEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String?),
      currency: $checkedConvert('currency', (v) => v as String),
      available: $checkedConvert('available', (v) => v as bool),
      canApprove: $checkedConvert('can_approve', (v) => v as bool),
      priceCents: $checkedConvert('price_cents', (v) => (v as num?)?.toInt()),
      shareCents: $checkedConvert('share_cents', (v) => (v as num?)?.toInt()),
      vatCents: $checkedConvert('vat_cents', (v) => (v as num?)?.toInt()),
      selected: $checkedConvert('selected', (v) => v as bool),
      currentTermsApproved: $checkedConvert(
        'current_terms_approved',
        (v) => v as bool,
      ),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      fingerprint: $checkedConvert('fingerprint', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'feeItemId': 'fee_item_id',
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'canApprove': 'can_approve',
    'priceCents': 'price_cents',
    'shareCents': 'share_cents',
    'vatCents': 'vat_cents',
    'currentTermsApproved': 'current_terms_approved',
  },
);

Map<String, dynamic> _$ParentFeeOptionalItemToJson(
  ParentFeeOptionalItem instance,
) => <String, dynamic>{
  'fee_item_id': instance.feeItemId,
  'name': instance.name,
  'scope': _$ParentFeeOptionalItemScopeEnumEnumMap[instance.scope]!,
  'billing': _$ParentFeeOptionalItemBillingEnumEnumMap[instance.billing]!,
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'currency': instance.currency,
  'available': instance.available,
  'can_approve': instance.canApprove,
  'price_cents': instance.priceCents,
  'share_cents': instance.shareCents,
  'vat_cents': instance.vatCents,
  'selected': instance.selected,
  'current_terms_approved': instance.currentTermsApproved,
  'revision': instance.revision,
  'fingerprint': instance.fingerprint,
};

const _$ParentFeeOptionalItemScopeEnumEnumMap = {
  ParentFeeOptionalItemScopeEnum.learner: 'learner',
  ParentFeeOptionalItemScopeEnum.family: 'family',
  ParentFeeOptionalItemScopeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$ParentFeeOptionalItemBillingEnumEnumMap = {
  ParentFeeOptionalItemBillingEnum.oncePerYear: 'once_per_year',
  ParentFeeOptionalItemBillingEnum.perOccurrence: 'per_occurrence',
  ParentFeeOptionalItemBillingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
