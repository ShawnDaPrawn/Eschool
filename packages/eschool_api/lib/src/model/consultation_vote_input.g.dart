// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_vote_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationVoteInput _$ConsultationVoteInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationVoteInput', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['answers']);
  final val = ConsultationVoteInput(
    answers: $checkedConvert(
      'answers',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ConsultationVoteAnswer.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
});

Map<String, dynamic> _$ConsultationVoteInputToJson(
  ConsultationVoteInput instance,
) => <String, dynamic>{
  'answers': instance.answers.map((e) => e.toJson()).toList(),
};
