// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_test_listing.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeTestListing _$PracticeTestListingFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('PracticeTestListing', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'server_time', 'tests']);
  final val = PracticeTestListing(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    serverTime: $checkedConvert(
      'server_time',
      (v) => DateTime.parse(v as String),
    ),
    tests: $checkedConvert(
      'tests',
      (v) => (v as List<dynamic>)
          .map(
            (e) => PracticeTestListingItem.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'serverTime': 'server_time'});

Map<String, dynamic> _$PracticeTestListingToJson(
  PracticeTestListing instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'server_time': instance.serverTime.toIso8601String(),
  'tests': instance.tests.map((e) => e.toJson()).toList(),
};
