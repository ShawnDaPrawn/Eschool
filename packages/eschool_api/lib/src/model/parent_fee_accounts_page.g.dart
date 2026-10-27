// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'parent_fee_accounts_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ParentFeeAccountsPage _$ParentFeeAccountsPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ParentFeeAccountsPage',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'items',
        'current_page',
        'per_page',
        'total',
        'school_id',
        'holder_user_id',
      ],
    );
    final val = ParentFeeAccountsPage(
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  ParentFeeAccountSummary.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
      perPage: $checkedConvert('per_page', (v) => (v as num).toInt()),
      total: $checkedConvert('total', (v) => (v as num).toInt()),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      holderUserId: $checkedConvert(
        'holder_user_id',
        (v) => (v as num).toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'currentPage': 'current_page',
    'perPage': 'per_page',
    'schoolId': 'school_id',
    'holderUserId': 'holder_user_id',
  },
);

Map<String, dynamic> _$ParentFeeAccountsPageToJson(
  ParentFeeAccountsPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'current_page': instance.currentPage,
  'per_page': instance.perPage,
  'total': instance.total,
  'school_id': instance.schoolId,
  'holder_user_id': instance.holderUserId,
};
