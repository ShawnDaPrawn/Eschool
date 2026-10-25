// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_catalogue_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionCatalogueChange _$RecognitionCatalogueChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionCatalogueChange',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['operation_id']);
    final val = RecognitionCatalogueChange(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      name: $checkedConvert('name', (v) => v as String?),
      active: $checkedConvert('active', (v) => v as bool?),
      emblem: $checkedConvert('emblem', (v) => v as String?),
      primaryColour: $checkedConvert('primary_colour', (v) => v as String?),
      secondaryColour: $checkedConvert('secondary_colour', (v) => v as String?),
      defaultPoints: $checkedConvert(
        'default_points',
        (v) => (v as num?)?.toInt(),
      ),
      minimumPoints: $checkedConvert(
        'minimum_points',
        (v) => (v as num?)?.toInt(),
      ),
      maximumPoints: $checkedConvert(
        'maximum_points',
        (v) => (v as num?)?.toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num?)?.toInt()),
      houseId: $checkedConvert('house_id', (v) => (v as num?)?.toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num?)?.toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String?),
      endsOn: $checkedConvert('ends_on', (v) => v as String?),
      reason: $checkedConvert('reason', (v) => v as String?),
      parentId: $checkedConvert('parent_id', (v) => (v as num?)?.toInt()),
      scopeKind: $checkedConvert('scope_kind', (v) => v as String?),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num?)?.toInt(),
      ),
      subjectId: $checkedConvert('subject_id', (v) => (v as num?)?.toInt()),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num?)?.toInt(),
      ),
      awardCategoryId: $checkedConvert(
        'award_category_id',
        (v) => (v as num?)?.toInt(),
      ),
      criteria: $checkedConvert('criteria', (v) => v as String?),
      frequency: $checkedConvert('frequency', (v) => v as String?),
      opensOn: $checkedConvert('opens_on', (v) => v as String?),
      closesOn: $checkedConvert('closes_on', (v) => v as String?),
      status: $checkedConvert('status', (v) => v as String?),
      userId: $checkedConvert('user_id', (v) => (v as num?)?.toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'primaryColour': 'primary_colour',
    'secondaryColour': 'secondary_colour',
    'defaultPoints': 'default_points',
    'minimumPoints': 'minimum_points',
    'maximumPoints': 'maximum_points',
    'learnerId': 'learner_id',
    'houseId': 'house_id',
    'sessionYearId': 'session_year_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'parentId': 'parent_id',
    'scopeKind': 'scope_kind',
    'gradeLevelId': 'grade_level_id',
    'subjectId': 'subject_id',
    'activityGroupId': 'activity_group_id',
    'awardCategoryId': 'award_category_id',
    'opensOn': 'opens_on',
    'closesOn': 'closes_on',
    'userId': 'user_id',
  },
);

Map<String, dynamic> _$RecognitionCatalogueChangeToJson(
  RecognitionCatalogueChange instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': ?instance.revision,
  'name': ?instance.name,
  'active': ?instance.active,
  'emblem': ?instance.emblem,
  'primary_colour': ?instance.primaryColour,
  'secondary_colour': ?instance.secondaryColour,
  'default_points': ?instance.defaultPoints,
  'minimum_points': ?instance.minimumPoints,
  'maximum_points': ?instance.maximumPoints,
  'learner_id': ?instance.learnerId,
  'house_id': ?instance.houseId,
  'session_year_id': ?instance.sessionYearId,
  'starts_on': ?instance.startsOn,
  'ends_on': ?instance.endsOn,
  'reason': ?instance.reason,
  'parent_id': ?instance.parentId,
  'scope_kind': ?instance.scopeKind,
  'grade_level_id': ?instance.gradeLevelId,
  'subject_id': ?instance.subjectId,
  'activity_group_id': ?instance.activityGroupId,
  'award_category_id': ?instance.awardCategoryId,
  'criteria': ?instance.criteria,
  'frequency': ?instance.frequency,
  'opens_on': ?instance.opensOn,
  'closes_on': ?instance.closesOn,
  'status': ?instance.status,
  'user_id': ?instance.userId,
};
