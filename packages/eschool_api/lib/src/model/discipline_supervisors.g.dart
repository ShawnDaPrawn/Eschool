// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_supervisors.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineSupervisors _$DisciplineSupervisorsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('DisciplineSupervisors', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['page', 'last_page', 'supervisors']);
  final val = DisciplineSupervisors(
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    supervisors: $checkedConvert(
      'supervisors',
      (v) => (v as List<dynamic>)
          .map((e) => DisciplineChoice.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$DisciplineSupervisorsToJson(
  DisciplineSupervisors instance,
) => <String, dynamic>{
  'page': instance.page,
  'last_page': instance.lastPage,
  'supervisors': instance.supervisors.map((e) => e.toJson()).toList(),
};
