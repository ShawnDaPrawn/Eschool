// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionList _$CompetitionListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('CompetitionList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items']);
      final val = CompetitionList(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => CompetitionSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    });

Map<String, dynamic> _$CompetitionListToJson(CompetitionList instance) =>
    <String, dynamic>{'items': instance.items.map((e) => e.toJson()).toList()};
