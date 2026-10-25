// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_owner_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskOwnerChoices _$RiskOwnerChoicesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('RiskOwnerChoices', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['page', 'last_page', 'owners']);
      final val = RiskOwnerChoices(
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        owners: $checkedConvert(
          'owners',
          (v) => (v as List<dynamic>)
              .map((e) => RiskOwnerChoice.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$RiskOwnerChoicesToJson(RiskOwnerChoices instance) =>
    <String, dynamic>{
      'page': instance.page,
      'last_page': instance.lastPage,
      'owners': instance.owners.map((e) => e.toJson()).toList(),
    };
