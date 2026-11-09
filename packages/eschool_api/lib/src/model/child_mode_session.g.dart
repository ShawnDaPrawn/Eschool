// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'child_mode_session.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ChildModeSession _$ChildModeSessionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ChildModeSession',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'learner_id',
            'first_name',
            'school',
            'opens',
            'started_at',
            'expires_at',
          ],
        );
        final val = ChildModeSession(
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          firstName: $checkedConvert('first_name', (v) => v as String),
          school: $checkedConvert(
            'school',
            (v) => ChildModeSessionSchool.fromJson(v as Map<String, dynamic>),
          ),
          opens: $checkedConvert(
            'opens',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => $enumDecode(
                    _$ChildModeSessionOpensEnumEnumMap,
                    e,
                    unknownValue:
                        ChildModeSessionOpensEnum.unknownDefaultOpenApi,
                  ),
                )
                .toList(),
          ),
          startedAt: $checkedConvert(
            'started_at',
            (v) => DateTime.parse(v as String),
          ),
          expiresAt: $checkedConvert(
            'expires_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'learnerId': 'learner_id',
        'firstName': 'first_name',
        'startedAt': 'started_at',
        'expiresAt': 'expires_at',
      },
    );

Map<String, dynamic> _$ChildModeSessionToJson(ChildModeSession instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'first_name': instance.firstName,
      'school': instance.school.toJson(),
      'opens': instance.opens
          .map((e) => _$ChildModeSessionOpensEnumEnumMap[e]!)
          .toList(),
      'started_at': instance.startedAt.toIso8601String(),
      'expires_at': instance.expiresAt.toIso8601String(),
    };

const _$ChildModeSessionOpensEnumEnumMap = {
  ChildModeSessionOpensEnum.myDay: 'my_day',
  ChildModeSessionOpensEnum.practice: 'practice',
  ChildModeSessionOpensEnum.tutor: 'tutor',
  ChildModeSessionOpensEnum.library_: 'library',
  ChildModeSessionOpensEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
