// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'recognition_award_window.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RecognitionAwardWindow _$RecognitionAwardWindowFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'RecognitionAwardWindow',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'award_category_id',
        'session_year_id',
        'opens_on',
        'closes_on',
        'status',
        'revision',
      ],
    );
    final val = RecognitionAwardWindow(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      awardCategoryId: $checkedConvert(
        'award_category_id',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      opensOn: $checkedConvert('opens_on', (v) => v as String),
      closesOn: $checkedConvert('closes_on', (v) => v as String),
      status: $checkedConvert('status', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num?)?.toInt(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'awardCategoryId': 'award_category_id',
    'sessionYearId': 'session_year_id',
    'opensOn': 'opens_on',
    'closesOn': 'closes_on',
    'activityGroupId': 'activity_group_id',
  },
);

Map<String, dynamic> _$RecognitionAwardWindowToJson(
  RecognitionAwardWindow instance,
) => <String, dynamic>{
  'id': instance.id,
  'award_category_id': instance.awardCategoryId,
  'session_year_id': instance.sessionYearId,
  'opens_on': instance.opensOn,
  'closes_on': instance.closesOn,
  'status': instance.status,
  'revision': instance.revision,
  'activity_group_id': ?instance.activityGroupId,
};
