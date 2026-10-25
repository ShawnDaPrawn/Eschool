// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report_thread.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReportThread _$WellbeingReportThreadFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingReportThread',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'staff_user_id',
        'author_type',
        'internal',
        'body',
        'created_at',
        'author_name',
      ],
    );
    final val = WellbeingReportThread(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      staffUserId: $checkedConvert(
        'staff_user_id',
        (v) => (v as num?)?.toInt(),
      ),
      authorType: $checkedConvert(
        'author_type',
        (v) => $enumDecode(
          _$WellbeingReportThreadAuthorTypeEnumEnumMap,
          v,
          unknownValue:
              WellbeingReportThreadAuthorTypeEnum.unknownDefaultOpenApi,
        ),
      ),
      internal: $checkedConvert('internal', (v) => v as bool),
      body: $checkedConvert('body', (v) => v as String),
      createdAt: $checkedConvert(
        'created_at',
        (v) => DateTime.parse(v as String),
      ),
      authorName: $checkedConvert('author_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'staffUserId': 'staff_user_id',
    'authorType': 'author_type',
    'createdAt': 'created_at',
    'authorName': 'author_name',
  },
);

Map<String, dynamic> _$WellbeingReportThreadToJson(
  WellbeingReportThread instance,
) => <String, dynamic>{
  'id': instance.id,
  'staff_user_id': instance.staffUserId,
  'author_type':
      _$WellbeingReportThreadAuthorTypeEnumEnumMap[instance.authorType]!,
  'internal': instance.internal,
  'body': instance.body,
  'created_at': instance.createdAt.toIso8601String(),
  'author_name': instance.authorName,
};

const _$WellbeingReportThreadAuthorTypeEnumEnumMap = {
  WellbeingReportThreadAuthorTypeEnum.reporter: 'reporter',
  WellbeingReportThreadAuthorTypeEnum.staff: 'staff',
  WellbeingReportThreadAuthorTypeEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
