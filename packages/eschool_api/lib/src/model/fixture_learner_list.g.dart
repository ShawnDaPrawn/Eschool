// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_learner_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureLearnerList _$FixtureLearnerListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('FixtureLearnerList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items']);
      final val = FixtureLearnerList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => LearnerFixture.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$FixtureLearnerListToJson(FixtureLearnerList instance) =>
    <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
