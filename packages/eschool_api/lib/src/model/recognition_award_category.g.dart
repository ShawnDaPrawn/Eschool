// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_award_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAwardCategory _$RecognitionAwardCategoryFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionAwardCategory',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'name',
        'parent_id',
        'scope_kind',
        'grade_level_id',
        'subject_id',
        'activity_group_id',
        'active',
        'revision',
        'can_manage',
      ],
    );
    final val = RecognitionAwardCategory(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      parentId: $checkedConvert('parent_id', (v) => (v as num?)?.toInt()),
      scopeKind: $checkedConvert('scope_kind', (v) => v as String),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num?)?.toInt(),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
    );
    return val;
  },
  fieldKeyMap: const {
    'parentId': 'parent_id',
    'scopeKind': 'scope_kind',
    'gradeLevelId': 'grade_level_id',
    'subjectId': 'subject_id',
    'activityGroupId': 'activity_group_id',
    'canManage': 'can_manage',
  },
);

Map<String, dynamic> _$RecognitionAwardCategoryToJson(
  RecognitionAwardCategory instance,
) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'parent_id': instance.parentId,
  'scope_kind': instance.scopeKind,
  'grade_level_id': instance.gradeLevelId,
  'subject_id': instance.subjectId,
  'activity_group_id': instance.activityGroupId,
  'active': instance.active,
  'revision': instance.revision,
  'can_manage': instance.canManage,
};
