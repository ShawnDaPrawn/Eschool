// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_result.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicResult _$AcademicResultFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicResult',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'state',
            'host_score',
            'guest_score',
            'winner',
            'entrants',
            'reasons',
            'revision',
            'submitted_at',
          ],
        );
        final val = AcademicResult(
          state: $checkedConvert(
            'state',
            (v) => $enumDecode(
              _$AcademicResultStateEnumEnumMap,
              v,
              unknownValue: AcademicResultStateEnum.unknownDefaultOpenApi,
            ),
          ),
          hostScore: $checkedConvert('host_score', (v) => (v as num?)?.toInt()),
          guestScore: $checkedConvert(
            'guest_score',
            (v) => (v as num?)?.toInt(),
          ),
          winner: $checkedConvert(
            'winner',
            (v) => $enumDecodeNullable(
              _$AcademicResultWinnerEnumEnumMap,
              v,
              unknownValue: AcademicResultWinnerEnum.unknownDefaultOpenApi,
            ),
          ),
          entrants: $checkedConvert(
            'entrants',
            (v) => (v as List<dynamic>)
                .map(
                  (e) =>
                      AcademicEntrantResult.fromJson(e as Map<String, dynamic>),
                )
                .toList(),
          ),
          reasons: $checkedConvert('reasons', (v) => v as String?),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          submittedAt: $checkedConvert(
            'submitted_at',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'hostScore': 'host_score',
        'guestScore': 'guest_score',
        'submittedAt': 'submitted_at',
      },
    );

Map<String, dynamic> _$AcademicResultToJson(AcademicResult instance) =>
    <String, dynamic>{
      'state': _$AcademicResultStateEnumEnumMap[instance.state]!,
      'host_score': instance.hostScore,
      'guest_score': instance.guestScore,
      'winner': _$AcademicResultWinnerEnumEnumMap[instance.winner],
      'entrants': instance.entrants.map((e) => e.toJson()).toList(),
      'reasons': instance.reasons,
      'revision': instance.revision,
      'submitted_at': instance.submittedAt?.toIso8601String(),
    };

const _$AcademicResultStateEnumEnumMap = {
  AcademicResultStateEnum.draft: 'draft',
  AcademicResultStateEnum.submitted: 'submitted',
  AcademicResultStateEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$AcademicResultWinnerEnumEnumMap = {
  AcademicResultWinnerEnum.host: 'host',
  AcademicResultWinnerEnum.guest: 'guest',
  AcademicResultWinnerEnum.draw: 'draw',
  AcademicResultWinnerEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
