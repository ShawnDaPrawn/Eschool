// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'family_summary_response_data_sections_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FamilySummaryResponseDataSectionsInner
_$FamilySummaryResponseDataSectionsInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FamilySummaryResponseDataSectionsInner',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'school_id',
            'school_name',
            'text',
            'wording',
            'unread_messages',
            'private_updates',
          ],
        );
        final val = FamilySummaryResponseDataSectionsInner(
          schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
          schoolName: $checkedConvert('school_name', (v) => v as String),
          text: $checkedConvert('text', (v) => v as String),
          wording: $checkedConvert(
            'wording',
            (v) => $enumDecode(
              _$FamilySummaryResponseDataSectionsInnerWordingEnumEnumMap,
              v,
              unknownValue: FamilySummaryResponseDataSectionsInnerWordingEnum
                  .unknownDefaultOpenApi,
            ),
          ),
          unreadMessages: $checkedConvert(
            'unread_messages',
            (v) => (v as num).toInt(),
          ),
          privateUpdates: $checkedConvert(
            'private_updates',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'schoolId': 'school_id',
        'schoolName': 'school_name',
        'unreadMessages': 'unread_messages',
        'privateUpdates': 'private_updates',
      },
    );

Map<String, dynamic> _$FamilySummaryResponseDataSectionsInnerToJson(
  FamilySummaryResponseDataSectionsInner instance,
) => <String, dynamic>{
  'school_id': instance.schoolId,
  'school_name': instance.schoolName,
  'text': instance.text,
  'wording':
      _$FamilySummaryResponseDataSectionsInnerWordingEnumEnumMap[instance
          .wording]!,
  'unread_messages': instance.unreadMessages,
  'private_updates': instance.privateUpdates,
};

const _$FamilySummaryResponseDataSectionsInnerWordingEnumEnumMap = {
  FamilySummaryResponseDataSectionsInnerWordingEnum.ai: 'ai',
  FamilySummaryResponseDataSectionsInnerWordingEnum.template: 'template',
  FamilySummaryResponseDataSectionsInnerWordingEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
