// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_response_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryResponseData _$FamilySummaryResponseDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FamilySummaryResponseData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'date',
        'title',
        'sections',
        'private_updates',
        'notifications_target',
      ],
    );
    final val = FamilySummaryResponseData(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      date: $checkedConvert('date', (v) => DateTime.parse(v as String)),
      title: $checkedConvert('title', (v) => v as String),
      sections: $checkedConvert(
        'sections',
        (v) => (v as List<dynamic>)
            .map(
              (e) => FamilySummaryResponseDataSectionsInner.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      privateUpdates: $checkedConvert(
        'private_updates',
        (v) => (v as num).toInt(),
      ),
      notificationsTarget: $checkedConvert(
        'notifications_target',
        (v) => v as String,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'privateUpdates': 'private_updates',
    'notificationsTarget': 'notifications_target',
  },
);

Map<String, dynamic> _$FamilySummaryResponseDataToJson(
  FamilySummaryResponseData instance,
) => <String, dynamic>{
  'id': instance.id,
  'date': instance.date.toIso8601String(),
  'title': instance.title,
  'sections': instance.sections.map((e) => e.toJson()).toList(),
  'private_updates': instance.privateUpdates,
  'notifications_target': instance.notificationsTarget,
};
