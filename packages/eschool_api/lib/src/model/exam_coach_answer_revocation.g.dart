// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'exam_coach_answer_revocation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ExamCoachAnswerRevocation _$ExamCoachAnswerRevocationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ExamCoachAnswerRevocation', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['id', 'state']);
  final val = ExamCoachAnswerRevocation(
    id: $checkedConvert('id', (v) => v as String),
    state: $checkedConvert(
      'state',
      (v) => $enumDecode(
        _$ExamCoachAnswerRevocationStateEnumEnumMap,
        v,
        unknownValue: ExamCoachAnswerRevocationStateEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$ExamCoachAnswerRevocationToJson(
  ExamCoachAnswerRevocation instance,
) => <String, dynamic>{
  'id': instance.id,
  'state': _$ExamCoachAnswerRevocationStateEnumEnumMap[instance.state]!,
};

const _$ExamCoachAnswerRevocationStateEnumEnumMap = {
  ExamCoachAnswerRevocationStateEnum.purged: 'purged',
  ExamCoachAnswerRevocationStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
