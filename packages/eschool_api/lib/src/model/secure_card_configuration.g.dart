// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_configuration.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardConfiguration _$SecureCardConfigurationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SecureCardConfiguration',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'can_manage',
        'can_manage_readers',
        'writable',
        'types',
      ],
    );
    final val = SecureCardConfiguration(
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      canManageReaders: $checkedConvert('can_manage_readers', (v) => v as bool),
      writable: $checkedConvert('writable', (v) => v as bool),
      types: $checkedConvert(
        'types',
        (v) => (v as List<dynamic>).map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'canManage': 'can_manage',
    'canManageReaders': 'can_manage_readers',
  },
);

Map<String, dynamic> _$SecureCardConfigurationToJson(
  SecureCardConfiguration instance,
) => <String, dynamic>{
  'can_manage': instance.canManage,
  'can_manage_readers': instance.canManageReaders,
  'writable': instance.writable,
  'types': instance.types,
};
