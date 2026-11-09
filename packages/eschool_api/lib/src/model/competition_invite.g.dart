// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_invite.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionInvite _$CompetitionInviteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CompetitionInvite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'school_id']);
  final val = CompetitionInvite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'schoolId': 'school_id'});

Map<String, dynamic> _$CompetitionInviteToJson(CompetitionInvite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'school_id': instance.schoolId,
    };
