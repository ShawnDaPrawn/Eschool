// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_response_entry.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationResponseEntry _$ConsultationResponseEntryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ConsultationResponseEntry',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['respondent_user_id', 'respondent_name', 'answers'],
    );
    final val = ConsultationResponseEntry(
      respondentUserId: $checkedConvert(
        'respondent_user_id',
        (v) => (v as num?)?.toInt(),
      ),
      respondentName: $checkedConvert('respondent_name', (v) => v as String?),
      answers: $checkedConvert(
        'answers',
        (v) => (v as List<dynamic>)
            .map(
              (e) => ConsultationResponseAnswer.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'respondentUserId': 'respondent_user_id',
    'respondentName': 'respondent_name',
  },
);

Map<String, dynamic> _$ConsultationResponseEntryToJson(
  ConsultationResponseEntry instance,
) => <String, dynamic>{
  'respondent_user_id': instance.respondentUserId,
  'respondent_name': instance.respondentName,
  'answers': instance.answers.map((e) => e.toJson()).toList(),
};
