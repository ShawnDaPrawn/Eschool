// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'learner_passport_transfer_reports_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LearnerPassportTransferReportsInner
_$LearnerPassportTransferReportsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate('LearnerPassportTransferReportsInner', json, (
      $checkedConvert,
    ) {
      $checkKeys(json, requiredKeys: const ['year_name', 'term_name']);
      final val = LearnerPassportTransferReportsInner(
        yearName: $checkedConvert('year_name', (v) => v as String),
        termName: $checkedConvert('term_name', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'yearName': 'year_name', 'termName': 'term_name'});

Map<String, dynamic> _$LearnerPassportTransferReportsInnerToJson(
  LearnerPassportTransferReportsInner instance,
) => <String, dynamic>{
  'year_name': instance.yearName,
  'term_name': instance.termName,
};
