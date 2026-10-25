// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'mastery_roster.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

MasteryRoster _$MasteryRosterFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'MasteryRoster',
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
        final val = MasteryRoster(
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
                      MasteryRosterLearner.fromJson(e as Map<String, dynamic>),
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

Map<String, dynamic> _$MasteryRosterToJson(MasteryRoster instance) =>
    <String, dynamic>{
      'group_id': instance.groupId,
      'current_page': instance.currentPage,
      'last_page': instance.lastPage,
      'total': instance.total,
      'learners': instance.learners.map((e) => e.toJson()).toList(),
    };
