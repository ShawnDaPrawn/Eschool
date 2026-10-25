// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'intervention_history.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

InterventionHistory _$InterventionHistoryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('InterventionHistory', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['intervention_id', 'versions']);
      final val = InterventionHistory(
        interventionId: $checkedConvert(
          'intervention_id',
          (v) => (v as num).toInt(),
        ),
        versions: $checkedConvert(
          'versions',
          (v) => (v as List<dynamic>)
              .map(
                (e) => InterventionVersion.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'interventionId': 'intervention_id'});

Map<String, dynamic> _$InterventionHistoryToJson(
  InterventionHistory instance,
) => <String, dynamic>{
  'intervention_id': instance.interventionId,
  'versions': instance.versions.map((e) => e.toJson()).toList(),
};
