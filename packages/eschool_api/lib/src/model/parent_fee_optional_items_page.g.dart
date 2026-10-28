// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_optional_items_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeOptionalItemsPage _$ParentFeeOptionalItemsPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeOptionalItemsPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'holder_user_id',
        'fee_account_id',
        'session_year_id',
        'writable',
        'items',
        'current_page',
        'per_page',
        'total',
      ],
    );
    final val = ParentFeeOptionalItemsPage(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
      feeAccountId: $checkedConvert(
        'fee_account_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      writable: $checkedConvert('writable', (v) => v as bool),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ParentFeeOptionalItem.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'holderUserId': 'holder_user_id',
    'feeAccountId': 'fee_account_id',
    'sessionYearId': 'session_year_id',
    'currentPage': 'current_page',
    'perPage': 'per_page',
  },
);

Map<String, dynamic> _$ParentFeeOptionalItemsPageToJson(
  ParentFeeOptionalItemsPage instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'holder_user_id': instance.holderUserId,
  'fee_account_id': instance.feeAccountId,
  'session_year_id': instance.sessionYearId,
  'writable': instance.writable,
  'items': instance.items.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'per_page': instance.perPage,
  'total': instance.total,
};
