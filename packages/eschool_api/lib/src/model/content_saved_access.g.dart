// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_saved_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentSavedAccess _$ContentSavedAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ContentSavedAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'learner_id',
            'allowed_version_ids',
            'server_time',
            'offline_until',
          ],
        );
        final val = ContentSavedAccess(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          allowedVersionIds: $checkedConvert(
            'allowed_version_ids',
            (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toSet(),
          ),
          serverTime: $checkedConvert(
            'server_time',
            (v) => DateTime.parse(v as String),
          ),
          offlineUntil: $checkedConvert(
            'offline_until',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'learnerId': 'learner_id',
        'allowedVersionIds': 'allowed_version_ids',
        'serverTime': 'server_time',
        'offlineUntil': 'offline_until',
      },
    );

Map<String, dynamic> _$ContentSavedAccessToJson(ContentSavedAccess instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'reader_id': instance.readerId,
      'learner_id': instance.learnerId,
      'allowed_version_ids': instance.allowedVersionIds.toList(),
      'server_time': instance.serverTime.toIso8601String(),
      'offline_until': instance.offlineUntil.toIso8601String(),
    };
