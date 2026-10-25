// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_detail.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupDetail _$ActivityGroupDetailFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityGroupDetail', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'group',
      'materials',
      'sessions',
      'can_manage',
      'can_capture',
    ],
  );
  final val = ActivityGroupDetail(
    group: $checkedConvert(
      'group',
      (v) => ActivityGroup.fromJson(v as Map<String, dynamic>),
    ),
    materials: $checkedConvert(
      'materials',
      (v) => (v as List<dynamic>)
          .map((e) => ActivityMaterialView.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    sessions: $checkedConvert(
      'sessions',
      (v) => (v as List<dynamic>)
          .map((e) => ActivitySession.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    canManage: $checkedConvert('can_manage', (v) => v as bool),
    canCapture: $checkedConvert('can_capture', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'canManage': 'can_manage', 'canCapture': 'can_capture'});

Map<String, dynamic> _$ActivityGroupDetailToJson(
  ActivityGroupDetail instance,
) => <String, dynamic>{
  'group': instance.group.toJson(),
  'materials': instance.materials.map((e) => e.toJson()).toList(),
  'sessions': instance.sessions.map((e) => e.toJson()).toList(),
  'can_manage': instance.canManage,
  'can_capture': instance.canCapture,
};
