// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'weekly_digest_access.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WeeklyDigestAccess _$WeeklyDigestAccessFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'WeeklyDigestAccess',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'account_id',
            'parent',
            'can_manage',
            'children',
            'language',
            'preference_revision',
            'languages',
            'approved_languages',
          ],
        );
        final val = WeeklyDigestAccess(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          accountId: $checkedConvert('account_id', (v) => (v as num).toInt()),
          parent: $checkedConvert('parent', (v) => v as bool),
          canManage: $checkedConvert('can_manage', (v) => v as bool),
          children: $checkedConvert(
            'children',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => WeeklyDigestChild.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          language: $checkedConvert('language', (v) => v as String),
          preferenceRevision: $checkedConvert(
            'preference_revision',
            (v) => (v as num).toInt(),
          ),
          languages: $checkedConvert(
            'languages',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
          approvedLanguages: $checkedConvert(
            'approved_languages',
            (v) => (v as List<dynamic>).map((e) => e as String).toList(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'accountId': 'account_id',
        'canManage': 'can_manage',
        'preferenceRevision': 'preference_revision',
        'approvedLanguages': 'approved_languages',
      },
    );

Map<String, dynamic> _$WeeklyDigestAccessToJson(WeeklyDigestAccess instance) =>
    <String, dynamic>{
      'school_id': instance.schoolId,
      'account_id': instance.accountId,
      'parent': instance.parent,
      'can_manage': instance.canManage,
      'children': instance.children.map((e) => e.toJson()).toList(),
      'language': instance.language,
      'preference_revision': instance.preferenceRevision,
      'languages': instance.languages,
      'approved_languages': instance.approvedLanguages,
    };
