// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detentions.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentions _$DisciplineDetentionsFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentions',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_year_id', 'page', 'last_page', 'sessions'],
    );
    final val = DisciplineDetentions(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      sessions: $checkedConvert(
        'sessions',
        (v) => (v as List<dynamic>)
            .map((e) => DisciplineDetention.fromJson(e as Map<String, dynamic>))
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

Map<String, dynamic> _$DisciplineDetentionsToJson(
  DisciplineDetentions instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'sessions': instance.sessions.map((e) => e.toJson()).toList(),
};
