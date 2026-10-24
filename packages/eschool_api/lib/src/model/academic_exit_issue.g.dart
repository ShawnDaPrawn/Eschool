// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_issue.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitIssue _$AcademicExitIssueFromJson(Map<String, dynamic> json) =>
    $checkedCreate('AcademicExitIssue', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['type', 'id']);
      final val = AcademicExitIssue(
        type: $checkedConvert('type', (v) => v as String),
        id: $checkedConvert('id', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$AcademicExitIssueToJson(AcademicExitIssue instance) =>
    <String, dynamic>{'type': instance.type, 'id': instance.id};
