// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_approval.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeApproval _$PracticeApprovalFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeApproval', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'version_id', 'revision', 'active'],
      );
      final val = PracticeApproval(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        versionId: $checkedConvert('version_id', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        active: $checkedConvert('active', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'versionId': 'version_id'});

Map<String, dynamic> _$PracticeApprovalToJson(PracticeApproval instance) =>
    <String, dynamic>{
      'id': instance.id,
      'version_id': instance.versionId,
      'revision': instance.revision,
      'active': instance.active,
    };
