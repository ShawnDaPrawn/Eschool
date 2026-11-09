// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'competition_members_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CompetitionMembersInner _$CompetitionMembersInnerFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('CompetitionMembersInner', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['name', 'role', 'state']);
  final val = CompetitionMembersInner(
    name: $checkedConvert('name', (v) => v as String),
    role: $checkedConvert('role', (v) => v as String),
    state: $checkedConvert('state', (v) => v as String),
  );
  return val;
});

Map<String, dynamic> _$CompetitionMembersInnerToJson(
  CompetitionMembersInner instance,
) => <String, dynamic>{
  'name': instance.name,
  'role': instance.role,
  'state': instance.state,
};
