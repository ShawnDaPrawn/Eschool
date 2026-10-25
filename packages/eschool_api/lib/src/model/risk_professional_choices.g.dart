// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'risk_professional_choices.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RiskProfessionalChoices _$RiskProfessionalChoicesFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('RiskProfessionalChoices', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'last_page', 'staff']);
  final val = RiskProfessionalChoices(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    staff: $checkedConvert(
      'staff',
      (v) => (v as List<dynamic>)
          .map((e) => RiskStaffChoice.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$RiskProfessionalChoicesToJson(
  RiskProfessionalChoices instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'staff': instance.staff.map((e) => e.toJson()).toList(),
};
