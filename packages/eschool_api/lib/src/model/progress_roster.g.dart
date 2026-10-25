// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'progress_roster.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProgressRoster _$ProgressRosterFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ProgressRoster',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'group_id',
            'current_page',
            'last_page',
            'total',
            'learners',
          ],
        );
        final val = ProgressRoster(
          groupId: $checkedConvert('group_id', (v) => (v as num).toInt()),
          currentPage: $checkedConvert(
            'current_page',
            (v) => (v as num).toInt(),
          ),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
          total: $checkedConvert('total', (v) => (v as num).toInt()),
          learners: $checkedConvert(
            'learners',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      ProgressLearnerOption.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'groupId': 'group_id',
        'currentPage': 'current_page',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$ProgressRosterToJson(ProgressRoster instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
      'total': instance.total,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
