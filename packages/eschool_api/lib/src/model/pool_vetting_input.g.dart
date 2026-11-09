// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_vetting_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolVettingInput _$PoolVettingInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PoolVettingInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'outcome',
            'checks',
            'checked_on',
          ],
        );
        final val = PoolVettingInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          outcome: $checkedConvert(
            'outcome',
            (v) => $enumDecode(
              _$PoolVettingInputOutcomeEnumEnumMap,
              v,
              unknownValue: PoolVettingInputOutcomeEnum.unknownDefaultOpenApi,
            ),
          ),
          checks: $checkedConvert(
            'checks',
            (v) => (v as List<dynamic>)
                .map(
                  (e) => $enumDecode(
                    _$PoolVettingInputChecksEnumEnumMap,
                    e,
                    unknownValue:
                        PoolVettingInputChecksEnum.unknownDefaultOpenApi,
                  ),
                )
                .toList(),
          ),
          checkedOn: $checkedConvert(
            'checked_on',
            (v) => DateTime.parse(v as String),
          ),
          expiresOn: $checkedConvert(
            'expires_on',
            (v) => v == null ? null : DateTime.parse(v as String),
          ),
          note: $checkedConvert('note', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'checkedOn': 'checked_on',
        'expiresOn': 'expires_on',
      },
    );

Map<String, dynamic> _$PoolVettingInputToJson(PoolVettingInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'outcome': _$PoolVettingInputOutcomeEnumEnumMap[instance.outcome]!,
      'checks': instance.checks
          .map((e) => _$PoolVettingInputChecksEnumEnumMap[e]!)
          .toList(),
      'checked_on': instance.checkedOn.toIso8601String(),
      'expires_on': ?instance.expiresOn?.toIso8601String(),
      'note': ?instance.note,
    };

const _$PoolVettingInputOutcomeEnumEnumMap = {
  PoolVettingInputOutcomeEnum.cleared: 'cleared',
  PoolVettingInputOutcomeEnum.refused: 'refused',
  PoolVettingInputOutcomeEnum.revoked: 'revoked',
  PoolVettingInputOutcomeEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PoolVettingInputChecksEnumEnumMap = {
  PoolVettingInputChecksEnum.identity: 'identity',
  PoolVettingInputChecksEnum.childProtectionRegister:
      'child_protection_register',
  PoolVettingInputChecksEnum.sexualOffencesRegister: 'sexual_offences_register',
  PoolVettingInputChecksEnum.policeClearance: 'police_clearance',
  PoolVettingInputChecksEnum.references: 'references',
  PoolVettingInputChecksEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
