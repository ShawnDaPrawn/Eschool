// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'staff_private_notice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StaffPrivateNotice _$StaffPrivateNoticeFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StaffPrivateNotice', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'id',
          'feature',
          'title',
          'message',
          'writable',
          'read_at',
        ],
      );
      final val = StaffPrivateNotice(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        feature: $checkedConvert('feature', (v) => v as String),
        title: $checkedConvert('title', (v) => v as String),
        message: $checkedConvert('message', (v) => v as String),
        writable: $checkedConvert('writable', (v) => v as bool),
        readAt: $checkedConvert(
          'read_at',
          (v) => v == null ? null : DateTime.parse(v as String),
        ),
      );
      return val;
    }, fieldKeyMap: const {'readAt': 'read_at'});

Map<String, dynamic> _$StaffPrivateNoticeToJson(StaffPrivateNotice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'feature': instance.feature,
      'title': instance.title,
      'message': instance.message,
      'writable': instance.writable,
      'read_at': instance.readAt?.toIso8601String(),
    };
