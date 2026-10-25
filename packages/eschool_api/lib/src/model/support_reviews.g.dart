// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_reviews.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportReviews _$SupportReviewsFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportReviews', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['plan_id', 'page', 'last_page', 'items'],
      );
      final val = SupportReviews(
        planId: $checkedConvert('plan_id', (v) => (v as num).toInt()),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => SupportReview.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'planId': 'plan_id', 'lastPage': 'last_page'});

Map<String, dynamic> _$SupportReviewsToJson(SupportReviews instance) =>
    <String, dynamic>{
      'plan_id': instance.planId,
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
