// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_thread_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatThreadPage _$GroupChatThreadPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatThreadPage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'items',
            'next_before',
            'policy',
            'persona',
            'writable',
            'max_characters',
          ],
        );
        final val = GroupChatThreadPage(
          items: $checkedConvert(
            'items',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => GroupChatThreadSummary.fromJson(
                    e as Map<String, dynamic>,
                  ),
                )
                .toList(),
          ),
          nextBefore: $checkedConvert('next_before', (v) => v as String?),
          policy: $checkedConvert(
            'policy',
            (v) => GroupChatPolicy.fromJson(v as Map<String, dynamic>),
          ),
          persona: $checkedConvert(
            'persona',
            (v) => $enumDecode(
              _$GroupChatPersonaEnumMap,
              v,
              unknownValue: GroupChatPersona.unknownDefaultOpenApi,
            ),
          ),
          writable: $checkedConvert('writable', (v) => v as bool),
          maxCharacters: $checkedConvert(
            'max_characters',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'nextBefore': 'next_before',
        'maxCharacters': 'max_characters',
      },
    );

Map<String, dynamic> _$GroupChatThreadPageToJson(
  GroupChatThreadPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_before': instance.nextBefore,
  'policy': instance.policy.toJson(),
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'writable': instance.writable,
  'max_characters': instance.maxCharacters,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
