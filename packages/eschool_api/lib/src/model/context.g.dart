// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'context.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Context _$ContextFromJson(Map<String, dynamic> json) => $checkedCreate(
  'Context',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'user',
        'schools',
        'current_school',
        'modules',
        'notification_rules',
        'feature_flags',
        'sign_in_methods',
        'children',
      ],
    );
    final val = Context(
      user: $checkedConvert(
        'user',
        (v) => ContextUser.fromJson(v as Map<String, dynamic>),
      ),
      schools: $checkedConvert(
        'schools',
        (v) => (v as List<dynamic>)
            .map((e) => SchoolMembership.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      currentSchool: $checkedConvert(
        'current_school',
        (v) => CurrentSchool.fromJson(v as Map<String, dynamic>),
      ),
      modules: $checkedConvert(
        'modules',
        (v) => (v as List<dynamic>)
            .map((e) => Module.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      notificationRules: $checkedConvert(
        'notification_rules',
        (v) => (v as List<dynamic>)
            .map((e) => NotificationRule.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      profileFields: $checkedConvert(
        'profile_fields',
        (v) => (v as List<dynamic>?)
            ?.map(
              (e) => ProfileFieldSummary.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      featureFlags: $checkedConvert(
        'feature_flags',
        (v) => Map<String, bool>.from(v as Map),
      ),
      signInMethods: $checkedConvert(
        'sign_in_methods',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
      children: $checkedConvert(
        'children',
        (v) => (v as List<dynamic>)
            .map((e) => Child.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      approval: $checkedConvert(
        'approval',
        (v) => v == null
            ? null
            : ApprovalState.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'currentSchool': 'current_school',
    'notificationRules': 'notification_rules',
    'profileFields': 'profile_fields',
    'featureFlags': 'feature_flags',
    'signInMethods': 'sign_in_methods',
  },
);

Map<String, dynamic> _$ContextToJson(Context instance) => <String, dynamic>{
  'user': instance.user.toJson(),
  'schools': instance.schools.map((e) => e.toJson()).toList(),
  'current_school': instance.currentSchool.toJson(),
  'modules': instance.modules.map((e) => e.toJson()).toList(),
  'notification_rules': instance.notificationRules
      .map((e) => e.toJson())
      .toList(),
  'profile_fields': ?instance.profileFields?.map((e) => e.toJson()).toList(),
  'feature_flags': instance.featureFlags,
  'sign_in_methods': instance.signInMethods,
  'children': instance.children.map((e) => e.toJson()).toList(),
  'approval': ?instance.approval?.toJson(),
};
