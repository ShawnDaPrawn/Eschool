// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'vendor_operator_profile.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VendorOperatorProfile _$VendorOperatorProfileFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'VendorOperatorProfile',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'school_id',
        'school_name',
        'school_slug',
        'user_id',
        'name',
        'counters',
      ],
    );
    final val = VendorOperatorProfile(
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      schoolName: $checkedConvert('school_name', (v) => v as String),
      schoolSlug: $checkedConvert('school_slug', (v) => v as String),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      counters: $checkedConvert(
        'counters',
        (v) => (v as List<dynamic>)
            .map(
              (e) => VendorOperatorCounter.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'schoolId': 'school_id',
    'schoolName': 'school_name',
    'schoolSlug': 'school_slug',
    'userId': 'user_id',
  },
);

Map<String, dynamic> _$VendorOperatorProfileToJson(
  VendorOperatorProfile instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'school_name': instance.schoolName,
  'school_slug': instance.schoolSlug,
  'user_id': instance.userId,
  'name': instance.name,
  'counters': instance.counters.map((e) => e.toJson()).toList(),
};
