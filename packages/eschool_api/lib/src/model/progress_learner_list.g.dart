// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_learner_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressLearnerList _$ProgressLearnerListFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ProgressLearnerList', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['current_page', 'last_page', 'total', 'learners'],
  );
  final val = ProgressLearnerList(
    currentPage: $checkedConvert('current_page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    total: $checkedConvert('total', (v) => (v as num).toInt()),
    learners: $checkedConvert(
      'learners',
      (v) => (v as List<dynamic>)
          .map((e) => ProgressLearnerOption.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'currentPage': 'current_page', 'lastPage': 'last_page'});

Map<String, dynamic> _$ProgressLearnerListToJson(
  ProgressLearnerList instance,
) => <String, dynamic>{
  'current_page': instance.currentPage,
  'last_page': instance.lastPage,
  'total': instance.total,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
