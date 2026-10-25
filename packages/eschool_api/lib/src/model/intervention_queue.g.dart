// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_queue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionQueue _$InterventionQueueFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'InterventionQueue',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'session_year_id',
            'page',
            'last_page',
            'interventions',
          ],
        );
        final val = InterventionQueue(
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          page: $checkedConvert('page', (v) => (v as num).toInt()),
          lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
          interventions: $checkedConvert(
            'interventions',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      InterventionQueueItem.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'lastPage': 'last_page',
      },
    );

Map<String, dynamic> _$InterventionQueueToJson(InterventionQueue instance) =>
    <String, dynamic>{
      'session_year_id': instance.sessionYearId,
      'page': instance.page,
      'last_page': instance.lastPage,
      'interventions': instance.interventions.map((e) => e.toJson()).toList(),
    };
