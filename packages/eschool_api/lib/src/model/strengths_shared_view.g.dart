// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_shared_view.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsSharedView _$StrengthsSharedViewFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'StrengthsSharedView',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'reader_id',
            'id',
            'kind',
            'shared_on',
            'expires_at',
            'notice',
            'mirror',
          ],
        );
        final val = StrengthsSharedView(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$StrengthsSharedViewKindEnumEnumMap,
              v,
              unknownValue: StrengthsSharedViewKindEnum.unknownDefaultOpenApi,
            ),
          ),
          sharedOn: $checkedConvert(
            'shared_on',
            (v) => DateTime.parse(v as String),
          ),
          expiresAt: $checkedConvert(
            'expires_at',
            (v) => DateTime.parse(v as String),
          ),
          notice: $checkedConvert('notice', (v) => v as String),
          mirror: $checkedConvert(
            'mirror',
            (v) => StrengthsSnapshot.fromJson(v as Map<String, dynamic>),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'readerId': 'reader_id',
        'sharedOn': 'shared_on',
        'expiresAt': 'expires_at',
      },
    );

Map<String, dynamic> _$StrengthsSharedViewToJson(
  StrengthsSharedView instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
  'id': instance.id,
  'kind': _$StrengthsSharedViewKindEnumEnumMap[instance.kind]!,
  'shared_on': instance.sharedOn.toIso8601String(),
  'expires_at': instance.expiresAt.toIso8601String(),
  'notice': instance.notice,
  'mirror': instance.mirror.toJson(),
};

const _$StrengthsSharedViewKindEnumEnumMap = {
  StrengthsSharedViewKindEnum.guardian: 'guardian',
  StrengthsSharedViewKindEnum.teacher: 'teacher',
  StrengthsSharedViewKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
